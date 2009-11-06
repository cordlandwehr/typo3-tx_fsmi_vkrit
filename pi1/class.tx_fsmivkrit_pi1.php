<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Andreas Cord-Landwehr <fsmi@uni-paderborn.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('fsmi_vkrit').'api/class.tx_fsmivkrit_div.php');
require_once(t3lib_extMgm::extPath('fsmi_vkrit').'pi2/class.tx_fsmivkrit_pi2.php');
require_once(t3lib_extMgm::extPath('rlmp_dateselectlib').'class.tx_rlmpdateselectlib.php');


/**
 * Plugin 'Input for Lecturers' for the 'fsmi_vkrit' extension.
 *
 * @author	Andreas Cord-Landwehr <fsmi@uni-paderborn.de>
 * @package	TYPO3
 * @subpackage	tx_fsmivkrit
 */
class tx_fsmivkrit_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_fsmivkrit_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_fsmivkrit_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'fsmi_vkrit';	// The extension key.
	
	// options for this frontend plugin
	const kVERIFY	= 1;
	const kSAVE		= 2;
	
	const kEVAL_STATE_CREATED	= 0;
	const kEVAL_STATE_NOTIFIED	= 1;
	const kEVAL_STATE_COMPLETED	= 2;
	const kEVAL_STATE_APPROVED	= 3;
	const kEVAL_STATE_EVALUATED	= 4;
	const kEVAL_STATE_FINISHED	= 5;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	
		// invoke calender frontend library
		tx_rlmpdateselectlib::includeLib();
		
		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$lecture = intval($GETcommands['lecture']);
		$hash = htmlspecialchars($GETcommands['auth']);
		
		$content .= '<h1>Dateneingabe für Veranstaltungen</h1>';
		
		switch (intval($GETcommands['type'])) {
			case self::kVERIFY: {
				$content .= '<h2>Eingabe bestätigen</h2>';
				$content .= $this->printInputValuesToCheck($lecture,$hash); 
				break;
			}
			case self::kSAVE: {
				$content .= '<h2>Eingabe abgeschlossen</h2>';
				$content .= $this->saveInputValues($lecture,$hash);
				break;
			}
			default: {
				$content .= '<h2>Daten eingeben</h2>';
				$content .= $this->printInputForm($lecture, $hash);
			}
		}
	
		return $this->pi_wrapInBaseClass($content);
	}
	
	function printInputForm ($lecture,$hash) {
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lectureUID['lecturer']);
		$surveyUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $lectureUID['survey']);
		
		$content = '';
		$content = '<strong>Evaluation:</strong> '.$surveyUID['name'].' - '.$surveyUID['semester'];
		
		$content .= '<h3>Einzutragende Veranstaltung</h3>';
		$content .= '<ul>'.
					'<li><strong>Veranstaltung:</strong> '.$lectureUID['name'].'</li>'.
					'<li><strong>PAUL-ID:</strong> '.$lectureUID['foreign_id'].'</li>'.
					'<li><strong>Dozent:</strong> '.$lecturerUID['name'].', '.$lecturerUID['forename'].'</li>'.
					'</ul>';

		// verify hash BEFORE form
		if ($hash != $lectureUID['inputform_verify'])
			return $content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_ERROR,
									'Der Verifikationswert ist falsch. Bitte verwenden sie die exakte URL aus der Benachrichtigungsmail.');
		
		$content .= '<h3>Dateneingabe</h3>';
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';
		
		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value='.self::kVERIFY.' />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[auth]'.'" value="'.$hash.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[lecture]'.'" value="'.$lecture.'" />';
		
		$content .= '<fieldset><legend>Vorlesung</legend><table>';
		// participants
		$content .= '<tr>
						<td><label for="'.$this->extKey.'_participants">Teilnehmer:</label></td>
						<td><input type="text" name="'.$this->extKey.'[participants]" size="3" id="'.$this->extKey.'_participants"  	
								value="'.htmlspecialchars($this->piVars["participants"]).'" /></td>
					</tr>'; //TODO make selector

		// assistents
		$content .= '<tr>
						<td style="vertical-align:top"><label for="'.$this->extKey.'_assistants">Tutoren:</label></td>
						<td><div>Exakt einen Tutor pro Zeile eintragen "Nachname,Vorname", mit Komma (,) trennen.<br />
							Beispiel: "Mustermann,Max"</div>
							<textarea name="'.$this->extKey.'[assistants]" id="'.$this->extKey.'_assistants" cols="74" rows="15"></textarea></td>
					</tr>'; //TODO make selector
		
		$content .= '</table></fieldset>';
		
		// configure Date Selector
		$dateSelectorConf = array (
		 	'calConf.' => array (
		 		'dateTimeFormat' => 'dd.mm.y',
	//	 		'stylesheet' => 'fileadmin/mystyle.css'
         		)
			);
		

		// Vkrit suggestion 1
		$content .= '<fieldset>';
		$content .= '<legend>V-Krit-Termin Vorschlag 1:</legend>';
		$content .= '<table><tr>
						<td><label for="'.$this->extKey.'_eval_date_1">Datum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_date_1]" id="'.$this->extKey.'_eval_date_1"  	
								value="'.htmlspecialchars($this->piVars["eval_date_1"]).'" size="10" />'.
								tx_rlmpdateselectlib::getInputButton ($this->extKey.'_eval_date_1',$dateSelectorConf).
					'</td></tr>
					<tr><td>'.
						'<label for="'.$this->extKey.'_eval_time_1">Uhrzeit:</label></td>
						<td><select type="text" name="'.$this->extKey.'[eval_time_1]" id="'.$this->extKey.'_eval_time_1"  	
								value="'.htmlspecialchars($this->piVars["eval_time_1"]).'">'.
								$this->printOptionListTime().
						'</select>	
					</td></tr>
					<tr><td>
						 <label for="'.$this->extKey.'_eval_room_1">Raum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_room_1]" id="'.$this->extKey.'_eval_room_1"  	
								value="'.htmlspecialchars($this->piVars["eval_room_1"]).'" />			
					</td></tr></table></fieldset>';		
						
		$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_INFO,
									'Mindestens ein Terminvorschlag muss angegeben werden. Weitere Vorschläge sind optional.');
			
								
		// Vkrit suggestion 2
		$content .= '<fieldset>';
		$content .= '<legend>V-Krit-Termin Vorschlag 2 (optional):</legend>';
		$content .= '<table><tr>
						<td><label for="'.$this->extKey.'_eval_date_2">Datum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_date_2]" id="'.$this->extKey.'_eval_date_2"  	
								value="'.htmlspecialchars($this->piVars["eval_date_2"]).'" size="10" />'.
								tx_rlmpdateselectlib::getInputButton ($this->extKey.'_eval_date_2',$dateSelectorConf).
					'</td></tr>
					<tr><td>'.
						'<label for="'.$this->extKey.'_eval_time_2">Uhrzeit:</label></td>
						<td><select type="text" name="'.$this->extKey.'[eval_time_2]" id="'.$this->extKey.'_eval_time_2"  	
								value="'.htmlspecialchars($this->piVars["eval_time_2"]).'">'.
								$this->printOptionListTime().
						'</select>	
					</td></tr>
					<tr><td>
						 <label for="'.$this->extKey.'_eval_room_2">Raum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_room_2]" id="'.$this->extKey.'_eval_room_2"  	
								value="'.htmlspecialchars($this->piVars["eval_room_2"]).'" />			
					</td></tr></table></fieldset>';		

		// Vkrit suggestion 3
		$content .= '<fieldset>';
		$content .= '<legend>V-Krit-Termin Vorschlag 3 (optional):</legend>';
		$content .= '<table><tr>
						<td><label for="'.$this->extKey.'_eval_date_3">Datum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_date_3]" id="'.$this->extKey.'_eval_date_3"  	
								value="'.htmlspecialchars($this->piVars["eval_date_3"]).'" size="10" />'.
								tx_rlmpdateselectlib::getInputButton ($this->extKey.'_eval_date_3',$dateSelectorConf).
					'</td></tr>
					<tr><td>'.
						'<label for="'.$this->extKey.'_eval_time_3">Uhrzeit:</label></td>
						<td><select type="text" name="'.$this->extKey.'[eval_time_3]" id="'.$this->extKey.'_eval_time_3"  	
								value="'.htmlspecialchars($this->piVars["eval_time_3"]).'">'.
								$this->printOptionListTime().
						'</select>	
					</td></tr>
					<tr><td>
						 <label for="'.$this->extKey.'_eval_room_3">Raum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_room_3]" id="'.$this->extKey.'_eval_room_3"  	
								value="'.htmlspecialchars($this->piVars["eval_room_3"]).'" />			
					</td></tr></table></fieldset>';		
		
		// comment input
		$content .= '<fieldset>';
		$content .= '<legend>Ergänzende Informationen:</legend>';
		$content .= '<textarea name="'.$this->extKey.'[comment]" cols="74" id="'.$this->extKey.'_comment"></textarea></fieldset>';	

		// submit button
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]" 
				value="'.htmlspecialchars('Daten überprüfen').'">';
		
		$content .= '</form>';

		return $content;
	}
	
	function printInputValuesToCheck ($lecture,$hash) {
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lectureUID['lecturer']);
		$surveyUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $lectureUID['survey']);
		
		$inputData = $this->getInputValuesToArray ();
		
		$content = '';
		
		$content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_INFO,
							'Daten wurden noch nicht gespeichert.'); 
		
		$content .= '<strong>Evaluation:</strong> '.$surveyUID['name'].' - '.$surveyUID['semester'];
		
		$content .= '<h3>Einzutragende Veranstaltung</h3>';
		$content .= '<ul>'.
					'<li><strong>Veranstaltung:</strong> '.$lectureUID['name'].' ('.$lectureUID['foreign_id'].')</li>'.
					'<li><strong>Dozent:</strong> '.$lecturerUID['name'].', '.$lecturerUID['forename'].'</li>'.
					'</ul>';

		// verify hash BEFORE form
		if ($hash != $lectureUID['inputform_verify'])
			return $content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_ERROR,
									'Der Verifikationswert ist falsch. Bitte verwenden sie die exakte URL aus der Benachrichtigungsmail.');
		
		$content .= '<h3>Daten überprüfen</h3>';
	
		$content .= '<div><strong>Teilnehmer:</strong> '.$inputData['participants'].'</div>';

		$content .= '<div><strong>Tutoren:</strong></div>';
		$content .= '<ol>';
		foreach ($inputData['assistant'] as $tutor)
			$content .= '<li>'.trim($tutor[0]).', '.trim($tutor[1]).'</li>'."\n";
		$content .= '</ol>';

		$content .= '<div><strong>V-Krit Termine:</strong></div>';
		$content .= '<ol>';
		for($i=1; $i<=3; $i++) {
			if ($inputData['eval_'.$i]['date']=='')
				continue;
			$content .= '<li><strong>Termin:</strong> '.date('d.m.Y h:i', $inputData['eval_'.$i]['date']).', 
						<strong>Raum:</strong> '.$inputData['eval_'.$i]['room'].'</li>';
		}
		$content .= '</ol>';
		
		$content .= '<div><strong>Kommentar/Ergänzung</strong></div>';
		$content .= '<pre>'.$inputData['comment'].'</pre>';
		
		// save everything in session
		$GLOBALS['TSFE']->fe_user->setKey('ses','inputData', $inputData);
				
		$content .= '<h3>Daten übermitteln</h3>';
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';
		
		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value='.self::kSAVE.' />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[auth]'.'" value="'.$hash.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[lecture]'.'" value="'.$lecture.'" />';
									
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]" 
				value="'.htmlspecialchars('Daten speichern').'">';
		
		$content .= '</form>';

		return $content;
	}
	
	function getInputValuesToArray () {
		$POSTdata = t3lib_div::_POST($this->extKey);
		$inputData = array ();
		
		// participants
		$inputData['participants'] = intval($POSTdata['participants']);
		
		// assistants
		$tutorArr = explode("\n",htmlspecialchars($POSTdata['assistants']));
		$inputData['assistant'] = array ();
		foreach($tutorArr as $tutor)
			array_push($inputData['assistant'],explode(',',$tutor));

		// vkrit dates
		for ($i=1; $i<=3; $i++) {
			if ($POSTdata['eval_date_'.$i]=='')
				continue;
			$inputData['eval_'.$i]['date'] = strtotime(
						htmlspecialchars($POSTdata['eval_date_'.$i]).' '.htmlspecialchars($POSTdata['eval_time_'.$i]).':00');
			$inputData['eval_'.$i]['room'] = htmlspecialchars($POSTdata['eval_room_'.$i]);
		}
		
		$inputData['comment'] = htmlspecialchars($POSTdata['comment']);
		
		return $inputData;
	}
	
	/**
	 * Creates a list of time steps from 7am to 8pm in steps of a quarter.
	 * @return list of <option>...</option> entries for a HTML selector.
	 */
	function printOptionListTime() {
		$content = '';
		for ($hour=7; $hour<20; $hour++)
			for ($min=0; $min<60; $min+=15) {
				$hour<10? $hourPrint='0'.$hour: $hourPrint=$hour;
				$min<10? $minPrint='0'.$min: $minPrint=$min;
				
				$content .= '<option value="'.$hourPrint.':'.$minPrint.'">'.$hourPrint.':'.$minPrint.'</option>'."\n";
			}
		return $content;
	}
	
	function saveInputValues($lecture,$hash) {
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lectureUID['lecturer']);
		$surveyUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $lectureUID['survey']);
		$inputData = $GLOBALS['TSFE']->fe_user->getKey('ses','inputData');
		
		$content = '';

		// verify hash BEFORE form
		if ($hash != $lectureUID['inputform_verify'])
			return $content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_ERROR,
									'Der Verifikationswert ist falsch. Bitte verwenden sie die exakte URL aus der Benachrichtigungsmail.');

		// update Lecture
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(	
									'tx_fsmivkrit_lecture',
									'uid=\''.$lecture.'\'',
									array (	'crdate' => time(),
											'tstamp' => time(),
											'participants' 	=> $inputData['participants'],
											'eval_date_1' 	=> $inputData['eval_1']['date'],
											'eval_date_2' 	=> $inputData['eval_2']['date'],
											'eval_date_3'	=> $inputData['eval_3']['date'],
											'eval_room_1'	=> $inputData['eval_1']['room'],
											'eval_room_2'	=> $inputData['eval_2']['room'],
											'eval_room_3'	=> $inputData['eval_3']['room'],
											'eval_state'	=> self::kEVAL_STATE_COMPLETED,
											'comment'		=> $inputDate['comment']
									));
		if (!$res)
			return tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_ERROR,
							'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.'); 
							
		// insert tutorials
		foreach ($inputData['assistant'] as $tutor) {
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(	
									'tx_fsmivkrit_tutorial',
									array (	'pid' => $lectureUID['pid'],
											'crdate' => time(),
											'tstamp' => time(),
											'assistant_name' => $tutor[0],
											'assistant_forename' => $tutor[1],
											'lecture' => $lectureUID['uid'],
									));
			if (!$res)
				return tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_ERROR,
							'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.'); 				
		}
		
		$content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_OK,
							'Daten erfolgreich gespeichert.'); 
							
		// give oppurtinity to insert another lecture
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
												FROM tx_fsmivkrit_lecture 
												WHERE deleted=0 AND hidden=0
												AND survey=\''.$surveyUID['uid'].'\'
												AND (eval_state='.self::kEVAL_STATE_CREATED.'
													OR eval_state='.self::kEVAL_STATE_NOTIFIED.')
												AND lecturer=\''.$lecturerUID['uid'].'\'');
		$lectureArr = array();
		if ($res && $row = mysql_fetch_assoc($res)) {
			$content .= '<h3>Eingabe fortsetzen</h3><div>Sie können direkt mit weiteren Eintragungen fortfahren:</div>';
			$content .= '<div style="text-align:center;">'.$this->pi_linkTP('<strong>weitere Vorlesung eintragen</strong>', 
								array (	
									$this->extKey.'[auth]' => $row['inputform_verify'],
									$this->extKey.'[lecture]' => $row['uid']
								)).'</div>';
		}
		else
			$content .= '<div>Vielen Dank für Ihre Eintragungen.</div>';
			
		return $content;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi1/class.tx_fsmivkrit_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi1/class.tx_fsmivkrit_pi1.php']);
}

?>