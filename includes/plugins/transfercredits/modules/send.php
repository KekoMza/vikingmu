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

if(!isLoggedIn()) redirect(1,'login');

echo '<div class="page-title"><span>'.lang('transfercredits_title',true).'</span></div>';

try {
	
	if(check_value($_GET['success'])) {
		
		message('success', lang('transfercredits_success_1',true));
		
	} else {
	
		if(!check_value($_GET['player'])) redirect(1,'transfercredits/transfer/');
		if(!check_value($_GET['type'])) redirect(1,'transfercredits/transfer/');
		if(!check_value($_GET['credits'])) redirect(1,'transfercredits/transfer/');
		
		$TransferCredits = new \Plugin\TransferCredits\TransferCredits();
		$TransferCredits->setUser($_SESSION['username']);
		$TransferCredits->setPlayer($_GET['player']);
		$TransferCredits->setCreditsConfigId($_GET['type']);
		$TransferCredits->setTransferAmount($_GET['credits']);
		$TransferCredits->transfer();
	
	}
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}