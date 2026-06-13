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

namespace Plugin\RedeemCode;

class RedeemCode {
	
	private $_codesTable = WE_PREFIX . 'WEBENGINE_REDEEMCODE';
	private $_logsTable = WE_PREFIX . 'WEBENGINE_REDEEMCODE_LOGS';
	
	private $_codeMaxLen = 50;
	private $_codeTypes = array(
		'regular',
		'limited',
		'account'
	);
	
	private $_configXml = 'config.xml';
	private $_modulesPath = 'modules';
	private $_sqlPath = 'sql';
	private $_sqlList;
	
	private $_id;
	private $_code;
	private $_codeType;
	private $_limit = null;
	private $_user = null;
	private $_configId;
	private $_reward;
	
	private $_checkIp;
	private $_requiredLevel;
	private $_requiredResets;
	
	private $_usercpmenu = array(
		array(
			'active' => true,
			'type' => 'internal',
			'phrase' => 'redeemcode_title',
			'link' => 'redeem/code',
			'icon' => 'usercp_default.png',
			'visibility' => 'user',
			'newtab' => false,
			'order' => 999,
		),
	);
	
	// codes:
	// 		regular: can be used by everyone, once
	//		limited: can be used N amount of times
	//		account: can be used by one account, once
	
	// CONSTRUCTOR
	
	function __construct() {
		// load databases
		$this->db = \Connection::Database('Me_MuOnline');
		
		// sql tables
		$this->_sqlList = array(
			'WEBENGINE_REDEEMCODE' => $this->_codesTable,
			'WEBENGINE_REDEEMCODE_LOGS' => $this->_logsTable
		);
		
		// sql file path
		$this->sqlFilePath = __PATH_REDEEMCODE_ROOT__.$this->_sqlPath.'/';
		
		// load configs
		$this->configFilePath = __PATH_REDEEMCODE_ROOT__.$this->_configXml;
		if(!file_exists($this->configFilePath)) throw new \Exception(lang('redeemcode_error_2'));
		$xml = simplexml_load_file($this->configFilePath);
		if(!$xml) throw new \Exception(lang('redeemcode_error_2'));
		
		// set configs	
		$this->_checkIp = (int) $xml->check_ip;
		$this->_requiredLevel = (int) $xml->required_level;
		$this->_requiredResets = (int) $xml->required_resets;
		
		// check tables
		$this->_checkTables();
	}
	
	// PUBLIC FUNCTIONS
	
	public function loadModule($module) {
		if(!\Validator::Alpha($module)) throw new \Exception(lang('redeemcode_error_4', true));
		if(!$this->_moduleExists($module)) throw new \Exception(lang('redeemcode_error_4', true));
		if(!@include_once(__PATH_REDEEMCODE_ROOT__ . $this->_modulesPath . '/' . $module . '.php')) throw new \Exception(lang('redeemcode_error_4', true));
	}
	
	public function setId($id) {
		$this->_id = $id;
	}
	
	public function setCode($code) {
		if(strlen($code) > $this->_codeMaxLen) throw new \Exception(lang('redeemcode_error_5', true));
		$this->_code = $code;
	}
	
	public function setCodeType($type) {
		if(!in_array(strtolower($type), $this->_codeTypes)) throw new \Exception(lang('redeemcode_error_6', true));
		$this->_codeType = strtolower($type);
	}
	
	public function setLimit($limit) {
		if(!\Validator::UnsignedNumber($limit)) return;
		$this->_limit = $limit;
	}
	
	public function setUser($user) {
		$this->_user = $user;
	}
	
	public function setCreditsConfigId($id) {
		if(!\Validator::UnsignedNumber($id)) return;
		$this->_configId = $id;
	}
	
	public function setReward($reward) {
		if(!\Validator::UnsignedNumber($reward)) return;
		$this->_reward = $reward;
	}
	
	public function addRewardCode() {
		if(!check_value($this->_code)) throw new \Exception(lang('redeemcode_error_7', true));
		if(!check_value($this->_codeType)) throw new \Exception(lang('redeemcode_error_7', true));
		
		if($this->_codeType == 'limited') {
			if(!check_value($this->_limit)) throw new \Exception(lang('redeemcode_error_7', true));
		} else {
			$this->_limit = null;
		}
		
		if($this->_codeType == 'account') {
			if(!check_value($this->_user)) throw new \Exception(lang('redeemcode_error_7', true));
		} else {
			$this->_user = null;
		}
		
		if(!check_value($this->_configId)) throw new \Exception(lang('redeemcode_error_7', true));
		if(!check_value($this->_reward)) throw new \Exception(lang('redeemcode_error_7', true));
		
		if($this->_codeExists($this->_code)) throw new \Exception(lang('redeemcode_error_8', true));
		
		$data = array(
			$this->_code,
			$this->_codeType,
			$this->_limit,
			$this->_user,
			$this->_configId,
			$this->_reward
		);
		
		$result = $this->db->query("INSERT INTO ".$this->_codesTable." (redeem_code, redeem_type, redeem_limit, redeem_user, redeem_credit_config_id, redeem_credit_amount) VALUES (?, ?, ?, ?, ?, ?)", $data);
		if(!$result) throw new \Exception('Could not add code.');
	}
	
	public function redeemCode() {
		if(!check_value($this->_code)) throw new \Exception(lang('redeemcode_error_9', true));
		
		// code data
		$redeemCodeData = $this->_getRedeemCodeData();
		if(!is_array($redeemCodeData)) throw new \Exception(lang('redeemcode_error_9', true));
		
		// code status
		if($redeemCodeData['status'] == 1) throw new \Exception(lang('redeemcode_error_10', true));
		
		// credits config
		$creditSystem = new \CreditSystem();
		$creditSystem->setConfigId($redeemCodeData['redeem_credit_config_id']);
		$configSettings = $creditSystem->showConfigs(true);
		switch($configSettings['config_user_col_id']) {
			case 'userid':
				$userIdentifierValue = $_SESSION['userid'];
				break;
			case 'username':
				$userIdentifierValue = $_SESSION['username'];
				break;
			default:
				throw new \Exception(lang('redeemcode_error_9', true));
		}
		
		// check if user has already redeemed the code
		if($this->_hasUserRedeemedCode($redeemCodeData['id'], $userIdentifierValue)) throw new \Exception(lang('redeemcode_error_11'));
		
		// check if ip address has already redeemed the code
		if($this->_checkIp) {
			if($this->_hasIpRedeemedCode($redeemCodeData['id'], $_SERVER['REMOTE_ADDR'])) throw new \Exception(lang('redeemcode_error_11'));
		}
		
		// level requirement
		if($this->_requiredLevel > 0) {
			if(!$this->_meetsLevelRequirement()) throw new \Exception(langf('redeemcode_error_13', array($this->_requiredLevel)));
		}
		
		// resets requirement
		if($this->_requiredResets > 0) {
			if(!$this->_meetsResetsRequirement()) throw new \Exception(langf('redeemcode_error_14', array($this->_requiredResets)));
		}
		
		// code type
		switch($redeemCodeData['redeem_type']) {
			case 'regular':
				
				// reward user
				$creditSystem->setIdentifier($userIdentifierValue);
				$creditSystem->addCredits($redeemCodeData['redeem_credit_amount']);
				
				// add log
				$this->_addRedeemLog($redeemCodeData['id'], $userIdentifierValue);
				
				break;
			case 'limited':
			
				// get redeem count
				$redeemCount = $this->_getCodeRedeemCount($redeemCodeData['id']);
				
				// check redeem limit
				if(!check_value($redeemCodeData['redeem_limit'])) throw new \Exception(lang('redeemcode_error_9', true));
				if($redeemCount >= $redeemCodeData['redeem_limit']) {
					$this->_disableRedeemCode($redeemCodeData['id']);
					throw new \Exception(lang('redeemcode_error_10', true));
				}
				
				// reward user
				$creditSystem->setIdentifier($userIdentifierValue);
				$creditSystem->addCredits($redeemCodeData['redeem_credit_amount']);
				
				// add log
				$this->_addRedeemLog($redeemCodeData['id'], $userIdentifierValue);
				
				break;
			case 'account':
				
				// check user identifier
				if($redeemCodeData['redeem_user'] != $userIdentifierValue) throw new \Exception(lang('redeemcode_error_12', true));
				
				// reward user
				$creditSystem->setIdentifier($userIdentifierValue);
				$creditSystem->addCredits($redeemCodeData['redeem_credit_amount']);
				
				// add log
				$this->_addRedeemLog($redeemCodeData['id'], $userIdentifierValue);
				
				// disable code
				$this->_disableRedeemCode($redeemCodeData['id']);
				
				break;
			default:
				throw new \Exception(lang('redeemcode_error_9', true));
		}
		
	}
	
	public function getRedeemCodesList() {
		$result = $this->db->query_fetch("SELECT * FROM ".$this->_codesTable." ORDER BY id DESC");
		if(!is_array($result)) return;
		return $result;
	}
	
	public function disableCode($id) {
		$this->_disableRedeemCode($id);
	}
	
	public function getLogs() {
		if(!check_value($this->_id)) {
			$result = $this->db->query_fetch("SELECT * FROM ".$this->_logsTable." ORDER BY id DESC");
		} else {
			$result = $this->db->query_fetch("SELECT * FROM ".$this->_logsTable." WHERE code_id = ? ORDER BY id DESC", array($this->_id));
		}
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
	
	private function _getRedeemCodeData() {
		if(!check_value($this->_code)) return;
		$result = $this->db->query_fetch_single("SELECT * FROM ".$this->_codesTable." WHERE redeem_code = ?", array($this->_code));
		if(!is_array($result)) return;
		return $result;
	}
	
	private function _hasUserRedeemedCode($codeId, $userIdentifier) {
		$result = $this->db->query_fetch_single("SELECT * FROM ".$this->_logsTable." WHERE code_id = ? AND user_identifier = ?", array($codeId, $userIdentifier));
		if(!is_array($result)) return;
		return true;
	}
	
	private function _hasIpRedeemedCode($codeId, $ipAddress) {
		$result = $this->db->query_fetch_single("SELECT * FROM ".$this->_logsTable." WHERE code_id = ? AND ip_address = ?", array($codeId, $ipAddress));
		if(!is_array($result)) return;
		return true;
	}
	
	private function _addRedeemLog($codeId, $userIdentifier) {
		$result = $this->db->query("INSERT INTO ".$this->_logsTable." (code_id, date_redeemed, ip_address, user_identifier) VALUES (?, CURRENT_TIMESTAMP, ?, ?)", array($codeId, $_SERVER['REMOTE_ADDR'], $userIdentifier));
		if(!$result) return;
		return true;
	}
	
	private function _getCodeRedeemCount($codeId) {
		$result = $this->db->query_fetch_single("SELECT COUNT(*) as result FROM ".$this->_logsTable." WHERE code_id = ?", array($codeId));
		if(!is_array($result)) return 0;
		return $result['result'];
	}
	
	private function _disableRedeemCode($codeId) {
		$result = $this->db->query("UPDATE ".$this->_codesTable." SET status = 1 WHERE id = ?", array($codeId));
		if(!$result) return;
		return true;
	}
	
	private function _codeExists($code) {
		$result = $this->db->query_fetch_single("SELECT * FROM ".$this->_codesTable." WHERE redeem_code = ?", array($code));
		if(!is_array($result)) return;
		return true;
	}
	
	private function _moduleExists($module) {
		if(!check_value($module)) return;
		if(!file_exists(__PATH_REDEEMCODE_ROOT__ . $this->_modulesPath . '/' . $module . '.php')) return;
		return true;
	}
	
	private function _checkTables() {
		if(!is_array($this->_sqlList)) return;
		foreach($this->_sqlList as $sqlFile => $tableName) {
			$tableExists = $this->db->query_fetch_single("SELECT * FROM sysobjects WHERE xtype = 'U' AND name = ?", array($tableName));
			if($tableExists) continue;
			if(!$this->_createTable($tableName, $sqlFile)) throw new \Exception(lang('redeemcode_error_3', true));
		}
	}
	
	private function _createTable($tableName, $sqlFile) {
		if(!array_key_exists($sqlFile, $this->_sqlList)) return;
		if(!file_exists($this->sqlFilePath.$sqlFile.'.txt')) return;
		$query = file_get_contents($this->sqlFilePath.$sqlFile.'.txt');
		if(!check_value($query)) return;
		$queryFinal = str_replace('{TABLE_NAME}', $tableName, $query);
		if(!$queryFinal) return;
		if(!$this->db->query($queryFinal)) return;
		return true;
	}
	
	private function _meetsLevelRequirement() {
		$Character = new \Character();
		$accountCharacters = $Character->AccountCharacter($_SESSION['username']);
		if(!is_array($accountCharacters)) return;
		foreach($accountCharacters as $characterName) {
			$characterData = $Character->CharacterData($characterName);
			if(!is_array($characterData)) continue;
			if($characterData[_CLMN_CHR_LVL_] >= $this->_requiredLevel) return true;
		}
		return;
	}
	
	private function _meetsResetsRequirement() {
		$Character = new \Character();
		$accountCharacters = $Character->AccountCharacter($_SESSION['username']);
		if(!is_array($accountCharacters)) return;
		foreach($accountCharacters as $characterName) {
			$characterData = $Character->CharacterData($characterName);
			if(!is_array($characterData)) continue;
			if($characterData[_CLMN_CHR_RSTS_] >= $this->_requiredResets) return true;
		}
		return;
	}
	
}