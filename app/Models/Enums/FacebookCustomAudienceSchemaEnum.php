<?php

namespace App\Models\Enums;

/**
 * @method static Email()
 * @method static Phone()
 * @method static Gender()
 * @method static Year_Of_Birth()
 * @method static Month_Of_Birth()
 * @method static Day_Of_Birth()
 * @method static Date_Of_Birth()
 * @method static Firstname()
 * @method static Lastname()
 * @method static Firstname_Initial()
 * @method static State()
 * @method static City()
 * @method static Zip()
 * @method static Country()
 * @method static Mobile_Advertiser_ID()
 * @method static External_ID()
 * @method static Value()
 */
class FacebookCustomAudienceSchemaEnum extends Enumerate
{
    const Email = 'EMAIL';
    const Phone = 'PHONE';
    const Gender = 'Gender';
    const Year_Of_Birth = 'DOBY';
    const Month_Of_Birth = 'DOBM';
    const Day_Of_Birth = 'DOBD';
    const Date_Of_Birth = 'DOB';
    const Firstname = 'FN';
    const Lastname = 'LN';
    const Firstname_Initial = 'FI';
    const State = 'ST';
    const City = 'CT';
    const Zip = 'ZIP';
    const Country = 'COUNTRY';
    const Mobile_Advertiser_ID = 'MADID';
    const External_ID = 'EXTERN_ID';
    const Value = 'VALUE';
}
