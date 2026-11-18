<?php

namespace App\Services;

use RuntimeException;

/** The OpenCV worker could not run or reported a failure. Message is safe to show to the user. */
class MatcherUnavailable extends RuntimeException {}
