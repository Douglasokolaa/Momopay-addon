<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Mtnpay
Description: Ericsondeveloperapi
Author: Techy4m
Author URI: https://codecanyon.net/user/techy4m
Version: 1.0.0
Requires at least: 2.6.*
*/
register_payment_gateway('mtnpay_gateway', 'mtnpay');
// register_language_files('mtnpay', ['mtnpay']);

/**
 * Generate UUID v4
 */
function mtnpay_generate_keys($trim = true)
{
    $lbrace = chr(123);    // "{"
    $rbrace = chr(125);    // "}"

    // Windows
    if (function_exists('com_create_guid') === true) {   // extension=php_com_dotnet.dll 
        if ($trim === true) {
            return trim(com_create_guid(), '{}');
        } else {
            return com_create_guid();
        }
    }


    // OSX/Linux and Windows with OpenSSL but without com classes loaded (extension=php_com_dotnet.dll loaded in php.ini)
    if (function_exists('openssl_random_pseudo_bytes') === true) {

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
        if ($trim === true) {
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } else {
            return $lbrace . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)) . $rbrace;
        }
    }

    // Fallback (PHP 4.2+)      
    mt_srand((float)microtime() * 10000);
    $charid = strtolower(md5(uniqid(rand(), true)));
    $hyphen = chr(45);                  // "-"
    $guidv4 = substr($charid,  0,  8) . $hyphen .
        substr($charid,  8,  4) . $hyphen .
        substr($charid, 12,  4) . $hyphen .
        substr($charid, 16,  4) . $hyphen .
        substr($charid, 20, 12);

    if ($trim === true) {
        return $guidv4;
    } else {
        return $lbrace . $guidv4 . $rbrace;
    }
}
