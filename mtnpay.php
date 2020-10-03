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
register_language_files('mtnpay', ['mtnpay']);


