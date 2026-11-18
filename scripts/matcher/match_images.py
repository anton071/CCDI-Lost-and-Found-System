#!/usr/bin/env python3
"""
CCDI Lost and Found - feature-matching worker.

Workflow (thesis Chapter 1): SIFT first; ORB only when no candidate satisfies the
configured SIFT acceptance criterion. Input is one JSON document on stdin, output is one
JSON document on stdout. Nothing is ever executed from the input: it only carries file
paths (which must live under `allowed_root`), identifiers and numeric settings.

Per candidate pair the evidence is:
  good_matches   descriptor matches that survive Lowe's ratio test (incorrect-match filter 1)
  inliers        matches consistent with ONE homography found by RANSAC (incorrect-match filter 2)
  inlier_ratio   inliers / good_matches
A pair is "accepted" only if inliers >= min_inliers AND inlier_ratio >= min_inlier_ratio
AND both images have at least min_keypoints keypoints. Raw keypoint counts are never turned
into a similarity percentage. Accepted pairs are potential matches only - never proof of ownership.
"""
import hashlib
import json
import os
import sys
import time

import cv2
import numpy as np

SCHEMA_VERSION = 1


class ImageError(Exception):
    def __init__(self, code, message):
        super().__init__(message)
        self.code = code
        self.message = message


def load_gray(path, allowed_root, max_bytes, max_dim):
    real = os.path.realpath(path)
    root = os.path.realpath(allowed_root)
    try:
        inside = os.path.commonpath([real, root]) == root
    except ValueError:
        inside = False
    if not inside:
        raise ImageError("path_not_allowed", "Image path is outside the permitted storage directory.")
    if not os.path.isfile(real):
        raise ImageError("file_missing", "Image file does not exist.")
    if os.path.getsize(real) > max_bytes:
        raise ImageError("file_too_large", "Image file exceeds the processing size limit.")
    with open(real, "rb") as fh:
        raw = fh.read()
    sha = hashlib.sha256(raw).hexdigest()
    img = cv2.imdecode(np.frombuffer(raw, dtype=np.uint8), cv2.IMREAD_GRAYSCALE)
    if img is None or img.size == 0:
        raise ImageError("unreadable_image", "File is not a readable image.")
    h, w = img.shape[:2]
    scale = max_dim / float(max(h, w))
    if scale < 1.0:
        img = cv2.resize(img, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)
    return img, w, h, sha


def make_extractor(algo, cfg):
    if algo == "SIFT":
        if not hasattr(cv2, "SIFT_create"):
            raise ImageError("sift_unavailable", "This OpenCV build does not provide SIFT (needs OpenCV >= 4.4).")
        return cv2.SIFT_create(nfeatures=int(cfg["n_features"]))
    return cv2.ORB_create(nfeatures=int(cfg["n_features"]))


def extract(entry, algo, cfg, ctx):
    """Return dict(pts, desc, width, height, keypoints, cached, cache_file) for one image."""
    img, w, h, sha = load_gray(entry["path"], ctx["allowed_root"], ctx["max_image_bytes"], ctx["max_dim"])
    cache_file = None
    if ctx.get("cache_dir") and entry.get("id") is not None:
        # sha256 and n_features are part of the name, so replacing an image or changing the
        # setting automatically invalidates the cached descriptors.
        name = "%s_%s_%s_%s.npz" % (int(entry["id"]), algo.lower(), sha[:16], int(cfg["n_features"]))
        cache_file = os.path.join(ctx["cache_dir"], name)
        if os.path.isfile(cache_file):
            try:
                z = np.load(cache_file, allow_pickle=False)
                return dict(pts=z["pts"], desc=z["desc"], width=w, height=h,
                            keypoints=int(len(z["pts"])), cached=True, cache_file=name, sha256=sha)
            except Exception:
                pass  # corrupt cache: fall through and rebuild
    extractor = make_extractor(algo, cfg)
    kps, desc = extractor.detectAndCompute(img, None)
    if desc is None or len(kps) == 0:
        pts = np.zeros((0, 2), dtype=np.float32)
        desc = np.zeros((0, 128 if algo == "SIFT" else 32), dtype=np.float32 if algo == "SIFT" else np.uint8)
    else:
        pts = np.float32([k.pt for k in kps])
    if cache_file:
        try:
            os.makedirs(ctx["cache_dir"], exist_ok=True)
            np.savez_compressed(cache_file, pts=pts, desc=desc)
        except OSError:
            cache_file = None
    return dict(pts=pts, desc=desc, width=w, height=h, keypoints=int(len(pts)),
                cached=False, cache_file=os.path.basename(cache_file) if cache_file else None, sha256=sha)


def compare(q, c, algo, cfg):
    """Descriptor matching + ratio test + RANSAC homography for one pair."""
    out = dict(good_matches=0, inliers=0, inlier_ratio=0.0, accepted=False, reason=None)
    if q["keypoints"] < cfg["min_keypoints"] or c["keypoints"] < cfg["min_keypoints"]:
        out["reason"] = "insufficient_keypoints"
        return out
    norm = cv2.NORM_L2 if algo == "SIFT" else cv2.NORM_HAMMING
    matcher = cv2.BFMatcher(norm)
    knn = matcher.knnMatch(q["desc"], c["desc"], k=2)
    good = [p[0] for p in knn if len(p) == 2 and p[0].distance < cfg["ratio"] * p[1].distance]
    out["good_matches"] = len(good)
    if len(good) < 4:
        out["reason"] = "too_few_good_matches"
        return out
    src = np.float32([q["pts"][m.queryIdx] for m in good]).reshape(-1, 1, 2)
    dst = np.float32([c["pts"][m.trainIdx] for m in good]).reshape(-1, 1, 2)
    H, mask = cv2.findHomography(src, dst, cv2.RANSAC, float(cfg["ransac_px"]))
    if H is None or mask is None:
        out["reason"] = "no_consistent_geometry"
        return out
    det = abs(float(np.linalg.det(H[:2, :2])))
    if not (0.01 <= det <= 100.0) or not np.all(np.isfinite(H)):
        out["reason"] = "degenerate_geometry"  # collapsed/exploded transform: reject spurious inliers
        return out
    inliers = int(mask.sum())
    out["inliers"] = inliers
    out["inlier_ratio"] = round(inliers / len(good), 4)
    if inliers >= cfg["min_inliers"] and out["inlier_ratio"] >= cfg["min_inlier_ratio"]:
        out["accepted"] = True
        out["reason"] = "accepted"
    else:
        out["reason"] = "below_threshold"
    return out


def run_stage(algo, query, candidates, cfg, ctx):
    started = time.time()
    rows, image_info = [], {}
    try:
        qf = extract(query, algo, cfg, ctx)
    except ImageError as e:
        raise
    image_info[str(query["id"])] = dict(algorithm=algo, keypoints=qf["keypoints"], width=qf["width"],
                                        height=qf["height"], cache_file=qf["cache_file"], sha256=qf["sha256"], error=None)
    for cand in candidates:
        try:
            cf = extract(cand, algo, cfg, ctx)
        except ImageError as e:
            image_info[str(cand["id"])] = dict(algorithm=algo, keypoints=None, width=None, height=None,
                                               cache_file=None, sha256=None, error=e.code)
            rows.append(dict(candidate_id=cand["id"], algorithm=algo, good_matches=0, inliers=0, inlier_ratio=0.0,
                             accepted=False, reason=e.code, query_keypoints=qf["keypoints"], candidate_keypoints=None))
            continue
        image_info[str(cand["id"])] = dict(algorithm=algo, keypoints=cf["keypoints"], width=cf["width"],
                                           height=cf["height"], cache_file=cf["cache_file"], sha256=cf["sha256"], error=None)
        res = compare(qf, cf, algo, cfg)
        res.update(candidate_id=cand["id"], algorithm=algo, query_keypoints=qf["keypoints"],
                   candidate_keypoints=cf["keypoints"])
        rows.append(res)
    accepted = sorted([r for r in rows if r["accepted"]], key=lambda r: (-r["inliers"], -r["inlier_ratio"]))
    return dict(algorithm=algo, rows=rows, accepted=accepted, images=image_info,
                seconds=round(time.time() - started, 3))


def main():
    try:
        job = json.load(sys.stdin)
        query = job["query"]
        candidates = job.get("candidates", [])
        s = job["settings"]
        ctx = dict(allowed_root=job["allowed_root"], cache_dir=job.get("cache_dir"),
                   max_image_bytes=int(s.get("max_image_bytes", 10 * 1024 * 1024)),
                   max_dim=int(s.get("max_dimension", 1024)))
    except Exception as e:  # malformed job
        print(json.dumps(dict(ok=False, error=dict(code="bad_request", message="Invalid job description: %s" % e))))
        return 2

    stages = []
    try:
        sift = run_stage("SIFT", query, candidates, s["sift"], ctx)
        stages.append(sift)
        chosen, fallback_used = sift, False
        if not sift["accepted"]:
            # Fallback condition: SIFT produced NO candidate that satisfies its acceptance criterion.
            orb = run_stage("ORB", query, candidates, s["orb"], ctx)
            stages.append(orb)
            fallback_used = True
            chosen = orb if orb["accepted"] else None
    except ImageError as e:
        print(json.dumps(dict(ok=False, error=dict(code=e.code, message=e.message))))
        return 2
    except Exception as e:
        print(json.dumps(dict(ok=False, error=dict(code="engine_error", message=str(e)))))
        return 2

    images = {}
    for st in stages:
        images.setdefault(st["algorithm"], st["images"])
    print(json.dumps(dict(
        ok=True,
        schema=SCHEMA_VERSION,
        opencv=cv2.__version__,
        fallback_used=fallback_used,
        final_algorithm=chosen["algorithm"] if chosen else None,
        matches=chosen["accepted"] if chosen else [],
        stages=[dict(algorithm=st["algorithm"], seconds=st["seconds"], evaluated=st["rows"]) for st in stages],
        images=images,
    )))
    return 0


if __name__ == "__main__":
    sys.exit(main())
