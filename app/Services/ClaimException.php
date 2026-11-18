<?php

namespace App\Services;

use RuntimeException;

/** A business-rule violation in the claim workflow. Message is safe to show to the user. */
class ClaimException extends RuntimeException {}
