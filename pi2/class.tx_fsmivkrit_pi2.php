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

/**
 * Plugin 'Organization Tool' for the 'fsmi_vkrit' extension.
 *
 * @author	Andreas Cord-Landwehr <fsmi@uni-paderborn.de>
 * @package	TYPO3
 * @subpackage	tx_fsmivkrit
 */
class tx_fsmivkrit_pi2 extends tslib_pibase {
	var $prefixId      = 'tx_fsmivkrit_pi2';		// Same as class name
	var $scriptRelPath = 'pi2/class.tx_fsmivkrit_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'fsmi_vkrit';	// The extension key.
	var $survey;
	
	// global const
	const kLIST			= 1;
	const kNOTIFY_FORM 	= 2;
	const kNOTIFY_SEND 	= 3;
	
	// states for lecture by 'eval_state' from table
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
		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin
		
		$content = '';

		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$this->survey = intval($GETcommands['survey']);
		
		// type selection head
		$content .= $this->createTypeSelector();
		
		// subselecter head
		$content .= $this->createSurveySelector();
		
		// select input type
		switch (intval($GETcommands['type'])) {
			case self::kLIST: {
				// check for POST data
				$content .= $this->printLectureList();
				break;
			}
			case self::kNOTIFY_FORM: {
				$content .= $this->printLecturerNotifyForm(intval($GETcommands['lecturer']));
				break;
			}
			case self::kNOTIFY_SEND: {
				$content .= $this->sendLecturerNotification(
								intval($GETcommands['lecturer']), 
								htmlspecialchars($GETcommands['comment']));
				break;
			}
			default: 
				$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_INFO,
									'Diese Ansicht dient zur Organisation der Eintragung, Information der Dozenten und Bearbeitung der Eintragungen.');
				$content .= $this->printLectureList();
				break;
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function createSurveySelector () {
		
		// no survey selected, yet
		if ($this->survey==0) {
			$content = '<div><h3>Wähle eine Umfrage</h3><ul>';
			
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
													FROM tx_fsmivkrit_survey 
													WHERE deleted=0 AND hidden=0');
			while ($res && $row = mysql_fetch_assoc($res))
				$content .= '<li>'.$this->pi_linkTP($row['name'].' - '.$row['semester'],
														array (	$this->extKey.'[survey]' => $row['uid'])).
							'</li>';
			
			$content .= '</div>';
		}
		else {
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
													FROM tx_fsmivkrit_survey 
													WHERE deleted=0 AND hidden=0
													AND uid=\''.$this->survey.'\'');
			while ($res && $row = mysql_fetch_assoc($res))
				$content .= '<div style="margin-top:10px; margin-bottom:10px;"><strong>Aktuelle Umfrage:</strong> '.
								$row['name'].' - '.$row['semester'].
								' '.
								$this->pi_linkTP('(Umfrage wechseln)').
							'</div>';
		}
		return $content;
	}
	
	function createTypeSelector () {
		$content = '<div>';
		$content .= $this->pi_linkTP('Listenansicht', 
								array (	$this->extKey.'[type]' => self::kLIST,
										$this->extKey.'[survey]' => $this->survey));
		$content .= ' | ';
		$content .= $this->pi_linkTP('Eintragung anfordern (alle unbearbeitete)', 
								array (	$this->extKey.'[type]' => self::kNOTIFY_FORM,
										$this->extKey.'[survey]' => $this->survey,
										$this->extKey.'[lecturer]' => 0));
		$content .= '</div>';

		return $content;							
	}
	
	
	function printLectureList() {
		$content = '';
		if ($this->survey==0)
			return '';
		
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
												FROM tx_fsmivkrit_lecture 
												WHERE deleted=0 AND hidden=0
												AND survey=\''.$this->survey.'\'');
		// print head
		$content .= '<table cellpadding="5" cellspacing="2" class="fsmivkrit">';
		$content .= '<tr><th>Status</th><th>Veranstaltung</th><th>Dozent</th><th>Erinnerungsmail</th>';
		
		while ($res && $row = mysql_fetch_assoc($res)) {
			// get lecturer name
			$resLecturer = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $row['lecturer']);
			
			$content .= '<tr class="fsmivkrit_state_'.$row['eval_state'].'">
							<td width="50">'.($row['eval_state']).'</td>
							<td width="250">'.$row['name'].'</td>
							<td width="300"><a href="mailto:'.$resLecturer['forename'].' '.$resLecturer['name'].'<'.$resLecturer['email'].'>?subject=Veranstaltungskritik">'.
								$resLecturer['name'].', '.$resLecturer['forename'].'</a></td>';
			//TODO this does not work: change to check if there is such a lecture!
			if ($resLecture['eval_state']<self::kEVAL_STATE_COMPLETED)
				$content .= '<td width="100">'.$this->pi_linkTP('erinnern', 
								array (	$this->extKey.'[type]' => self::kNOTIFY_FORM,
										$this->extKey.'[survey]' => $this->survey,
										$this->extKey.'[lecturer]' => $resLecturer['uid'])).
							'</td>';
			else
				$content .= '<td width="100">erinnern</td>';
			$contetn .= '</tr>';
			
		}
		$content .= '</table>';
		return $content;
	}
	
	/**
	 * This function is a form to send notification mails to lecturers. 
	 * Note that $lecturer=0 means that mail is send to all lecturers without information inserted
	 * @param integer $lecturer
	 * @return text HTML for input form
	 */
	function printLecturerNotifyForm ($lecturer) {
		$content = '';
		
		// the user probably wants to have a way out:
		$content .= '<div style="margin:10px;"><strong>'.$this->pi_linkTP('Eingabe abbrechen!', 
						array (	
							$this->extKey.'[type]' => self::kLIST,
							$this->extKey.'[survey]' => $this->survey
						)).'</strong></div>';
		
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lecturer);
		
		// recall, that mail is send to everybody
		if ($lecturer==0)
			$content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_WARNING,
							'Diese Erinnerungsmail wird an <b>alle Dozenten</b> geschickt, die noch keine Daten bestätigt haben.');
		else
			$content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_INFO,
							'Erinnerungsmail wird an <b>'.$lecturerUID['forename'].' '.$lecturerUID['name'].'</b> geschickt.');
		
		if ($lecturer!=0) {			
			$content .= '<h3>E-mail Kopf</h3>'.
						'<pre style="margin-left:20px">'.$this->printLecturerNotificationHead($lecturerUID['uid']).'</pre>';
		}					
		
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';
		
		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value="'.self::kNOTIFY_SEND.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[survey]'.'" value="'.$this->survey.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[lecturer]'.'" value="'.$lecturer.'" />';
		
		$content .= '
			<h3>E-mail Body</h3>
			<div style="margin-left:20px;">
			<textarea name="'.$this->extKey.'[comment]" cols="74" rows="15" id="'.$this->extKey.'_comment">
bitte verwenden Sie die angefügten Links um für Ihre diesjährigen 
Veranstaltungen Termine zur Vorlesungs-Evaluation (V-Krit) anzugeben. 
Sollte Ihre Veranstaltung unter 10 Teilnehmer haben, so können Sie uns 
dieses auch über das verlinkte Formular mitteilen.

Bei Rückfragen melden Sie sich bitte bei criticus@uni-paderborn.de. 
Dieses Jahr verwenden wir erstmals einen Datenimport aus PAUL. Dieser
ist jedoch noch nicht automatisiert zu verarbeiten und erfordert einen 
hohen manuellen Aufwand. Sollten bei dieser Verarbeitung Fehler 
aufgetreten sein, was sich insbesondere in nicht aufgeführten 
Veranstaltungen widerspiegelt, so teilen Sie uns dieses bitte umgehend 
mit. 
</textarea></div>
		';
		if ($lecturer!=0) {
			$content .= '<h3>Links</h3>';
			$content .= '<pre style="margin-left:20px">'.
						$this->printLecturerNotificationInputlinks($lecturer).
						'</pre>';
		}
					
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]" 
				value="'.htmlspecialchars('Mail absenden').'">';
		$content .= '</form>';
		
		return $content;
	}
	
	function printLecturerNotificationHead ($lecturer) {
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lecturer);
		
		$content = '';
		$content .= 'Sehr geehrte/r '.$lecturerUID['title'].' '.$lecturerUID['name'].','.
					"\n\n";
		return $content;
		
	}
	
	function printLecturerNotificationInputlinks ($lecturer) {
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
												FROM tx_fsmivkrit_lecture 
												WHERE deleted=0 AND hidden=0
												AND survey=\''.$this->survey.'\'
												AND (
													eval_state='.self::kEVAL_STATE_CREATED.'
													OR eval_state='.self::kEVAL_STATE_NOTIFIED.')
												AND lecturer=\''.$lecturer.'\'');
		$lectureArr = array();
		while ($res && $row = mysql_fetch_assoc($res)) {
			// create hash-values if needed
			if ($row['inputform_verify']==0)
				$hash = $this->createLectureAuthenticationHash($row['uid']);
			else
				$hash = $row['inputform_verify'];
				
			// TODO correct this!	
			$baseLink = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'baseUrl')).
				'index.php?id='.
				intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pidInputform'));
				
			$link = $baseLink.
					'&'.
					$this->extKey.'[auth]='.$hash.
					'&'.
					$this->extKey.'[lecture]='.$row['uid'];
			
			
			// create links
			array_push	(	$lectureArr,
							$row['name'].":\n".$link."\n"
						);
		}
		return implode("\n",$lectureArr);
	}
	
	function sendLecturerNotification ($lecturer, $mailBody) {
		$content = '';
		$lecturerInputArr = array ();
		
		if ($lecturer==0) {
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT tx_fsmivkrit_lecturer.uid as uid
												FROM tx_fsmivkrit_lecturer, tx_fsmivkrit_lecture
												WHERE tx_fsmivkrit_lecturer.deleted=0 AND tx_fsmivkrit_lecturer.hidden=0
												AND tx_fsmivkrit_lecture.survey=\''.$this->survey.'\'
												AND tx_fsmivkrit_lecture.lecturer=tx_fsmivkrit_lecturer.uid
												GROUP BY tx_fsmivkrit_lecturer.uid');
			while ($res && $row = mysql_fetch_assoc($res))
				array_push($lecturerInputArr,$row['uid']);
		}
		else
			array_push($lecturerInputArr,$lecturer);

		foreach($lecturerInputArr as $lecturer) {
			$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lecturer);
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
													FROM tx_fsmivkrit_lecture 
													WHERE deleted=0 AND hidden=0
													AND survey=\''.$this->survey.'\'
													AND lecturer=\''.$lecturer.'\'');
													
			// now start writing mail
			$mailContent = '';
			$mailContent .= $this->printLecturerNotificationHead($lecturer);
			$mailContent .= $mailBody;
			$mailContent .= "\n\n".$this->printLecturerNotificationInputlinks($lecturer); // remember: survey is set
			$mailContent .= "\n\nVielen Dank,\n   das V-Krit Team der Fachschaft Mathematik/Informatik";

			$send = $this->cObj->sendNotifyEmail(
				$msg='Eintragung Veranstaltungskritik'."\n". // first line is subject
						$mailContent, 
				$recipients=$lecturerUID['email'], 
				$cc='', 
				$email_from='criticus@uni-paderborn.de', 
				$email_fromName='', 
				$replyTo='');
				
			if ($send) {
				$content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_OK,
							'Senden der E-mail an <b>'.$lecturerUID['forename'].' '.$lecturerUID['name'].'</b> erfolgreich.');
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery  ( 'tx_fsmivkrit_lecture',
											'lecturer=\''.$lecturerUID['uid'].'\' 
											AND survey=\''.$this->survey.'\'
											AND eval_state=\''.self::kEVAL_STATE_CREATED.'\'',
											array(
												'eval_state' => self::kEVAL_STATE_NOTIFIED)
											);
							
			} else
				$content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_ERROR,
							'Senden der E-mail an <b>'.$lecturerUID['forename'].' '.$lecturerUID['name'].'</b> fehlgeschlagen.');
		}
		return($content);	
	}
	
	/**
	 * To prevent unauthorized modification of lecture contents, we put a secred hash value on each lecture.
	 * Function returns hash value if there is any set.
	 * @param $lecture UID of lecture
	 * @return $hash string with hash value
	 */
	function createLectureAuthenticationHash ($lecture) {
		$lecture = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
				
		// check if already hash value set: if yes, break
		if ($lecture['inputform_verify']!=0)
			return $lecture['inputform_verify'];
			
		$data = $lecture['name'].$lecture['lecturer'].time();
		$hash = hash('md5',$data);
		
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery  ( 'tx_fsmivkrit_lecture',
											'uid=\''.$lecture['uid'].'\'',
											array(
												'inputform_verify' => $hash)
											);

		if (!$res)
			debug('pi2: Update of hash value failed');
		
		return $hash;
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi2/class.tx_fsmivkrit_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi2/class.tx_fsmivkrit_pi2.php']);
}

?>