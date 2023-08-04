<?php


namespace Model\Checkout;

use Model\Checkout\Checkout;

class Helper
{
    /**
     * @var array
     */
    protected static array $checkoutsTypes = [
        Checkout::INIT_PRO => 'ИнитПро'
    ];

    /**
     * @return array|string[]
     */
    public static function getCheckoutsTypes() : array
    {
        return self::$checkoutsTypes;
    }

    /**
     * @return array
     */
    public static function getCheckoutTypesListStd() : array
    {
        $output = [];
        foreach (self::$checkoutsTypes as $type => $name) {
            $stdType = new \stdClass();
            $stdType->type = $type;
            $stdType->name = $name;
            $output[] = $stdType;
        }
        return $output;
    }
}