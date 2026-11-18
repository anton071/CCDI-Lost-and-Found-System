#!/usr/bin/env python3
"""
Generate a small CONTROLLED test dataset for the matcher from scikit-image's bundled sample
photographs (astronaut, coffee, chelsea, rocket). It writes "found" originals plus "lost"
re-photographed variants (viewpoint, lighting, blur, JPEG quality, crop), plus negatives.

    pip install scikit-image
    python scripts/matcher/make_sample_dataset.py storage/app/private/sample-dataset

The images are generated locally and are NOT shipped with the project.
This is a smoke-test dataset, not evidence of accuracy on real CCDI belongings.
"""
import os
import sys

import cv2
import numpy as np
from skimage import data


def save(path, img, q=92):
    cv2.imwrite(path, cv2.cvtColor(img, cv2.COLOR_RGB2BGR), [cv2.IMWRITE_JPEG_QUALITY, q])


def rotate_scale(img, deg, scale):
    h, w = img.shape[:2]
    M = cv2.getRotationMatrix2D((w / 2, h / 2), deg, scale)
    return cv2.warpAffine(img, M, (w, h), borderMode=cv2.BORDER_REFLECT)


def perspective(img, amt=0.08):
    h, w = img.shape[:2]
    src = np.float32([[0, 0], [w, 0], [w, h], [0, h]])
    d = amt * min(h, w)
    dst = np.float32([[d, d * .5], [w - d * .4, 0], [w - d, h - d * .6], [d * .3, h]])
    return cv2.warpPerspective(img, cv2.getPerspectiveTransform(src, dst), (w, h), borderMode=cv2.BORDER_REFLECT)


def main(out):
    os.makedirs(out, exist_ok=True)
    objs = {"astronaut": data.astronaut(), "coffee": data.coffee(), "chelsea": data.chelsea(), "rocket": data.rocket()}
    for name, img in objs.items():
        save(os.path.join(out, f"found_{name}.jpg"), img)
    a = objs["astronaut"]
    # positives: same physical object, different shot
    save(os.path.join(out, "lost_astronaut_rotated.jpg"), rotate_scale(a, 18, 0.8))
    save(os.path.join(out, "lost_astronaut_dim_blur.jpg"),
         cv2.GaussianBlur((a * 0.65).astype(np.uint8), (5, 5), 1.4), q=45)
    save(os.path.join(out, "lost_astronaut_perspective.jpg"), perspective(a))
    c = objs["coffee"]
    save(os.path.join(out, "lost_coffee_crop.jpg"), c[40:340, 80:520])
    save(os.path.join(out, "lost_rocket_shifted.jpg"), rotate_scale(objs["rocket"], -10, 0.9))
    # negatives: objects with NO counterpart among the found items, and featureless images
    for name, img in {"camera": data.camera(), "coins": data.coins()}.items():
        if img.ndim == 2:
            img = np.stack([img] * 3, axis=-1)
        save(os.path.join(out, f"lost_negative_{name}.jpg"), img)
    save(os.path.join(out, "lost_blank.jpg"), np.full((300, 300, 3), 200, np.uint8))
    save(os.path.join(out, "lost_gradient.jpg"),
         np.repeat(np.linspace(0, 255, 300, dtype=np.uint8)[None, :, None], 300, 0).repeat(3, 2))
    with open(os.path.join(out, "not_an_image.jpg"), "wb") as fh:
        fh.write(b"this is plain text pretending to be a jpeg")
    print("dataset written to", out)


if __name__ == "__main__":
    main(sys.argv[1] if len(sys.argv) > 1 else "sample-dataset")
