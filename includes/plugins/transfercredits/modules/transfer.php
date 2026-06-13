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
	
	$TransferCredits = new \Plugin\TransferCredits\TransferCredits();
	$allowedCreditConfigs = $TransferCredits->getAllowedCreditConfigs();
	
	if(!check_value($_GET['player']) && !check_value($_GET['type']) && !check_value($_GET['credits'])) {
	
		if(check_value($_POST['transfer_submit'])) {
			try {
				
				if(!check_value($_POST['player_name'])) throw new Exception(lang('transfercredits_error_5',true));
				if(!Validator::AlphaNumeric($_POST['player_name'])) throw new Exception(lang('transfercredits_error_5',true));
				
				if(!check_value($_POST['credits_type'])) throw new Exception(lang('transfercredits_error_6',true));
				if(!Validator::UnsignedNumber($_POST['credits_type'])) throw new Exception(lang('transfercredits_error_6',true));
				
				if(!check_value($_POST['credits_amount'])) throw new Exception(lang('transfercredits_error_7',true));
				if(!Validator::UnsignedNumber($_POST['credits_amount'])) throw new Exception(lang('transfercredits_error_7',true));
				
				redirect(1,'transfercredits/transfer/player/'.$_POST['player_name'].'/type/'.$_POST['credits_type'].'/credits/'.$_POST['credits_amount'].'/');
				
			} catch(Exception $ex) {
				message('error', $ex->getMessage());
			}
		}
		
		echo '<div class="col-xs-8 col-xs-offset-2" style="margin-top:30px;">';
			echo '<form class="form-horizontal" action="" method="post">';
			
				echo '<div class="form-group">';
					echo '<label for="input1">'.lang('transfercredits_txt_4',true).'</label>';
					echo '<input type="text" name="player_name" class="form-control" id="input1">';
				echo '</div>';
				
				echo '<div class="form-group">';
					echo '<label for="input2">'.lang('transfercredits_txt_5',true).'</label>';
					echo '<select name="credits_type" class="form-control" id="input2">';
						echo '<option value="0">'.lang('transfercredits_txt_2',true).'</option>';
						if(is_array($allowedCreditConfigs)) {
							foreach($allowedCreditConfigs as $configId => $configTitle) {
								echo '<option value="'.$configId.'">'.$configTitle.'</option>';
							}
						}
					echo '</select>';
				echo '</div>';
				
				echo '<div class="form-group">';
					echo '<label for="input3">'.lang('transfercredits_txt_6',true).'</label>';
					echo '<input type="text" name="credits_amount" class="form-control" id="input3">';
				echo '</div>';
				
				
				echo '<div class="form-group">';
					echo '<button type="submit" name="transfer_submit" value="submit" class="btn btn-primary pull-right">'.lang('transfercredits_txt_1',true).'</button>';
				echo '</div>';
			echo '</form>';
		echo '</div>';
	
	} else {
		
		// player
		$playerName = $_GET['player'];
		
		// credits type
		$creditsType = $_GET['type'];
		if(!array_key_exists($creditsType, $allowedCreditConfigs)) throw new Exception(lang('transfercredits_error_6',true));
		$creditsTitle = $allowedCreditConfigs[$creditsType];
		
		// amount
		$creditsAmount = $_GET['credits'];
		$TransferCredits->setTransferAmount($creditsAmount);
		
		// tax
		$transferTax = $TransferCredits->getTransferTaxValue();
		
		// total
		$creditsTotal = $TransferCredits->calculateTransferTotal();
		
		
		echo '<div class="col-xs-8 col-xs-offset-2" style="margin-top:30px;">';
			echo '<table class="table table-bordered table-hover table-striped">';
				echo '<tr>';
					echo '<th class="text-right" style="width:35%;">'.lang('transfercredits_txt_4',true).'</th>';
					echo '<td class="text-right">'.$playerName.'</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th class="text-right">'.lang('transfercredits_txt_5',true).'</th>';
					echo '<td class="text-right">'.$creditsTitle.'</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th class="text-right">'.lang('transfercredits_txt_6',true).'</th>';
					echo '<td class="text-right">'.number_format($creditsAmount).'</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th class="text-right">'.lang('transfercredits_txt_7',true).'</th>';
					echo '<td class="text-right">'.number_format($transferTax).'%</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th class="text-right" style="color:red;">'.lang('transfercredits_txt_8',true).'</th>';
					echo '<td class="text-right" style="color:red;">'.number_format($creditsTotal).'</td>';
				echo '</tr>';
			echo '</table>';
			
			echo '<a href="'.__BASE_URL__.'transfercredits/send/player/'.$playerName.'/type/'.$creditsType.'/credits/'.$creditsAmount.'/" class="btn btn-primary pull-right">'.lang('transfercredits_txt_3',true).'</a>';
		echo '</div>';
		
	}
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}