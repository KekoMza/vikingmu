<?php
/**
 * Redeem Code
 * https://webenginecms.org/
 * 
 * @version 1.0.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2018 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

if(!isLoggedIn()) redirect(1,'login');

echo '<div class="page-title"><span>'.lang('redeemcode_title',true).'</span></div>';

try {
	
	if(check_value($_POST['redeem_submit'], $_POST['redeem_code'])) {
		try {
			
			$RedeemCode = new \Plugin\RedeemCode\RedeemCode();
			$RedeemCode->setCode($_POST['redeem_code']);
			$RedeemCode->redeemCode();
			
			message('success', lang('redeemcode_success_1',true));
			
		} catch(Exception $ex) {
			message('error', $ex->getMessage());
		}
	}
	
	echo '<div class="col-xs-8 col-xs-offset-2" style="margin-top:30px;">';
		echo '<form class="form-horizontal" action="" method="post">';
			echo '<div class="form-group">';
				echo '<div class="col-sm-12">';
					echo '<input type="text" class="form-control" name="redeem_code" required>';
				echo '</div>';
			echo '</div>';
			echo '<div class="form-group">';
				echo '<div class="col-sm-12">';
					echo '<button type="submit" name="redeem_submit" value="submit" class="btn btn-primary">'.lang('redeemcode_txt_1',true).'</button>';
				echo '</div>';
			echo '</div>';
		echo '</form>';
	echo '</div>';
	
	
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}