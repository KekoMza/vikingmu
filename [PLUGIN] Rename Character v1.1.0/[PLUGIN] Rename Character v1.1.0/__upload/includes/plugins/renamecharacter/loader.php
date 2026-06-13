<?php
/**
 * Rename Character
 * https://webenginecms.org/
 * 
 * @version 1.0.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2019 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

// namespace
namespace Plugin\RenameCharacter;

// plugin root
define('__PATH_RENAMECHARACTER_ROOT__', __PATH_PLUGINS__.'renamecharacter/');

// plugin home url
define('__RENAMECHARACTER_HOME__', __BASE_URL__.'usercp/renamecharacter/');

// admincp
$extra_admincp_sidebar[] = array(
    'Rename Character', array(
        array('Settings','renamecharacter&page=settings'),
    )
);

// language phrases
if(file_exists(__PATH_RENAMECHARACTER_ROOT__ . 'languages/'.config('language_default', true).'/language.php')) {
	// attempt to load same language as website
	if(!@include_once(__PATH_RENAMECHARACTER_ROOT__ . 'languages/'.config('language_default', true).'/language.php')) throw new Exception('Error loading language file (myplugin).');
} else {
	// load default language file (en)
	if(!@include_once(__PATH_RENAMECHARACTER_ROOT__ . 'languages/en/language.php')) throw new Exception('Error loading language file (renamecharacter).');
}

// load classes
if(!@include_once(__PATH_RENAMECHARACTER_ROOT__ . 'classes/class.renamecharacter.php')) throw new Exception(lang('renamecharacter_error_1'));