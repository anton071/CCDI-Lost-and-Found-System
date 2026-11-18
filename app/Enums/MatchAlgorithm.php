<?php

namespace App\Enums;

enum MatchAlgorithm: string
{
    case Sift = 'SIFT';
    case Orb = 'ORB';
}
