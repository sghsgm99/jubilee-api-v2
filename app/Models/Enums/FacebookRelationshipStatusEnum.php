<?php

namespace App\Models\Enums;

/**
 * @method static SINGLE()
 * @method static IN_RELATIONSHIP()
 * @method static MARRIED()
 * @method static ENGAGED()
 * @method static NOT_SPECIFIED()
 * @method static IN_A_CIVIL_UNION()
 * @method static IN_A_DOMESTIC_PARTNERSHIP()
 * @method static IN_AN_OPEN_RELATIONSHIP()
 * @method static ITS_COMPLICATED()
 * @method static SEPARETED()
 * @method static DIVORCED()
 * @method static WIDOWED()
 */
class FacebookRelationshipStatusEnum extends Enumerate
{
    const SINGLE = 1;
    const IN_RELATIONSHIP = 2;
    const MARRIED = 3;
    const ENGAGED = 4;
    const NOT_SPECIFIED = 6;
    const IN_A_CIVIL_UNION = 7;
    const IN_A_DOMESTIC_PARTNERSHIP = 8;
    const IN_AN_OPEN_RELATIONSHIP = 9;
    const ITS_COMPLICATED = 10;
    const SEPARETED = 11;
    const DIVORCED = 12;
    const WIDOWED = 13;
}
