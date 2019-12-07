<?php

class Yr3kBaseEncoder
{
    /**
     * Encode string to base64.
     *
     * @param $string
     *
     * @return string
     */
    public static function encode($string)
    {
        return strrev(base64_encode($string));
    }

    /**
     * Decode string from base64.
     *
     * @param $data
     *
     * @return array
     */
    public static function decode($data)
    {
        $file = base64_decode(strrev($data));

        return explode('||', $file);
    }
}
