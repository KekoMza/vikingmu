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

try {
	
	if(!class_exists('Plugin\RedeemCode\RedeemCode')) throw new Exception('Plugin disabled.');
	$RedeemCode = new \Plugin\RedeemCode\RedeemCode();
	$RedeemCode->loadModule('code');
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}