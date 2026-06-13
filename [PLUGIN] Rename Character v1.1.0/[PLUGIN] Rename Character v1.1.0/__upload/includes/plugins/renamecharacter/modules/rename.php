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

if(!isLoggedIn()) redirect(1,'login');

echo '<div class="page-title"><span>'.lang('renamecharacter_title',true).'</span></div>';

try {
	
	$RenameCharacter = new \Plugin\RenameCharacter\RenameCharacter();
	$RenameCharacter->setUsername($_SESSION['username']);
	$AccountCharacters = $RenameCharacter->getAccountCharacterList();
	$Character = new Character();
	
	if(check_value($_GET['success'])) {
		message('success', lang('renamecharacter_success_1'));
	}
	
	if(check_value($_GET['character'])) {
		
		// Selected Character
		if(!in_array($_GET['character'], $AccountCharacters)) throw new Exception(lang('renamecharacter_error_6'));
		
		// Submit
		if(check_value($_POST['submit'])) {
			try {
				
				if($_POST['character'] != $_GET['character']) throw new Exception(lang('renamecharacter_error_6'));
				$RenameCharacter->setUserid($_SESSION['userid']);
				$RenameCharacter->setCharacter($_POST['character']);
				$RenameCharacter->setNewCharacterName($_POST['new_name']);
				$RenameCharacter->changeName();
				
			} catch(Exception $ex) {
				message('error', $ex->getMessage());
			}
		}
		
		echo '<table class="table general-table-ui">';
			echo '<tr>';
				echo '<td></td>';
				echo '<td>'.lang('renamecharacter_txt_1',true).'</td>';
				echo '<td>'.lang('renamecharacter_txt_3',true).'</td>';
				echo '<td></td>';
			echo '</tr>';
			
			$characterData = $Character->CharacterData($_GET['character']);
			$characterIMG = $Character->GenerateCharacterClassAvatar($characterData[_CLMN_CHR_CLASS_]);
			
			echo '<form action="'.__RENAMECHARACTER_HOME__.'character/'.$characterData[_CLMN_CHR_NAME_].'" method="post">';
				echo '<input type="hidden" name="character" value="'.$characterData[_CLMN_CHR_NAME_].'"/>';
				echo '<tr>';
					echo '<td>'.$characterIMG.'</td>';
					echo '<td>'.$characterData[_CLMN_CHR_NAME_].'</td>';
					echo '<td><input type="text" class="form-control" name="new_name" maxlength="'.$RenameCharacter->getMaxLen().'" required autofocus/></td>';
					echo '<td><button type="submit" name="submit" value="submit" class="btn btn-primary">'.lang('renamecharacter_txt_2',true).'</button></td>';
				echo '</tr>';
			echo '</form>';
		echo '</table>';
		
	} else {
		
		// Character List
		
		echo '<table class="table general-table-ui">';
			echo '<tr>';
				echo '<td></td>';
				echo '<td>'.lang('renamecharacter_txt_1',true).'</td>';
				echo '<td></td>';
			echo '</tr>';
			foreach($AccountCharacters as $row) {
				$characterData = $Character->CharacterData($row);
				$characterIMG = $Character->GenerateCharacterClassAvatar($characterData[_CLMN_CHR_CLASS_]);
				echo '<tr>';
					echo '<td>'.$characterIMG.'</td>';
					echo '<td>'.$characterData[_CLMN_CHR_NAME_].'</td>';
					echo '<td><a href="'.__RENAMECHARACTER_HOME__.'character/'.$characterData[_CLMN_CHR_NAME_].'" class="btn btn-primary">'.lang('renamecharacter_txt_2',true).'</a></td>';
				echo '</tr>';
			}
		echo '</table>';
		
	}
	
	echo '<div class="module-requirements text-center">';
		echo '<p>'.langf('renamecharacter_txt_4', array($RenameCharacter->getCost())).'</p>';
		echo '<p>'.langf('renamecharacter_txt_6', array($RenameCharacter->getMinLen(), $RenameCharacter->getMaxLen())).'</p>';
		echo '<p>'.lang('renamecharacter_txt_5').'</p>';
	echo '</div>';
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}