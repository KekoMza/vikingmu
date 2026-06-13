<?php
/**
 * Transfer Credits
 * https://webenginecms.org/
 * 
 * @version 1.0.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2019 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

try {
	
	$TransferCredits = new \Plugin\TransferCredits\TransferCredits();
	$TransferCredits->loadModule('send');
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}