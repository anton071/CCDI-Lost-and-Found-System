<?php

namespace App\Services;

use Illuminate\Support\Collection;

/** Result of one matching run. `status` tells the UI what happened; it never claims ownership. */
final class MatchOutcome
{
    public const MATCHED = 'matched';
    public const NO_MATCH = 'no_match';
    public const NO_IMAGE = 'no_image';
    public const NO_CANDIDATES = 'no_candidates';
    public const UNAVAILABLE = 'unavailable';

    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly Collection $matches,
        public readonly ?string $algorithm = null,
        public readonly bool $fallbackUsed = false,
    ) {}

    public static function of(string $status, string $message): self
    {
        return new self($status, $message, collect());
    }

    public function failed(): bool
    {
        return $this->status === self::UNAVAILABLE;
    }
}
