<?php
/**
 * Rename Character
 * https://webenginecms.org/
 * 
 * @version 1.1.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2021 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

namespace Plugin\RenameCharacter;

class RenameCharacter {
	
	private $_configXml = 'config.xml';
	private $_modulesPath = 'modules';
	private $_serverFiles;
	
	private $_cost;
	private $_configId;
	private $_newNameMinLen = 3;
	private $_newNameMaxLen = 10;
	
	private $_userid;
	private $_username;
	private $_character;
	private $_newName;
	
	private $_usercpmenu = array(
		array(
			'active' => true,
			'type' => 'internal',
			'phrase' => 'renamecharacter_title',
			'link' => 'usercp/renamecharacter',
			'icon' => 'usercp_default.png',
			'visibility' => 'user',
			'newtab' => false,
			'order' => 999,
		),
	);
	
	// CONSTRUCTOR
	
	function __construct() {
		
		// webengine configs
		$this->config = webengineConfigs();
		$this->_serverFiles = strtolower($this->config['server_files']);
		
		// load databases
		$this->common = new \common();
		$this->mu = \Connection::Database('MuOnline');
		$this->me = \Connection::Database('Me_MuOnline');
		
		// load configs
		$this->configFilePath = __PATH_RENAMECHARACTER_ROOT__.$this->_configXml;
		if(!file_exists($this->configFilePath)) throw new \Exception(lang('renamecharacter_error_2'));
		$xml = simplexml_load_file($this->configFilePath);
		if(!$xml) throw new \Exception(lang('renamecharacter_error_2'));
		
		// set configs
		$this->_cost = $xml->rename_cost;
		$this->_configId = $xml->config_id;
		$this->_newNameMinLen = $xml->min_len;
		$this->_newNameMaxLen = $xml->max_len;
		
	}
	
	// PUBLIC FUNCTIONS
	
	public function loadModule($module) {
		if(!\Validator::Alpha($module)) throw new \Exception(lang('renamecharacter_error_3'));
		if(!$this->_moduleExists($module)) throw new \Exception(lang('renamecharacter_error_3'));
		if(!@include_once(__PATH_RENAMECHARACTER_ROOT__ . $this->_modulesPath . '/' . $module . '.php')) throw new \Exception(lang('renamecharacter_error_3'));
	}
	
	public function setUserid($userid) {
		$this->_userid = $userid;
	}
	
	public function setUsername($username) {
		if(!\Validator::UsernameLength($username)) throw new \Exception(lang('renamecharacter_error_10'));
		$this->_username = $username;
	}
	
	public function setCharacter($character) {
		$this->_character = $character;
	}
	
	public function setNewCharacterName($name) {
		if(!\Validator::AlphaNumeric($name)) throw new \Exception(lang('renamecharacter_error_14'));
		$this->_newName = $name;
	}
	
	public function getCost() {
		return $this->_cost;
	}
	
	public function getMinLen() {
		return $this->_newNameMinLen;
	}
	
	public function getMaxLen() {
		return $this->_newNameMaxLen;
	}
	
	public function getAccountCharacterList() {
		if(!check_value($this->_username)) throw new \Exception(lang('renamecharacter_error_4'));
		$Character = new \Character();
		$AccountCharacters = $Character->AccountCharacter($this->_username);
		if(!is_array($AccountCharacters)) throw new \Exception(lang('renamecharacter_error_5'));
		return $AccountCharacters;
	}
	
	public function changeName() {
		if($this->_configId == 0) throw new \Exception(lang('renamecharacter_error_15'));
		if(!check_value($this->_userid)) throw new \Exception(lang('renamecharacter_error_7'));
		if(!check_value($this->_username)) throw new \Exception(lang('renamecharacter_error_7'));
		if(!check_value($this->_character)) throw new \Exception(lang('renamecharacter_error_7'));
		if(!check_value($this->_newName)) throw new \Exception(lang('renamecharacter_error_7'));
		if(!$this->_checkLength($this->_newName)) throw new \Exception(lang('renamecharacter_error_8'));
		if($this->_characterNameExists($this->_newName)) throw new \Exception(lang('renamecharacter_error_9'));
		
		// account info
		$Account = new \Account();
		$accountInfo = $Account->accountInformation($this->_userid);
		if(!is_array($accountInfo)) throw new \Exception(lang('renamecharacter_error_11'));
		
		// account online status
		if($Account->accountOnline($this->_username)) throw new \Exception(lang('renamecharacter_error_12'));
		
		// check credits
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
			case 'character':
				$creditSystem->setIdentifier($this->_character);
				break;
			default:
				throw new \Exception(lang('renamecharacter_error_11'));
		}
		
		// check cost
		if($this->_cost > $creditSystem->getCredits()) throw new \Exception(lang('renamecharacter_error_13'));
		
		// subtract credits
		$creditSystem->subtractCredits($this->_cost);
		
		// Rename Process
		switch($this->_serverFiles) {
			case 'igcn':
				
				$this->_renameProcess_Character();
				$this->_renameProcess_AccountCharacter();
				$this->_renameProcess_Guild();
				$this->_renameProcess_GuildMember();
				
				$this->_renameProcess_CMonsterKillCount();
				$this->_renameProcess_CPlayerKillerInfo();
				$this->_renameProcess_IGCN_ArcaBattle();
				$this->_renameProcess_IGCN_BlockChat();
				$this->_renameProcess_IGCN_ClassQuestMonsterKill();
				$this->_renameProcess_IGCN_DuelLog();
				$this->_renameProcess_IGCN_EventMapEnterLimit();
				$this->_renameProcess_IGCN_EvolutionMonster();
				$this->_renameProcess_IGCN_FavouriteWarpData();
				$this->_renameProcess_IGCN_FriendChatMessageLog();
				$this->_renameProcess_IGCN_Gens();
				$this->_renameProcess_IGCN_GensAbuse();
				$this->_renameProcess_IGCN_GremoryCase();
				$this->_renameProcess_IGCN_GuildMatching();
				$this->_renameProcess_IGCN_HuntingRecord();
				$this->_renameProcess_IGCN_HuntingRecordOption();
				$this->_renameProcess_IGCN_Labyrinth();
				$this->_renameProcess_IGCN_Muun();
				$this->_renameProcess_IGCN_PartyMatching();
				$this->_renameProcess_IGCN_PentagramInfo();
				$this->_renameProcess_IGCN_PeriodBuffInfo();
				$this->_renameProcess_IGCN_PeriodItemInfo();
				$this->_renameProcess_IGCN_RestoreItemInventory();
				$this->_renameProcess_IGCN_WaitGuildMatching();
				$this->_renameProcess_IGCN_WaitPartyMatching();
				$this->_renameProcess_OptionData();
				$this->_renameProcess_TBombGameScore();
				$this->_renameProcess_TCGuid();
				$this->_renameProcess_TEventInventory();
				$this->_renameProcess_TFriendList();
				$this->_renameProcess_TFriendMail();
				$this->_renameProcess_TFriendMain();
				$this->_renameProcess_TGuideQuestInfo();
				$this->_renameProcess_TLuckyItemInfo();
				$this->_renameProcess_TMineSystem();
				$this->_renameProcess_TMuRummy();
				$this->_renameProcess_TPShopItemValueInfo();
				$this->_renameProcess_TQuestExpInfo();
				$this->_renameProcess_TWaitFriend();
				
				break;
			default:
				
				// xteam muemu louis
				
				$this->_renameProcess_Character();
				$this->_renameProcess_AccountCharacter();
				$this->_renameProcess_Guild();
				$this->_renameProcess_GuildMember();
				
				$this->_renameProcess_XTEAM_ArcaBattleGuildMember();
				$this->_renameProcess_XTEAM_ChatBlockData();
				$this->_renameProcess_XTEAM_EventEntryCount();
				$this->_renameProcess_XTEAM_EventInventory();
				$this->_renameProcess_XTEAM_EventLeoTheHelper();
				$this->_renameProcess_XTEAM_EventSantaClaus();
				$this->_renameProcess_XTEAM_FavoriteMoveList();
				$this->_renameProcess_XTEAM_Gens_Left();
				$this->_renameProcess_XTEAM_Gens_Rank();
				$this->_renameProcess_XTEAM_Gens_Reward();
				$this->_renameProcess_XTEAM_GremoryCaseLocal();
				$this->_renameProcess_XTEAM_HelperData();
				$this->_renameProcess_XTEAM_HuntingLog();
				$this->_renameProcess_XTEAM_ItemBuyBack();
				$this->_renameProcess_XTEAM_LabyrinthInfo();
				$this->_renameProcess_XTEAM_LabyrinthMissionInfo();
				$this->_renameProcess_XTEAM_MasterSkillTree();
				$this->_renameProcess_XTEAM_MasterSkillTreeExt();
				$this->_renameProcess_XTEAM_MuunInventory();
				$this->_renameProcess_XTEAM_PentagramJewel();
				$this->_renameProcess_XTEAM_PShopItemValue();
				$this->_renameProcess_XTEAM_QuestGuide();
				$this->_renameProcess_XTEAM_QuestKillCount();
				$this->_renameProcess_XTEAM_QuestWorld();
				$this->_renameProcess_XTEAM_RankingBloodCastle();
				$this->_renameProcess_XTEAM_RankingCastleSiege();
				$this->_renameProcess_XTEAM_RankingChaosCastle();
				$this->_renameProcess_XTEAM_RankingDevilSquare();
				$this->_renameProcess_XTEAM_RankingDuel();
				$this->_renameProcess_XTEAM_RankingIllusionTemple();
				$this->_renameProcess_XTEAM_ReconnectData();
				$this->_renameProcess_XTEAM_ReconnectOfflineData();
				$this->_renameProcess_XTEAM_SNSData();
		}
		
		redirect(1, 'usercp/renamecharacter/success/1');
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
	
	// PROTECTED FUNCTIONS
	
	protected function _characterNameExists($name) {
		$Character = new \Character();
		if(!$Character->CharacterExists($name)) return;
		return true;
	}
	
	protected function _checkLength($name) {
		if(strlen($name) < $this->_newNameMinLen) return;
		if(strlen($name) > $this->_newNameMaxLen) return;
		return true;
	}
	
	protected function _renameProcess_AccountCharacter() {
		if(!$this->_checkTable(_TBL_AC_)) return true;
		$accountCharacter = $this->mu->query_fetch_single("SELECT * FROM "._TBL_AC_." WHERE "._CLMN_AC_ID_." = ?", array($this->_username));
		
		if(array_key_exists('GameID1', $accountCharacter)) {
			if($accountCharacter['GameID1'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameID1 = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		if(array_key_exists('GameID2', $accountCharacter)) {
			if($accountCharacter['GameID2'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameID2 = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		if(array_key_exists('GameID3', $accountCharacter)) {
			if($accountCharacter['GameID3'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameID3 = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		if(array_key_exists('GameID4', $accountCharacter)) {
			if($accountCharacter['GameID4'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameID4 = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		if(array_key_exists('GameID5', $accountCharacter)) {
			if($accountCharacter['GameID5'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameID5 = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		if(array_key_exists('GameID6', $accountCharacter)) {
			if($accountCharacter['GameID6'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameID6 = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		if(array_key_exists('GameID7', $accountCharacter)) {
			if($accountCharacter['GameID7'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameID7 = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		if(array_key_exists('GameID8', $accountCharacter)) {
			if($accountCharacter['GameID8'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameID8 = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		if(array_key_exists('GameIDC', $accountCharacter)) {
			if($accountCharacter['GameIDC'] == $this->_character) $this->mu->query("UPDATE "._TBL_AC_." SET GameIDC = ? WHERE "._CLMN_AC_ID_." = ?", array($this->_newName, $this->_username));
		}
		
		return true;
	}
	
	protected function _renameProcess_Character() {
		if(!$this->_checkTable(_TBL_CHR_)) return true;
		$update = $this->mu->query("UPDATE "._TBL_CHR_." SET "._CLMN_CHR_NAME_." = ? WHERE "._CLMN_CHR_NAME_." = ?", array($this->_newName, $this->_character));
		if(!$update) return;
		return true;
	}
	
	protected function _renameProcess_Guild() {
		if(!$this->_checkTable(_TBL_GUILD_)) return true;
		$update = $this->mu->query("UPDATE "._TBL_GUILD_." SET "._CLMN_GUILD_MASTER_." = ? WHERE "._CLMN_GUILD_MASTER_." = ?", array($this->_newName, $this->_character));
		if(!$update) return;
		return true;
	}
	
	protected function _renameProcess_GuildMember() {
		if(!$this->_checkTable(_TBL_GUILDMEMB_)) return true;
		$update = $this->mu->query("UPDATE "._TBL_GUILDMEMB_." SET "._CLMN_GUILDMEMB_CHAR_." = ? WHERE "._CLMN_GUILDMEMB_CHAR_." = ?", array($this->_newName, $this->_character));
		if(!$update) return;
		return true;
	}
	
	// IGCN
	
	protected function _renameProcess_CMonsterKillCount() {
		if(!$this->_checkTable('C_Monster_KillCount')) return true;
		$this->mu->query("UPDATE C_Monster_KillCount SET name = ? WHERE name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_CPlayerKillerInfo() {
		if(!$this->_checkTable('C_PlayerKiller_info')) return true;
		$this->mu->query("UPDATE C_PlayerKiller_info SET Victim = ? WHERE Victim = ?", array($this->_newName, $this->_character));
		$this->mu->query("UPDATE C_PlayerKiller_info SET Killer = ? WHERE Killer = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_ArcaBattle() {
		if($this->_checkTable('IGC_ARCA_BATTLE_GUILD_JOIN_INFO')) $this->mu->query("UPDATE IGC_ARCA_BATTLE_GUILD_JOIN_INFO SET G_Master = ? WHERE G_Master = ?", array($this->_newName, $this->_character));
		if($this->_checkTable('IGC_ARCA_BATTLE_GUILDMARK_REG')) $this->mu->query("UPDATE IGC_ARCA_BATTLE_GUILDMARK_REG SET G_Master = ? WHERE G_Master = ?", array($this->_newName, $this->_character));
		if($this->_checkTable('IGC_ARCA_BATTLE_MEMBER_JOIN_INFO')) $this->mu->query("UPDATE IGC_ARCA_BATTLE_MEMBER_JOIN_INFO SET CharName = ? WHERE CharName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_BlockChat() {
		if(!$this->_checkTable('IGC_BlockChat')) return true;
		$this->mu->query("UPDATE IGC_BlockChat SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_ClassQuestMonsterKill() {
		if(!$this->_checkTable('IGC_ClassQuest_MonsterKill')) return true;
		$this->mu->query("UPDATE IGC_ClassQuest_MonsterKill SET CharacterName = ? WHERE CharacterName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_DuelLog() {
		if(!$this->_checkTable('IGC_DuelLog')) return true;
		$this->mu->query("UPDATE IGC_DuelLog SET Player1 = ? WHERE Player1 = ?", array($this->_newName, $this->_character));
		$this->mu->query("UPDATE IGC_DuelLog SET Player2 = ? WHERE Player2 = ?", array($this->_newName, $this->_character));
		$this->mu->query("UPDATE IGC_DuelLog SET Winner = ? WHERE Winner = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_EventMapEnterLimit() {
		if(!$this->_checkTable('IGC_EventMapEnterLimit')) return true;
		$this->mu->query("UPDATE IGC_EventMapEnterLimit SET CharacterName = ? WHERE CharacterName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_EvolutionMonster() {
		if(!$this->_checkTable('IGC_EvolutionMonster')) return true;
		$this->mu->query("UPDATE IGC_EvolutionMonster SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_FavouriteWarpData() {
		if(!$this->_checkTable('IGC_FavouriteWarpData')) return true;
		$this->mu->query("UPDATE IGC_FavouriteWarpData SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_FriendChatMessageLog() {
		if(!$this->_checkTable('IGC_FriendChat_MessageLog')) return true;
		$this->mu->query("UPDATE IGC_FriendChat_MessageLog SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
		$this->mu->query("UPDATE IGC_FriendChat_MessageLog SET FriendName = ? WHERE FriendName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_Gens() {
		if(!$this->_checkTable('IGC_Gens')) return true;
		$this->mu->query("UPDATE IGC_Gens SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_GensAbuse() {
		if(!$this->_checkTable('IGC_GensAbuse')) return true;
		$this->mu->query("UPDATE IGC_GensAbuse SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
		$this->mu->query("UPDATE IGC_GensAbuse SET KillName = ? WHERE KillName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_GremoryCase() {
		if(!$this->_checkTable('IGC_GremoryCase')) return true;
		$this->mu->query("UPDATE IGC_GremoryCase SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_GuildMatching() {
		if(!$this->_checkTable('IGC_GuildMatching')) return true;
		$this->mu->query("UPDATE IGC_GuildMatching SET GuildMasterName = ? WHERE GuildMasterName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_HuntingRecord() {
		if(!$this->_checkTable('IGC_HuntingRecord')) return true;
		$this->mu->query("UPDATE IGC_HuntingRecord SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_HuntingRecordOption() {
		if(!$this->_checkTable('IGC_HuntingRecordOption')) return true;
		$this->mu->query("UPDATE IGC_HuntingRecordOption SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_Labyrinth() {
		if($this->_checkTable('IGC_LabyrinthClearLog')) $this->mu->query("UPDATE IGC_LabyrinthClearLog SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
		if($this->_checkTable('IGC_LabyrinthInfo')) $this->mu->query("UPDATE IGC_LabyrinthInfo SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
		if($this->_checkTable('IGC_LabyrinthMissionInfo')) $this->mu->query("UPDATE IGC_LabyrinthMissionInfo SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_Muun() {
		if($this->_checkTable('IGC_Muun_ConditionInfo')) $this->mu->query("UPDATE IGC_Muun_ConditionInfo SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
		if($this->_checkTable('IGC_Muun_Inventory')) $this->mu->query("UPDATE IGC_Muun_Inventory SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
		if($this->_checkTable('IGC_Muun_Period')) $this->mu->query("UPDATE IGC_Muun_Period SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_PartyMatching() {
		if(!$this->_checkTable('IGC_PartyMatching')) return true;
		$this->mu->query("UPDATE IGC_PartyMatching SET PartyLeaderName = ? WHERE PartyLeaderNam = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_PentagramInfo() {
		if(!$this->_checkTable('IGC_PentagramInfo')) return true;
		$this->mu->query("UPDATE IGC_PentagramInfo SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_PeriodBuffInfo() {
		if(!$this->_checkTable('IGC_PeriodBuffInfo')) return true;
		$this->mu->query("UPDATE IGC_PeriodBuffInfo SET CharacterName = ? WHERE CharacterName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_PeriodItemInfo() {
		if(!$this->_checkTable('IGC_PeriodItemInfo')) return true;
		$this->mu->query("UPDATE IGC_PeriodItemInfo SET CharacterName = ? WHERE CharacterName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_RestoreItemInventory() {
		if(!$this->_checkTable('IGC_RestoreItem_Inventory')) return true;
		$this->mu->query("UPDATE IGC_RestoreItem_Inventory SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_WaitGuildMatching() {
		if(!$this->_checkTable('IGC_WaitGuildMatching')) return true;
		$this->mu->query("UPDATE IGC_WaitGuildMatching SET GuildMasterName = ? WHERE GuildMasterName = ?", array($this->_newName, $this->_character));
		$this->mu->query("UPDATE IGC_WaitGuildMatching SET ApplicantName = ? WHERE ApplicantName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_IGCN_WaitPartyMatching() {
		if(!$this->_checkTable('IGC_WaitPartyMatching')) return true;
		$this->mu->query("UPDATE IGC_WaitPartyMatching SET LeaderName = ? WHERE LeaderName = ?", array($this->_newName, $this->_character));
		$this->mu->query("UPDATE IGC_WaitPartyMatching SET MemberName = ? WHERE MemberName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_OptionData() {
		if(!$this->_checkTable('OptionData')) return true;
		$this->mu->query("UPDATE OptionData SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TBombGameScore() {
		if(!$this->_checkTable('T_BombGameScore')) return true;
		$this->mu->query("UPDATE T_BombGameScore SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TCGuid() {
		if(!$this->_checkTable('T_CGuid')) return true;
		$this->mu->query("UPDATE T_CGuid SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TEventInventory() {
		if(!$this->_checkTable('T_Event_Inventory')) return true;
		$this->mu->query("UPDATE T_Event_Inventory SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TFriendList() {
		if(!$this->_checkTable('T_FriendList')) return true;
		$this->mu->query("UPDATE T_FriendList SET FriendName = ? WHERE FriendName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TFriendMail() {
		if(!$this->_checkTable('T_FriendMail')) return true;
		$this->mu->query("UPDATE T_FriendMail SET FriendName = ? WHERE FriendName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TFriendMain() {
		if(!$this->_checkTable('T_FriendMain')) return true;
		$this->mu->query("UPDATE T_FriendMain SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TGuideQuestInfo() {
		if(!$this->_checkTable('T_GUIDE_QUEST_INFO')) return true;
		$this->mu->query("UPDATE T_GUIDE_QUEST_INFO SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TLuckyItemInfo() {
		if(!$this->_checkTable('T_LUCKY_ITEM_INFO')) return true;
		$this->mu->query("UPDATE T_LUCKY_ITEM_INFO SET CharName = ? WHERE CharName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TMineSystem() {
		if(!$this->_checkTable('T_MineSystem')) return true;
		$this->mu->query("UPDATE T_MineSystem SET CharacterName = ? WHERE CharacterName = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TMuRummy() {
		if($this->_checkTable('T_MuRummy')) $this->mu->query("UPDATE T_MuRummy SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
		if($this->_checkTable('T_MuRummyInfo')) $this->mu->query("UPDATE T_MuRummyInfo SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
		if($this->_checkTable('T_MuRummyLog')) $this->mu->query("UPDATE T_MuRummyLog SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TPShopItemValueInfo() {
		if(!$this->_checkTable('T_PSHOP_ITEMVALUE_INFO')) return true;
		$this->mu->query("UPDATE T_PSHOP_ITEMVALUE_INFO SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TQuestExpInfo() {
		if(!$this->_checkTable('T_QUEST_EXP_INFO')) return true;
		$this->mu->query("UPDATE T_QUEST_EXP_INFO SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_TWaitFriend() {
		if(!$this->_checkTable('T_WaitFriend')) return true;
		$this->mu->query("UPDATE T_WaitFriend SET FriendName = ? WHERE FriendName = ?", array($this->_newName, $this->_character));
	}
	
	// XTEAM
	
	protected function _renameProcess_XTEAM_ArcaBattleGuildMember() {
		if(!$this->_checkTable('ArcaBattleGuildMember')) return true;
		$this->mu->query("UPDATE ArcaBattleGuildMember SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_ChatBlockData() {
		if(!$this->_checkTable('ChatBlockData')) return true;
		$this->mu->query("UPDATE ChatBlockData SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_EventEntryCount() {
		if(!$this->_checkTable('EventEntryCount')) return true;
		$this->mu->query("UPDATE EventEntryCount SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_EventInventory() {
		if(!$this->_checkTable('EventInventory')) return true;
		$this->mu->query("UPDATE EventInventory SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_EventLeoTheHelper() {
		if(!$this->_checkTable('EventLeoTheHelper')) return true;
		$this->mu->query("UPDATE EventLeoTheHelper SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_EventSantaClaus() {
		if(!$this->_checkTable('EventSantaClaus')) return true;
		$this->mu->query("UPDATE EventSantaClaus SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_FavoriteMoveList() {
		if(!$this->_checkTable('FavoriteMoveList')) return true;
		$this->mu->query("UPDATE FavoriteMoveList SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_Gens_Left() {
		if(!$this->_checkTable('Gens_Left')) return true;
		$this->mu->query("UPDATE Gens_Left SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_Gens_Rank() {
		if(!$this->_checkTable('Gens_Rank')) return true;
		$this->mu->query("UPDATE Gens_Rank SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_Gens_Reward() {
		if(!$this->_checkTable('Gens_Reward')) return true;
		$this->mu->query("UPDATE Gens_Reward SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_GremoryCaseLocal() {
		if(!$this->_checkTable('GremoryCaseLocal')) return true;
		$this->mu->query("UPDATE GremoryCaseLocal SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_HelperData() {
		if(!$this->_checkTable('HelperData')) return true;
		$this->mu->query("UPDATE HelperData SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_HuntingLog() {
		if(!$this->_checkTable('HuntingLog')) return true;
		$this->mu->query("UPDATE HuntingLog SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_ItemBuyBack() {
		if(!$this->_checkTable('ItemBuyBack')) return true;
		$this->mu->query("UPDATE ItemBuyBack SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_LabyrinthInfo() {
		if(!$this->_checkTable('LabyrinthInfo')) return true;
		$this->mu->query("UPDATE LabyrinthInfo SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_LabyrinthMissionInfo() {
		if(!$this->_checkTable('LabyrinthMissionInfo')) return true;
		$this->mu->query("UPDATE LabyrinthMissionInfo SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_MasterSkillTree() {
		if(!$this->_checkTable('MasterSkillTree')) return true;
		$this->mu->query("UPDATE MasterSkillTree SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_MasterSkillTreeExt() {
		if(!$this->_checkTable('MasterSkillTreeExt')) return true;
		$this->mu->query("UPDATE MasterSkillTreeExt SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_MuunInventory() {
		if(!$this->_checkTable('MuunInventory')) return true;
		$this->mu->query("UPDATE MuunInventory SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_PentagramJewel() {
		if(!$this->_checkTable('PentagramJewel')) return true;
		$this->mu->query("UPDATE PentagramJewel SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_PShopItemValue() {
		if(!$this->_checkTable('PShopItemValue')) return true;
		$this->mu->query("UPDATE PShopItemValue SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_QuestGuide() {
		if(!$this->_checkTable('QuestGuide')) return true;
		$this->mu->query("UPDATE QuestGuide SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_QuestKillCount() {
		if(!$this->_checkTable('QuestKillCount')) return true;
		$this->mu->query("UPDATE QuestKillCount SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_QuestWorld() {
		if(!$this->_checkTable('QuestWorld')) return true;
		$this->mu->query("UPDATE QuestWorld SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_RankingBloodCastle() {
		if(!$this->_checkTable('RankingBloodCastle')) return true;
		$this->mu->query("UPDATE RankingBloodCastle SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_RankingCastleSiege() {
		if(!$this->_checkTable('RankingCastleSiege')) return true;
		$this->mu->query("UPDATE RankingCastleSiege SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_RankingChaosCastle() {
		if(!$this->_checkTable('RankingChaosCastle')) return true;
		$this->mu->query("UPDATE RankingChaosCastle SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_RankingDevilSquare() {
		if(!$this->_checkTable('RankingDevilSquare')) return true;
		$this->mu->query("UPDATE RankingDevilSquare SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_RankingDuel() {
		if(!$this->_checkTable('RankingDuel')) return true;
		$this->mu->query("UPDATE RankingDuel SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_RankingIllusionTemple() {
		if(!$this->_checkTable('RankingIllusionTemple')) return true;
		$this->mu->query("UPDATE RankingIllusionTemple SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_ReconnectData() {
		if(!$this->_checkTable('ReconnectData')) return true;
		$this->mu->query("UPDATE ReconnectData SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_ReconnectOfflineData() {
		if(!$this->_checkTable('ReconnectOfflineData')) return true;
		$this->mu->query("UPDATE ReconnectOfflineData SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	protected function _renameProcess_XTEAM_SNSData() {
		if(!$this->_checkTable('SNSData')) return true;
		$this->mu->query("UPDATE SNSData SET Name = ? WHERE Name = ?", array($this->_newName, $this->_character));
	}
	
	
	// PRIVATE FUNCTIONS
	
	private function _moduleExists($module) {
		if(!check_value($module)) return;
		if(!file_exists(__PATH_RENAMECHARACTER_ROOT__ . $this->_modulesPath . '/' . $module . '.php')) return;
		return true;
	}
	
	private function _checkTable($tableName) {
		$tableExists = $this->mu->query_fetch_single("SELECT * FROM sysobjects WHERE xtype = 'U' AND name = ?", array($tableName));
		if($tableExists) return true;
		return;
	}
	
}