<?php

namespace App\Models\Enums;

/**
 * @method static HIGH_SCHOOL()
 * @method static UNDERGRAD()
 * @method static ALUM()
 * @method static HIGH_SCHOOL_GRAD()
 * @method static SOME_COLLEGE()
 * @method static ASSOCIATE_DEGREE()
 * @method static IN_GRAD_SCHOOL()
 * @method static SOME_GRAD_SCHOOL()
 * @method static MASTER_DEGREE()
 * @method static PROFESSIONAL_DEGREE()
 * @method static DOCTORATE_DEGREE()
 * @method static UNSPECIFIED()
 * @method static SOME_HIGH_SCHOOL()
 */
class FacebookEducationalStatusEnum extends Enumerate
{
    const HIGH_SCHOOL = 1;
    const UNDERGRAD = 2;
    const ALUM = 3;
    const HIGH_SCHOOL_GRAD = 4;
    const SOME_COLLEGE = 5;
    const ASSOCIATE_DEGREE = 6;
    const IN_GRAD_SCHOOL = 7;
    const SOME_GRAD_SCHOOL = 8;
    const MASTER_DEGREE = 9;
    const PROFESSIONAL_DEGREE = 10;
    const DOCTORATE_DEGREE = 11;
    const UNSPECIFIED = 12;
    const SOME_HIGH_SCHOOL = 13;
}
