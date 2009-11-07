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
require_once(t3lib_extMgm::extPath('rlmp_dateselectlib').'class.tx_rlmpdateselectlib.php');

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
	const kLIST						= 1;
	const kNOTIFY_FORM 				= 2;
	const kNOTIFY_SEND 				= 3;
	const kCHANGE_ENABLE_LECTURE	= 4;
	const kASSIGN_EVAL_DATE_FORM	= 5;
	const kASSIGN_EVAL_DATE_SAVE	= 6;
	
	
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
		$this->survey = intval($GETcommands['survey']);require_once(t3lib_extMgm::extPath('fsmi_vkrit').'api/class.tx_fsmivkrit_div.php');
		
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
			case self::kCHANGE_ENABLE_LECTURE: {
				$resLecture = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', intval($GETcommands['lecture']));
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(	
									'tx_fsmivkrit_lecture',
									'uid=\''.intval($GETcommands['lecture']).'\'',
									array (	'crdate' => time(),
											'tstamp' => time(),
											'hidden' => (($resLecture['hidden']+1) % 2)
									));
				if (!$res)
					$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_ERROR,
									'Fehler bei Datenbankzugriff.');
									
				$content .= $this->printLectureList();
				break;
			}
			case self::kASSIGN_EVAL_DATE_FORM: {
				$content .= $this->printLectureEvaldateAssignmentForm(intval($GETcommands['lecture']));
				break;
			}
			case self::kASSIGN_EVAL_DATE_SAVE: {
				$content .= $this->saveLectureEvaldataAssignment(intval($GETcommands['lecture']));
				$content .= $this->printLectureList();
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
												WHERE deleted=0
												AND eval_state BETWEEN 0 AND 2
												AND survey=\''.$this->survey.'\'');
		// lectures within process 0-2
		// print head
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)>0) {
			$content .= '<h3>Vorlesungen in Vorbereitung</h3>';
			$content .= '<table cellpadding="5" cellspacing="2" class="fsmivkrit">';
			$content .= '<tr><th>Status</th><th>Veranstaltung</th><th>Dozent</th><th>Erinnerungsmail</th>';
				
			while ($res && $row = mysql_fetch_assoc($res)) {
				// get lecturer name
				$resLecturer = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $row['lecturer']);
				
				if ($row['hidden']==0)
					$content .= '<tr class="fsmivkrit_state_'.$row['eval_state'].'">';
				if ($row['hidden']==1)
					$content .= '<tr style="background-color: #ddd; font-style: italic;">';
					
				// lecture and lecture activation state
				$lectureActivation = array ();
				$lectureActivation[0] = tx_fsmivkrit_div::imgPath.'enabled.png';
				$lectureActivation[1] = tx_fsmivkrit_div::imgPath.'disabled.png';
				$content .= '	<td width="50">'.($row['eval_state']).'</td>
								<td width="200">'.
									$this->pi_linkTP('<img src="'.$lectureActivation[$row['hidden']].'" />', 
									array (	$this->extKey.'[type]' => self::kCHANGE_ENABLE_LECTURE,
											$this->extKey.'[survey]' => $this->survey,
											$this->extKey.'[lecture]' => $row['uid'])
								).
								' '.$row['name'].'</td>
								<td width="200"><a href="mailto:'.$resLecturer['forename'].' '.$resLecturer['name'].'<'.$resLecturer['email'].'>?subject=Veranstaltungskritik">'.
									$resLecturer['name'].', '.$resLecturer['forename'].'</a></td>';
				// if no lecturer input, yet: notification option
				if ($row['eval_state']<self::kEVAL_STATE_COMPLETED)
					$content .= '<td width="200">'.$this->pi_linkTP('erinnern', 
									array (	$this->extKey.'[type]' => self::kNOTIFY_FORM,
											$this->extKey.'[survey]' => $this->survey,
											$this->extKey.'[lecturer]' => $resLecturer['uid'])).
								'</td>';
				// else: next step should be to assign evaluation date
				else
					$content .= '<td width="200">'.$this->pi_linkTP('moderieren',
									array ( $this->extKey.'[type]' => self::kASSIGN_EVAL_DATE_FORM,
											$this->extKey.'[survey]' => $this->survey,
											$this->extKey.'[lecture]' => $row['uid'])).
								'</td>';
				$content .= '</tr>';
				
			}
			$content .= '</table>';
		}
		
		// lectures in process 3-5
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
												FROM tx_fsmivkrit_lecture 
												WHERE deleted=0
												AND eval_state BETWEEN 3 AND 5
												AND survey=\''.$this->survey.'\'
												ORDER BY no_eval, eval_date_fixed, name');
		// lectures within process 0-2
		// print head
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)>0) {
			$content .= '<h3>Vorlesungen in Evaluation</h3>';
			$content .= '<table cellpadding="5" cellspacing="2" class="fsmivkrit">';
			$content .= '<tr><th>Status</th><th>Veranstaltung</th><th>Dozent</th><th>VKrit Termin</th><th>Bearbeiten</th>';
				
			while ($res && $row = mysql_fetch_assoc($res)) {
				// get lecturer name
				$resLecturer = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $row['lecturer']);
				
				if ($row['hidden']==0)
					$content .= '<tr class="fsmivkrit_state_'.$row['eval_state'].'">';
				if ($row['hidden']==1)
					$content .= '<tr style="background-color: #ddd; font-style: italic;">';
					
				// lecture and lecture activation state
				$lectureActivation = array ();
				$lectureActivation[0] = tx_fsmivkrit_div::imgPath.'enabled.png';
				$lectureActivation[1] = tx_fsmivkrit_div::imgPath.'disabled.png';
				$content .= '	<td width="50">'.($row['eval_state']).'</td>
								<td width="200">'.
									$this->pi_linkTP('<img src="'.$lectureActivation[$row['hidden']].'" />', 
									array (	$this->extKey.'[type]' => self::kCHANGE_ENABLE_LECTURE,
											$this->extKey.'[survey]' => $this->survey,
											$this->extKey.'[lecture]' => $row['uid'])
								).
								' '.$row['name'].'</td>
								<td width="200"><a href="mailto:'.$resLecturer['forename'].' '.$resLecturer['name'].'<'.$resLecturer['email'].'>?subject=Veranstaltungskritik">'.
									$resLecturer['name'].', '.$resLecturer['forename'].'</a></td>';
									
				// eval date
				if ($row['no_eval']==0)
					$content .= '	<td width="100">'.date('d.m.y - H:i',$row['eval_date_fixed']).'</td>';
				else
					$content .= '	<td width="100">keine Evaluation</td>';
					
				//TODO this does not work: change to check if there is such a lecture!
				if ($row['eval_state']<self::kEVAL_STATE_COMPLETED)
					$content .= '<td width="100">'.$this->pi_linkTP('erinnern', 
									array (	$this->extKey.'[type]' => self::kNOTIFY_FORM,
											$this->extKey.'[survey]' => $this->survey,
											$this->extKey.'[lecturer]' => $resLecturer['uid'])).
								'</td>';
				else
					$content .= '<td width="100">'.$this->pi_linkTP('moderieren',
									array ( $this->extKey.'[type]' => self::kASSIGN_EVAL_DATE_FORM,
											$this->extKey.'[survey]' => $this->survey,
											$this->extKey.'[lecture]' => $row['uid'])).
								'</td>';
				$content .= '</tr>';
				
			}
			$content .= '</table>';
		}
		
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
Sollte Ihre Veranstaltung unter 10 Teilnehmer haben und die Veranstaltung
soll dennoch evaluiert werden, so tragen Sie dieses bitte als Kommentar
ein.

Bei Rückfragen melden Sie sich bitte bei criticus@uni-paderborn.de. 
Dieses Jahr verwenden wir erstmals einen Datenimport aus PAUL. Dieser
ist jedoch noch nicht automatisiert zu verarbeiten und erfordert einen 
hohen manuellen Aufwand. Sollten bei dieser Verarbeitung Fehler 
aufgetreten sein, was sich insbesondere in nicht aufgeführten 
Veranstaltungen widerspiegelt, so teilen Sie uns dieses bitte umgehend 
mit.</textarea></div>
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
	
	/**
	 * Function requires a lecturer ID as target for notification mail. Also the mail body is needed.
	 * While sending this function creates a hash value (if not yet existing) that
	 * will be used as identification for lecturer. 
	 * @param $lecturer integer
	 * @param $mailBody text
	 * @return answer if mail was sent or not
	 */
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
				t3lib_div::sysLog (
					'Sent notification mail to '.$lecturerUID['name'].'.',
					$this->extKey);
							
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
	 * Current survey is selected from class variable
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
	
	function printLectureEvaldateAssignmentForm ($lecture) {
		$content = '';
		
		// TODO preset selection
		
		// the user probably wants to have a way out:
		$content .= '<div style="margin:10px;"><strong>'.$this->pi_linkTP('Eingabe abbrechen!', 
						array (	
							$this->extKey.'[type]' => self::kLIST,
							$this->extKey.'[survey]' => $this->survey
						)).'</strong></div>';
		
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lectureUID['lecturer']);
		$surveyUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $this->survey);

		// head information
		$content .= '<h3>Allgemeine Daten</h3>';
		$content .= '<ul>'.
					'<li><strong>Veranstaltung:</strong> '.$lectureUID['name'].'</li>'.
					'<li><strong>PAUL-ID:</strong> '.$lectureUID['foreign_id'].'</li>'.
					'<li><strong>Dozent:</strong> '.$lecturerUID['name'].', '.$lecturerUID['forename'].'</li>'.
					'<li><strong>Teilnehmer:</strong> '.$lectureUID['participants'].'</li>'.
					'</ul>';
		$content .= '<pre>'.$lectureUID['comment'].'</pre>';
		
		$content .= '<h3>Evaluationstermin</h3>';
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';

		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value='.self::kASSIGN_EVAL_DATE_SAVE.' />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[lecture]'.'" value="'.$lecture.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[survey]'.'" value="'.$this->survey.'" />';
		
		$content .= '<fieldset>';
		// here all three input fields and one additional ...
		for ($i=1; $i<=3; $i++) {
			$content .= '<input type="radio" name="'.$this->extKey.'[eval_date_choice]" 
							id="'.$this->extKey.'_eval_date_choice_'.$i.'" value="'.$i.'" />'."\n";
			$content .= '<label for="'.$this->extKey.'_eval_date_choice_'.$i.'">'.date('d.m.Y - H:i',$lectureUID['eval_date_'.$i]).'</label>'."<br />\n";
		}

		tx_rlmpdateselectlib::includeLib();		// invoke calender frontend library
		// configure Date Selector
		$dateSelectorConf = array (
   			'calConf.' => array (
     		'dateTimeFormat' => 'dd.mm.y',
    		'inputFieldDateTimeFormat' => '%d.%m.%Y'
			)
		);
		
		$content .= '<input type="radio" name="'.$this->extKey.'[eval_date_choice]" id="'.$this->extKey.'_eval_date_choice" value="4" />'."\n";
		$content .= '<label for="'.$this->extKey.'_eval_date_choice_4">Anderes Datum:</label>'."\n";
		$content .= '<div style="margin-left:20px"><table><tr>
						<td><label for="'.$this->extKey.'_eval_date">Datum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_date]" id="'.$this->extKey.'_eval_date"  	
								value="'.htmlspecialchars($this->piVars["eval_date"]).'" size="10" />'.
								tx_rlmpdateselectlib::getInputButton ($this->extKey.'_eval_date',$dateSelectorConf).
					'</td></tr>
					<tr><td>'.
						'<label for="'.$this->extKey.'_eval_time">Uhrzeit:</label></td>
						<td><select type="text" name="'.$this->extKey.'[eval_time]" id="'.$this->extKey.'_eval_time">'.
								tx_fsmivkrit_div::printOptionListTime($this->piVars["eval_time"]).
						'</select>	
					</td></tr>
					<tr><td>
						 <label for="'.$this->extKey.'_eval_room">Raum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_room]" id="'.$this->extKey.'_eval_room"  	
								value="'.htmlspecialchars($this->piVars["eval_room"]).'" />			
					</td></tr></table></div>';
								
		$content .= '<input type="radio" name="'.$this->extKey.'[eval_date_choice]" 
							id="'.$this->extKey.'_eval_date_choice_5" value="5" />'."\n";
		$content .= '<label for="'.$this->extKey.'_eval_date_choice_5">Keine Evaluation</label>'."<br />\n";
		$content .= '</fieldset>';
								
		$content .= '<input type="checkbox" name="'.$this->extKey.'[notify_lecturer]" id="'.$this->extKey.'_notify_lecturer" checked="checked" />'."\n";
		$content .= '<label for="'.$this->extKey.'_notify_lecturer">Dozenten informieren (Dozent erhält beim Speichern E-mail)</label><br />'."\n";	
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]" 
				value="'.htmlspecialchars('Speichern').'">';
		$content .= '</form>';

		return $content;
	}
	
	function saveLectureEvaldataAssignment($lecture) {
		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$evalDateChoice = intval($GETcommands['eval_date_choice']);
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		
		// this means: we have any of the preset dates from the lecturers
		if ($evalDateChoice < 4 && $evalDateChoice >= 0) {
			// update Lecture
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(	
										'tx_fsmivkrit_lecture',
										'uid=\''.$lecture.'\'',
										array (	'crdate' => time(),
												'tstamp' => time(),
												'eval_date_fixed' 	=> $lectureUID['eval_date_'.$evalDateChoice],
												'eval_room_fixed'	=> $lectureUID['eval_room_'.$evalDateChoice],
												'no_eval'		=> 0,
												'eval_state'	=> self::kEVAL_STATE_APPROVED,
										));
			if (!$res) {
				return $content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_ERROR,
								'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.');
			}
			else {
				$content .= $this->sendEvaldateSetMail($lecture);
				return $content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_INFO,
								'Evaluationstermin für '.$lectureUID['name'].' wurde auf den <strong>'.
								date('j.m. H:i', $lectureUID['eval_date_'.$evalDateChoice]).
								'</strong> festgelegt.');
			}
		}
		
		// this means: the organizer sets his own wish
		if ($evalDateChoice == 4) {
			// compute date
			$evalDate = strtotime( htmlspecialchars($POSTdata['eval_date']).' '.htmlspecialchars($POSTdata['eval_time']).':00');
			
			// update Lecture
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(	
										'tx_fsmivkrit_lecture',
										'uid=\''.$lecture.'\'',
										array (	'crdate' => time(),
												'tstamp' => time(),
												'eval_date_fixed' 	=> $evalDate,
												'eval_room_fixed'	=> strip_tags($GETcommands['eval_room']),
												'no_eval'			=> 0,
												'eval_state'		=> self::kEVAL_STATE_APPROVED,
										));
			if (!$res) {
				return $content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_ERROR,
								'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.');
			}
			else {
				$content .= $this->sendEvaldateSetMail($lecture);
				return $content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_INFO,
								'Evaluationstermin für '.$lectureUID['name'].' wurde auf den <strong>'.
								date('j.m. H:i', $lectureUID['eval_date_'.$evalDateChoice]).
								'</strong> festgelegt.');
			}
								
			return $content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_ERROR,
								'Fehlerhafte Auswahl bei Evaluationstermin. Konnte Termin nicht speichern.');
		}
		
		// this means: no evaluation for this lecture
		if ($evalDateChoice == 5) {
			// compute date
			$evalDate = strtotime( htmlspecialchars($POSTdata['eval_date']).' '.htmlspecialchars($POSTdata['eval_time']).':00');
			
			// update Lecture
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(	
										'tx_fsmivkrit_lecture',
										'uid=\''.$lecture.'\'',
										array (	'crdate'		=> time(),
												'tstamp' 		=> time(),
												'no_eval' 		=> 1,
												'eval_state'	=> self::kEVAL_STATE_APPROVED,
										));
			if (!$res)
				return $content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_ERROR,
								'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.');
			else 
				return $content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_INFO,
								'Vorlesung wurde mit <strong>"keine Evaluation durchführen"</strong> gekennzeichnet.');
		}
	
		
	}
	
	function sendEvaldateSetMail ($lecture) {
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lectureUID['lecturer']);
		
		// check if mail shall really be sent
		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		if (intval($GETcommands['notify_lecturer'])!=1)
			return;
		
		// now start writing mail
		$mailContent = '';
		$mailContent .= $this->printLecturerNotificationHead($lecturer);
		$mailContent .= 'als Termin für die Evaluation Ihrer Veranstaltung '."\n".
						'   '.$lecture['name']."\n".
						'wurde folgender Termin festgelegt:'."\n". 
						'   '.date('d.m.y - H:i',$lecture['eval_date_fixed']);
		$mailContent .= "\n\nVielen Dank,\n   das V-Krit Team der Fachschaft Mathematik/Informatik";

		$send = $this->cObj->sendNotifyEmail(
				$msg='Termin für Veranstaltungskritik wurde festgelegt'."\n". // first line is subject
					$mailContent, 
				$recipients=$lecturerUID['email'], 
				$cc='', 
				$email_from='criticus@uni-paderborn.de', 
				$email_fromName='', 
				$replyTo='');
				
		return tx_fsmivkrit_div::printSystemMessage(
						tx_fsmivkrit_div::kSTATUS_INFO,
						'Info Mail wurde an Dozenten versandt.');
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi2/class.tx_fsmivkrit_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi2/class.tx_fsmivkrit_pi2.php']);
}

?>