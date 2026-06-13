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
		
		message('success', lang('transfercredits_success_2',true));
		
	} else {
	
		if(!check_value($_GET['id'])) redirect();
		if(!check_value($_GET['key'])) redirect();
		
		$TransferCredits = new \Plugin\TransferCredits\TransferCredits();
		$TransferCredits->setUser($_SESSION['username']);
		$TransferCredits->setTransferId($_GET['id']);
		$TransferCredits->setTransferKey($_GET['key']);
		$TransferCredits->verifyTransfer();
	
	}
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}