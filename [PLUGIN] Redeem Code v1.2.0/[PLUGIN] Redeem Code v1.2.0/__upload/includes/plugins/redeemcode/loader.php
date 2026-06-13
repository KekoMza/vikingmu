<?php
/**
 * Redeem Code
 * https://webenginecms.org/
 * 
 * @version 1.2.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2020 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

// namespace
namespace Plugin\RedeemCode;

// plugin root
define('__PATH_REDEEMCODE_ROOT__', __PATH_PLUGINS__.'redeemcode/');

// plugin root
define('__REDEEMCODE_HOME__', __BASE_URL__.'redeem/');

// admincp
$extra_admincp_sidebar[] = array(
    'Redeem Code', array(
        array('Settings','redeem&page=settings'),
        array('Create','redeem&page=create'),
        array('List','redeem&page=list'),
        array('Logs','redeem&page=logs')
    )
);

if(file_exists(__PATH_REDEEMCODE_ROOT__ . 'languages/'.config('language_default', true).'/language.php')) {
	// attempt to load same language as website
	if(!@include_once(__PATH_REDEEMCODE_ROOT__ . 'languages/'.config('language_default', true).'/language.php')) throw new Exception('Error loading redeem code language file.');
} else {
	// load default language file (en)
	if(!@include_once(__PATH_REDEEMCODE_ROOT__ . 'languages/en/language.php')) throw new Exception('Error loading redeem code language file.');
}

// load classes
if(!@include_once(__PATH_REDEEMCODE_ROOT__ . 'classes/class.redeemcode.php')) throw new Exception(lang('redeemcode_error_1', true));