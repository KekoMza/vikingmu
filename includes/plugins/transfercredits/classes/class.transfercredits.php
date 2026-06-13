<?php
/**
 * Transfer Credits
 * https://webenginecms.org/
 * 
 * @version 1.3.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2021 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

namespace Plugin\TransferCredits;

class TransferCredits {
	
	private $_configXml = 'config.xml';
	private $_modulesPath = 'modules';
	private $_emailsPath = 'emails';
	private $_sqlPath = 'sql';
	private $_sqlList = array(
		'WEBENGINE_TRANSFERCREDITS_LOGS',
	);
	
	private $_enableTransferTax = 0;
	private $_transferTax = 10;
	private $_allowedCreditConfigs = 0;
	private $_allowedCreditConfigsArray = null;
	private $_transferMinLimit = 10;
	private $_transferMaxLimit = 10000;
	private $_enableEmailVerification = 0;
	private $_ignoreReceiverOnlineStatus = 0;
	
	private $_user;
	private $_player;
	private $_configId;
	private $_transferAmount;
	
	private $_transferId;
	private $_transferKey;
	
	private $_usercpmenu = array(
		array(
			'active' => true,
			'type' => 'internal',
			'phrase' => 'transfercredits_title',
			'link' => 'transfercredits/transfer',
			'icon' => 'usercp_default.png',
			'visibility' => 'user',
			'newtab' => false,
			'order' => 999,
		),
	);
	
	// CONSTRUCTOR
	
	function __construct() {
		
		// load databases
		$this->common = new \common();
		$this->db = \Connection::Database('Me_MuOnline');
		
		// config file path
		$this->configFilePath = __PATH_TRANSFERCREDITS_ROOT__.$this->_configXml;
		if(!file_exists($this->configFilePath)) throw new \Exception(lang('transfercredits_error_2', true));
		$xml = simplexml_load_file($this->configFilePath);
		if(!$xml) throw new \Exception(lang('transfercredits_error_2', true));
		$this->_configs = convertXML($xml->children());
		if(!is_array($this->_configs)) throw new \Exception(lang('transfercredits_error_2', true));
		
		// set configs
		$this->_enableTransferTax = $this->_configs['enable_transfer_tax'];
		$this->_transferTax = $this->_configs['transfer_tax'];
		$this->_allowedCreditConfigs = $this->_configs['credit_configs'];
		$this->_transferMinLimit = $this->_configs['transfer_minimum_limit'];
		$this->_transferMaxLimit = $this->_configs['transfer_maximum_limit'];
		$this->_enableEmailVerification = $this->_configs['enable_email_verification'];
		$this->_ignoreReceiverOnlineStatus = $this->_configs['ignore_receiver_online_status'];
		
		// allowed credit configs
		$this->_buildAllowedCreditConfigsArray();
		
		// sql file path
		$this->sqlFilePath = __PATH_TRANSFERCREDITS_ROOT__.$this->_sqlPath.'/';
		
		$this->_checkTables();
	}
	
	// PUBLIC FUNCTIONS
	
	public function loadModule($module) {
		if(!\Validator::Alpha($module)) throw new \Exception(lang('transfercredits_error_4', true));
		if(!$this->_moduleExists($module)) throw new \Exception(lang('transfercredits_error_4', true));
		if(!@include_once(__PATH_TRANSFERCREDITS_ROOT__ . $this->_modulesPath . '/' . $module . '.php')) throw new \Exception(lang('transfercredits_error_4', true));
	}
	
	public function setUser($user) {
		$this->_user = $user;
	}
	
	public function setPlayer($player) {
		$this->_player = $player;
	}
	
	public function setCreditsConfigId($id) {
		if(!\Validator::UnsignedNumber($id)) return;
		$this->_configId = $id;
	}
	
	public function getAllowedCreditConfigs() {
		if(!is_array($this->_allowedCreditConfigsArray)) return;
		return $this->_allowedCreditConfigsArray;
	}
	
	public function getTransferTaxValue() {
		if($this->_enableTransferTax == 0) return 0;
		return $this->_transferTax;
	}
	
	public function setTransferAmount($amount) {
		if(!\Validator::UnsignedNumber($amount)) throw new \Exception(lang('transfercredits_error_7',true));
		if($amount < $this->_transferMinLimit) throw new \Exception(lang('transfercredits_error_7',true));
		if($amount > $this->_transferMaxLimit) throw new \Exception(lang('transfercredits_error_7',true));
		$this->_transferAmount = $amount;
	}
	
	public function calculateTransferTotal() {
		if(!check_value($this->_transferAmount)) return 0;
		if($this->_transferAmount == 0) return 0;
		
		$tax = $this->getTransferTaxValue();
		if($tax == 0) return $this->_transferAmount;
		
		$result = round($this->_transferAmount+($this->_transferAmount*($tax/100)));
		return $result;
	}
	
	public function transfer() {
		
		if(!check_value($this->_user)) throw new \Exception(lang('transfercredits_error_9',true));
		if(!check_value($this->_player)) throw new \Exception(lang('transfercredits_error_5',true));
		if(!check_value($this->_configId)) throw new \Exception(lang('transfercredits_error_6',true));
		if(!check_value($this->_transferAmount)) throw new \Exception(lang('transfercredits_error_7',true));
		
		// check credits id
		if(!array_key_exists($this->_configId, $this->_allowedCreditConfigsArray)) throw new \Exception(lang('transfercredits_error_6',true));
		$creditsName = $this->_allowedCreditConfigsArray[$this->_configId];
		
		// check player
		$Character = new \Character();
		$characterData = $Character->CharacterData($this->_player);
		if(!is_array($characterData)) throw new \Exception(lang('transfercredits_error_5',true));
		
		// check player account
		if($characterData[_CLMN_CHR_ACCID_] == $this->_user) throw new \Exception(lang('transfercredits_error_8',true));
		
		// player
		$player = $characterData[_CLMN_CHR_NAME_];
		
		// receiver account info
		$receiverAccountId = $this->common->retrieveUserID($characterData[_CLMN_CHR_ACCID_]);
		$receiverAccountInfo = $this->common->accountInformation($receiverAccountId);
		if(!is_array($receiverAccountInfo)) throw new \Exception(lang('transfercredits_error_9',true));
		
		// sender account info
		$accountId = $this->common->retrieveUserID($this->_user);
		if(!check_value($accountId)) throw new \Exception(lang('transfercredits_error_9',true));
		$accountInfo = $this->common->accountInformation($accountId);
		if(!is_array($accountInfo)) throw new \Exception(lang('transfercredits_error_9',true));
		
		// check account credits
		$creditSystem = new \CreditSystem();
		$creditSystem->setConfigId($this->_configId);
		$configInfo = $creditSystem->showConfigs(true);
		switch($configInfo['config_user_col_id']) {
			case 'userid':
				$creditSystem->setIdentifier($accountInfo[_CLMN_MEMBID_]);
				break;
			case 'username':
				$creditSystem->setIdentifier($accountInfo[_CLMN_USERNM_]);
				break;
			case 'email':
				$creditSystem->setIdentifier($accountInfo[_CLMN_EMAIL_]);
				break;
			default:
				throw new \Exception(lang('transfercredits_error_9',true));
		}
		
		// account credits
		$accountCredits = $creditSystem->getCredits();
		
		// check balance
		$transferTotal = $this->calculateTransferTotal();
		if($transferTotal > $accountCredits) throw new \Exception(lang('transfercredits_error_10',true));
		
		// sender character name
		$senderCharacterName = $this->_getFirstCharacterFromAccount();
		if(!check_value($senderCharacterName)) throw new \Exception(lang('transfercredits_error_9',true));
		
		// check receiver online status (only if verification is disabled)
		if($this->_enableEmailVerification == 0 && $this->_ignoreReceiverOnlineStatus == 0) {
			if($this->common->accountOnline($receiverAccountInfo[_CLMN_USERNM_])) throw new \Exception(lang('transfercredits_error_12'));
		}
		
		// subtract credits
		$creditSystem->subtractCredits($transferTotal);
		
		// add transfer request
		if($this->_enableEmailVerification) {
			
			$transferRequest = $this->db->query("INSERT INTO WEBENGINE_TRANSFERCREDITS_LOGS (amount, sent_by, sent_to, date_sent, credit_config, credits_title) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?)", array($this->_transferAmount, $accountInfo[_CLMN_USERNM_], $receiverAccountInfo[_CLMN_USERNM_], $this->_configId, $creditsName));
			if(!$transferRequest) throw new \Exception(lang('transfercredits_error_9',true));
			
			// last insert id
			$lastInsertId = $this->db->db->lastInsertId();
			
			try {
				
				$transferId = $lastInsertId;
				$key = $this->_generateKey($transferId, $receiverAccountInfo[_CLMN_USERNM_], $this->_transferAmount);
				$confirmLink = __BASE_URL__ . 'transfercredits/verify/id/'.$transferId.'/key/' . $key;
				
				$email = new \Email();
				$email->setSubject(lang('transfercredits_email_1_title'));
				$email->setCustomTemplate(__PATH_TRANSFERCREDITS_ROOT__ . $this->_emailsPath . '/PLUGIN_TRANSFERCREDITS.txt');
				$email->addVariable('{USERNAME}', $receiverAccountInfo[_CLMN_USERNM_]);
				$email->addVariable('{SENDER_PLAYER_NAME}', $senderCharacterName);
				$email->addVariable('{CREDITS_AMOUNT}', $this->_transferAmount);
				$email->addVariable('{CREDITS_NAME}', $creditsName);
				$email->addVariable('{CONFIRM_LINK}', $confirmLink);
				$email->addAddress($receiverAccountInfo[_CLMN_EMAIL_]);
				$email->send();
				
			} catch(\Exception $ex) {
				throw new \Exception(lang('transfercredits_error_9',true));
			}
			
		} else {
			
			// set receiver identifier
			switch($configInfo['config_user_col_id']) {
				case 'userid':
					$creditSystem->setIdentifier($receiverAccountInfo[_CLMN_MEMBID_]);
					break;
				case 'username':
					$creditSystem->setIdentifier($receiverAccountInfo[_CLMN_USERNM_]);
					break;
				case 'email':
					$creditSystem->setIdentifier($receiverAccountInfo[_CLMN_EMAIL_]);
					break;
				default:
					throw new \Exception(lang('transfercredits_error_9',true));
			}
			
			// send credits
			$creditSystem->addCredits($this->_transferAmount);
			
			// save transfer log
			$transferRequest = $this->db->query("INSERT INTO WEBENGINE_TRANSFERCREDITS_LOGS (amount, sent_by, sent_to, date_sent, credit_config, credits_title, is_received, date_received) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, CURRENT_TIMESTAMP)", array($this->_transferAmount, $accountInfo[_CLMN_USERNM_], $receiverAccountInfo[_CLMN_USERNM_], $this->_configId, $creditsName, 1));
			if(!$transferRequest) throw new \Exception(lang('transfercredits_error_9',true));
			
		}
		
		redirect(1,'transfercredits/send/success/1/');
		
	}
	
	public function setTransferId($id) {
		if(!\Validator::UnsignedNumber($id)) throw new \Exception();
		$this->_transferId = $id;
	}
	
	public function setTransferKey($key) {
		$this->_transferKey = $key;
	}
	
	public function verifyTransfer() {
		
		if(!check_value($this->_user)) throw new \Exception(lang('transfercredits_error_9',true));
		if(!check_value($this->_transferId)) throw new \Exception(lang('transfercredits_error_9',true));
		if(!check_value($this->_transferKey)) throw new \Exception(lang('transfercredits_error_9',true));
		
		// transfer data
		$transferData = $this->_getTransferDataFromId(lang('transfercredits_error_9',true));
		
		// check key
		$key = $this->_generateKey($this->_transferId, $this->_user, $transferData['amount']);
		if($this->_transferKey != $key) throw new \Exception(lang('transfercredits_error_9',true));
		
		// check if is receiver
		if($transferData['sent_to'] != $this->_user) throw new \Exception(lang('transfercredits_error_9',true));
		
		// account data
		$accountId = $this->common->retrieveUserID($transferData['sent_to']);
		if(!check_value($accountId)) throw new \Exception(lang('transfercredits_error_9',true));
		$accountInfo = $this->common->accountInformation($accountId);
		if(!is_array($accountInfo)) throw new \Exception(lang('transfercredits_error_9',true));
		
		// credit system
		$creditSystem = new \CreditSystem();
		$creditSystem->setConfigId($transferData['credit_config']);
		$configInfo = $creditSystem->showConfigs(true);
		switch($configInfo['config_user_col_id']) {
			case 'userid':
				$creditSystem->setIdentifier($accountInfo[_CLMN_MEMBID_]);
				break;
			case 'username':
				$creditSystem->setIdentifier($accountInfo[_CLMN_USERNM_]);
				break;
			case 'email':
				$creditSystem->setIdentifier($accountInfo[_CLMN_EMAIL_]);
				break;
			default:
				throw new \Exception(lang('transfercredits_error_9',true));
		}
		
		// add credits
		$creditSystem->addCredits($transferData['amount']);
		
		// complete transfer
		$this->_setTransferComplete();
		
		redirect(1,'transfercredits/verify/success/1/');
		
	}
	
	public function getPendingTransfers() {
		$result = $this->db->query_fetch("SELECT * FROM WEBENGINE_TRANSFERCREDITS_LOGS WHERE is_received = 0 ORDER BY id DESC");
		if(!is_array($result)) return;
		return $result;
	}
	
	public function getCompletedTransfers() {
		$result = $this->db->query_fetch("SELECT * FROM WEBENGINE_TRANSFERCREDITS_LOGS WHERE is_received = 1 ORDER BY id DESC");
		if(!is_array($result)) return;
		return $result;
	}
	
	public function checkPluginUsercpLinks() {
		if(!is_array($this->_usercpmenu)) return;
		$cfg = loadConfig('usercp');
		if(!is_array($cfg)) return;
		foreach($cfg as $usercpMenu) {
			$usercpLinks[] = $usercpMenu['link'];
		}
		foreach($this->_usercpmenu as $pluginMenuLink) {
			if(in_array($pluginMenuLink['link'],$usercpLinks)) continue;
			$cfg[] = $pluginMenuLink;
		}
		usort($cfg, function($a, $b) {
			return $a['order'] - $b['order'];
		});
		$usercpJson = json_encode($cfg, JSON_PRETTY_PRINT);
		$cfgFile = fopen(__PATH_CONFIGS__.'usercp.json', 'w');
		if(!$cfgFile) throw new \Exception('There was a problem opening the usercp file.');
		fwrite($cfgFile, $usercpJson);
		fclose($cfgFile);
	}
	
	// PRIVATE FUNCTIONS
	
	private function _moduleExists($module) {
		if(!check_value($module)) return;
		if(!file_exists(__PATH_TRANSFERCREDITS_ROOT__ . $this->_modulesPath . '/' . $module . '.php')) return;
		return true;
	}
	
	private function _checkTables() {
		if(!is_array($this->_sqlList)) return;
		foreach($this->_sqlList as $tableName) {
			$tableExists = $this->db->query_fetch_single("SELECT * FROM sysobjects WHERE xtype = 'U' AND name = ?", array($tableName));
			if($tableExists) continue;
			if(!$this->_createTable($tableName)) throw new \Exception(lang('transfercredits_error_3', true));
		}
	}
	
	private function _tableFileExists($name) {
		if(!file_exists($this->sqlFilePath.$name.'.txt')) return;
		return true;
	}
	
	private function _createTable($name) {
		if(!in_array($name, $this->_sqlList)) return;
		if(!$this->_tableFileExists($name)) return;
		$query = file_get_contents($this->sqlFilePath.$name.'.txt');
		if(!check_value($query)) return;
		if(!$this->db->query($query)) return;
		return true;
	}
	
	private function _buildAllowedCreditConfigsArray() {
		if($this->_allowedCreditConfigs == 0) return;
		
		$allowedConfigs = explode(",", $this->_allowedCreditConfigs);
		$allowedConfigsArray = array_filter($allowedConfigs);
		if(!is_array($allowedConfigsArray)) return;
		
		try {
			$creditSystem = new \CreditSystem();
			$creditCofigList = $creditSystem->showConfigs();
			if(is_array($creditCofigList)) {
				foreach($creditCofigList as $creditConfigInfo) {
					if(!in_array($creditConfigInfo['config_id'], $allowedConfigsArray)) continue;
					$result[$creditConfigInfo['config_id']] = $creditConfigInfo['config_title'];
				}
			}
		} catch(\Exception $ex) {
			return;
		}
		
		if(!is_array($result)) return;
		$this->_allowedCreditConfigsArray = $result;
	}
	
	private function _getFirstCharacterFromAccount() {
		if(!check_value($this->_user)) return;
		$Character = new \Character();
		$AccountCharacters = $Character->AccountCharacter($this->_user);
		if(!is_array($AccountCharacters)) throw new \Exception(lang('transfercredits_error_11',true));
		foreach($AccountCharacters as $characterName) {
			if(!check_value($characterName)) continue;
			return $characterName;
		}
	}
	
	private function _getTransferDataFromId() {
		if(!check_value($this->_transferId)) return;
		$result = $this->db->query_fetch_single("SELECT * FROM WEBENGINE_TRANSFERCREDITS_LOGS WHERE id = ? AND is_received = 0", array($this->_transferId));
		if(!is_array($result)) return;
		return $result;
	}
	
	private function _generateKey($transferId, $receiverUsername, $transferAmount) {
		return md5($transferId . $receiverUsername . $transferAmount);
	}
	
	private function _setTransferComplete() {
		if(!check_value($this->_transferId)) return;
		$result = $this->db->query("UPDATE WEBENGINE_TRANSFERCREDITS_LOGS SET is_received = 1, date_received = CURRENT_TIMESTAMP WHERE id = ?", array($this->_transferId));
		if(!$result) return;
		return true;
	}
	
}