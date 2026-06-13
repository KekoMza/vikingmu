<?php
/**
 * Transfer Credits
 * https://webenginecms.org/
 * 
 * @version 1.1.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2019 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

// namespace
namespace Plugin\TransferCredits;

// plugin root
define('__PATH_TRANSFERCREDITS_ROOT__', __PATH_PLUGINS__.'transfercredits/');

// plugin root
define('__TRANSFERCREDITS_HOME__', __BASE_URL__.'transfercredits/');

// admincp
$extra_admincp_sidebar[] = array(
    'Transfer Credits', array(
        array('Settings','transfercredits&page=settings'),
        array('Logs','transfercredits&page=logs')
    )
);

if(file_exists(__PATH_TRANSFERCREDITS_ROOT__ . 'languages/'.config('language_default', true).'/language.php')) {
	// attempt to load same language as website
	if(!@include_once(__PATH_TRANSFERCREDITS_ROOT__ . 'languages/'.config('language_default', true).'/language.php')) throw new Exception('Error loading transfer credits language file.');
} else {
	// load default language file (en)
	if(!@include_once(__PATH_TRANSFERCREDITS_ROOT__ . 'languages/en/language.php')) throw new Exception('Error loading transfer credits language file.');
}

// load classes
if(!@include_once(__PATH_TRANSFERCREDITS_ROOT__ . 'classes/class.transfercredits.php')) throw new Exception(lang('transfercredits_error_1', true));