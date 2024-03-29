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
include_once(t3lib_extMgm::siteRelPath('date2cal').'/src/class.jscalendar.php');


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
    var $emailOrganizer;
    var $emailHelper;

    var $lecture_type = array(
		'Fach-Veranstaltung', 
		'Service-Veranstaltung', 
		'Didaktik-Veranstaltung'
	);

	// options for this frontend plugin
	const kVERIFY	= 1;
	const kSAVE		= 2;

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

		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$lecture = intval($GETcommands['lecture']);
		$hash = htmlspecialchars($GETcommands['auth']);

        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fsmi_vkrit']);
        $this->emailOrganizer = ($confArr['emailHelper'] ? $confArr['emailHelper'] : 'organizer@nomail.com');   
        $this->emailHelper = ($confArr['emailHelper'] ? $confArr['emailHelper'] : 'helper@nomail.com');

		$content .= '<h1>Dateneingabe für Veranstaltungen</h1>';

		switch (intval($GETcommands['type'])) {
			case self::kVERIFY: {
				if ($lecture==0) {
					$content .= tx_fsmivkrit_div::printSystemMessage(
										tx_fsmivkrit_div::kSTATUS_ERROR,
										'Die von Ihnen verwendete URL ist nicht korrekt. Es fehlt die Angabe einer Veranstaltung.');
					break;
				}

				$content .= '<h2>Eingabe bestätigen</h2>';
				$content .= $this->printInputValuesToCheck($lecture,$hash);
				break;
			}
			case self::kSAVE: {
				if ($lecture==0) {
					$content .= tx_fsmivkrit_div::printSystemMessage(
										tx_fsmivkrit_div::kSTATUS_ERROR,
										'Die von Ihnen verwendete URL ist nicht korrekt. Es fehlt die Angabe einer Veranstaltung.<br />
										Bitte kopieren Sie den vollständigen Link aus Ihrem E-Mail Programm in die
										Adressleiste ihres Browsers.');
					break;
				}


				if ($GETcommands['submit_button']) {
					$content .= '<h2>Eingabe abgeschlossen</h2>';
					$content .= $this->saveInputValues($lecture,$hash);
				}
				else {
					$content .= '<h2>Daten ändern</h2>';
					$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
					$this->fillPiVarsWithPostValues($GETcommands);
					$content .= $this->printInputForm($lecture, $hash);
				}
				break;
			}
			default: {
				// crucial:
				// if this is not here the lecturer gets no notification that its data totally screwed up, cause the input lecuture does not exist
				// reasion for this is e.g. Thunderbird that does not include GET arguments.
				if ($lecture==0) {
					$content .= tx_fsmivkrit_div::printSystemMessage(
										tx_fsmivkrit_div::kSTATUS_ERROR,
										'Die von Ihnen verwendete URL ist nicht korrekt. Es fehlt die Angabe einer Veranstaltung.');
					break;
				}

				$content .= '<h2>Daten eingeben</h2>';
				$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);

				switch ($lectureUID['eval_state']) {
					case tx_fsmivkrit_div::kEVAL_STATE_NOTIFIED: {
						$content .= $this->printInputForm($lecture, $hash);
						break;
					}
					case tx_fsmivkrit_div::kEVAL_STATE_CREATED: {
						$content .= $this->printInputForm($lecture, $hash);
						break;
						}
					case tx_fsmivkrit_div::kEVAL_STATE_COMPLETED: {
						$content .= tx_fsmivkrit_div::printSystemMessage(
										tx_fsmivkrit_div::kSTATUS_WARNING,
										'Für die Vorlesung <strong>'.$lectureUID['name'].'</strong> wurden bereits Daten eingegeben.');
//						$content .= '<div>Sie können die ensprechenden Einträge an dieser Stelle ändern:</div>';
						$this->fillPiVarsWithDBValues($lecture);

						$content .= $this->printInputForm($lecture, $hash);
						break;
					}
					case tx_fsmivkrit_div::kEVAL_STATE_APPROVED: {
						$content .= tx_fsmivkrit_div::printSystemMessage(
										tx_fsmivkrit_div::kSTATUS_ERROR,
										'Die Eintragung für die Vorlesung <strong>'.$lectureUID['name'].'</strong> wurde gesperrt.');
						$content .= '<div>Das Orga-Team hat die Eintragung für diesen Datensatz gesperrt. Grund ist die bereits erfolgte
										Festlegung auf einen Evaluationstermin.<br />
										Bei Fragen wenden Sie sich bitte direkt an <a href="mailto:'.$this->emailOrganizer.'">'.$this->emailOrganizer.'</a>.</div>';
						break;
					}
					default: { // same as before, but to not get confused... again here
						$content .= tx_fsmivkrit_div::printSystemMessage(
										tx_fsmivkrit_div::kSTATUS_ERROR,
										'Die Eintragung für die Vorlesung <strong>'.$lectureUID['name'].'</strong> wurde gesperrt.');
						$content .= '<div>Das Orga-Team hat die Eintragung für diesen Datensatz gesperrt. Grund ist die bereits erfolgte
										Festlegung auf einen Evaluationstermin.<br />
										Bei Fragen wenden Sie sich bitte direkt an <a href="mailto:'.$this->emailOrganizer.'">'.$this->emailOrganizer.'</a>.</div>';
						break;
					}
				}
			}
		}

		return $this->pi_wrapInBaseClass($content);
	}

	function printInputForm ($lecture, $hash) {
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
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="post" enctype="multipart/form-data" name="'.$this->extKey.'">';

		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value="'.self::kVERIFY.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[auth]'.'" value="'.$hash.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[lecture]'.'" value="'.$lecture.'" />';

		$content .= '<fieldset><legend>Vorlesung</legend><table>';
		// participants
		$content .= '<tr>
						<td><label for="'.$this->extKey.'_participants">Vorlesungsteilnehmer:</label></td>
						<td><input type="text" name="'.$this->extKey.'[participants]" size="3" id="'.$this->extKey.'_participants"
								value="'.htmlspecialchars($this->piVars["participants"]).'" /></td>
					</tr>';
		$content .= '<tr>
						<td><label for="'.$this->extKey.'_lecture_type">Veranstaltungsart:</label></td>
						<td><select name="'.$this->extKey.'[lecture_type]" id="'.$this->extKey.'_lecture_type">';
		for ($i=0; $i<3; $i++) {
			$selected = ($lectureUID["lecture_type"]==$i ? 'selected="selected"' : '');
			$content .= '<option value="'.$i.'" '.$selected.'>'.$this->lecture_type[$i].'</option>';
		}
		$content .= '</select></td></tr>';
		// assistents
		$content .= '<tr>
						<td style="vertical-align:top"><label for="'.$this->extKey.'_assistants">Tutoren:</label></td>
						<td><div>Exakt <strong>einen Tutor pro Zeile</strong> eintragen "Nachname,Vorname,Titel", mit Komma (,) trennen. Jeden Tutor bitte genau einmal eingeben, auch wenn
							von diesem mehrere Übungsgruppen betreut werden.<br />
							Beispiel: "Musterfrau,Maria,Dr." bzw. "Mustermann,Max"</div>
							<textarea name="'.$this->extKey.'[assistants]" id="'.$this->extKey.'_assistants" cols="74" rows="15">'.
							$this->piVars['assistants'].
							'</textarea></td>
					</tr>'; //TODO make selector

		$content .= '</table></fieldset>';

		// init jscalendar class
		$JSCalendar = JSCalendar::getInstance();
		$JSCalendar->setDateFormat(false, '%d-%m-%Y');		// do not display time
		$JSCalendar->setNLP($this->extConfig['natLangParser']);
		$JSCalendar->setCSS($this->extConfig['calendarCSS']);
		$JSCalendar->setLanguage($this->extConfig['lang']);

		//TODO think about what to display if only one value is selected
		if ($surveyUID['eval_start']!=0 && $surveyUID['eval_end']!=0)
			$content .= '<h3>Evaluation findet statt vom '.date('j. F',$surveyUID['eval_start']).' bis '.date('j. F',$surveyUID['eval_end']).'</h3>';

		// Vkrit suggestion 1
		if ($this->piVars['eval_date_1']=='01-01-1970')
			$this->piVars['eval_date_1'] = '';
		$JSCalendar->setInputField($this->extKey.'_eval_time_1');
		$content .= '<fieldset>';
		$content .= '<legend>V-Krit-Termin Vorschlag 1:</legend>';
		$content .= '<table><tr>
						<td><label for="'.$this->extKey.'_eval_date_1">Datum:</label></td>
						<td>'.
						$JSCalendar->render(
							$this->piVars['eval_date_1'],
							$this->extKey.'[eval_date_1]'
						).
					'</td></tr>
					<tr><td>'.
						'<label for="'.$this->extKey.'_eval_time_1">Uhrzeit:</label></td>
						<td><select type="text" name="'.$this->extKey.'[eval_time_1]" id="'.$this->extKey.'_eval_time_1">'.
								tx_fsmivkrit_div::printOptionListTime($this->piVars["eval_time_1"]).
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
		if ($this->piVars['eval_date_2']=='01-01-1970')
			$this->piVars['eval_date_2'] = '';
		$JSCalendar->setInputField($this->extKey.'_eval_time_2');
		$content .= '<fieldset>';
		$content .= '<legend>V-Krit-Termin Vorschlag 2 (optional):</legend>';
		$content .= '<table><tr>
						<td><label for="'.$this->extKey.'_eval_date_2">Datum:</label></td>
						<td>'.
						$JSCalendar->render(
							$this->piVars['eval_date_2'],
							$this->extKey.'[eval_date_2]'
						).
					'</td></tr>
					<tr><td>'.
						'<label for="'.$this->extKey.'_eval_time_2">Uhrzeit:</label></td>
						<td><select type="text" name="'.$this->extKey.'[eval_time_2]" id="'.$this->extKey.'_eval_time_2">'.
								tx_fsmivkrit_div::printOptionListTime($this->piVars["eval_time_2"]).
						'</select>
					</td></tr>
					<tr><td>
						 <label for="'.$this->extKey.'_eval_room_2">Raum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_room_2]" id="'.$this->extKey.'_eval_room_2"
								value="'.htmlspecialchars($this->piVars["eval_room_2"]).'" />
					</td></tr></table></fieldset>';

		// Vkrit suggestion 3
		if ($this->piVars['eval_date_3']=='01-01-1970')
			$this->piVars['eval_date_3'] = '';
		$JSCalendar->setInputField($this->extKey.'_eval_time_3');
		$content .= '<fieldset>';
		$content .= '<legend>V-Krit-Termin Vorschlag 3 (optional):</legend>';
		$content .= '<table><tr>
						<td><label for="'.$this->extKey.'_eval_date_3">Datum:</label></td>
						<td>'.
						$JSCalendar->render(
							$this->piVars['eval_date_3'],
							$this->extKey.'[eval_date_3]'
						).
					'</td></tr>
					<tr><td>'.
						'<label for="'.$this->extKey.'_eval_time_3">Uhrzeit:</label></td>
						<td><select type="text" name="'.$this->extKey.'[eval_time_3]" id="'.$this->extKey.'_eval_time_3">'.
								tx_fsmivkrit_div::printOptionListTime($this->piVars["eval_time_3"]).
						'</select>
					</td></tr>
					<tr><td>
						 <label for="'.$this->extKey.'_eval_room_3">Raum:</label></td>
						<td><input type="text" name="'.$this->extKey.'[eval_room_3]" id="'.$this->extKey.'_eval_room_3"
								value="'.htmlspecialchars($this->piVars["eval_room_3"]).'" />
					</td></tr></table></fieldset>';

		// reshipment of results
		$content .= '<fieldset>';
		$content .= '<legend>Zustellung der Ergebnisse</legend';
		$content .= '<input type="radio" '.($this->piVars['reshipment']==tx_fsmivkrit_div::kEVAL_RESHIPMENT_EMAIL? 'checked="checked"':'').'
			name="'.$this->extKey.'[reshipment]" value="'.tx_fsmivkrit_div::kEVAL_RESHIPMENT_EMAIL.'"> Zusendung per E-Mail<br />';
		$content .= '<input type="radio" '.($this->piVars['reshipment']==tx_fsmivkrit_div::kEVAL_RESHIPMENT_MAIL? 'checked="checked"':'').'
			name="'.$this->extKey.'[reshipment]" value="'.tx_fsmivkrit_div::kEVAL_RESHIPMENT_MAIL.'"> per Hauspost<br />';
		$content .= '</fieldset>';

		// comment input
		$content .= '<fieldset>';
		$content .= '<legend>Ergänzende Informationen:</legend>';
		$content .= '<textarea name="'.$this->extKey.'[comment]" cols="74" id="'.$this->extKey.'_comment">'.
			$this->piVars["comment"].'</textarea></fieldset>';

		// submit button
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]"
				value="'.htmlspecialchars('weiter zum Daten überprüfen').'">';

		$content .= '</form>';

		// get initialisation code of the calendar
		if (($jsCode = $JSCalendar->getMainJS()) != '') {
			$GLOBALS['TSFE']->additionalHeaderData['fsmivkrit_date2cal'] = $jsCode;
		}

		return $content;
	}

	function printInputValuesToCheck ($lecture,$hash) {
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lectureUID['lecturer']);
		$surveyUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $lectureUID['survey']);

		$inputData = $this->getInputValuesToArray ();

		$content = '';

		$content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_WARNING,
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
		
		$content .= '<div><strong>Veranstaltungsart:</strong> '.$this->lecture_type[$inputData['lecture_type']].'</div>';

		$content .= '<div><strong>Tutoren:</strong></div>';
		$content .= '<ol>';
		foreach ($inputData['assistants'] as $tutor) {
			if ($tutor[0]=='' && $tutor[1]=='')
				continue;
			if (!isset($tutor[2]))
				$content .= '<li>'.trim($tutor[0]).', '.trim($tutor[1]).'</li>'."\n";
			else
				$content .= '<li>'.trim($tutor[0]).', '.trim($tutor[1]).', '.trim($tutor[2]).'</li>'."\n";

			// check for comma count
			if (count($tutor)>3)
				$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_WARNING,
									'Bitte überprüfen Sie, ob Tutor korrekt angegeben wurde: in entsprechender Zeile wurden mehr als zwei Komma angegeben.');
		}
		$content .= '</ol>';

		$content .= '<div><strong>V-Krit Termine:</strong></div>';
		$content .= '<ol>';
		for($i=1; $i<=3; $i++) {
			if ($inputData['eval_'.$i]['date']<=21600)
				continue;

			$content .= '<li><strong>Termin:</strong> '.date('d.m.Y H:i', $inputData['eval_'.$i]['date']).',
						<strong>Raum:</strong> '.$inputData['eval_'.$i]['room'].'</li>';
			if ( 	($inputData['eval_'.$i]['date']<$surveyUID['eval_start'])
						||
					($surveyUID['eval_end']!=0 && $inputData['eval_'.$i]['date']> $surveyUID['eval_end'] + 24*60*60)	// addition is one additional day
				)
				$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_WARNING,
									'Der vorgeschlagene Termin liegt außerhalb des Evaluationszeitraumes.
									Der Zeitraum ist '.date('j. F',$surveyUID['eval_start']).' bis '.date('j. F',$surveyUID['eval_end']).'.');

		}
		$content .= '</ol>';

		$content .= '<div><strong>Zusendung der Ergebnisse: </strong> per '.
			($inputData['reshipment']==tx_fsmivkrit_div::kEVAL_RESHIPMENT_EMAIL? 'E-Mail' : 'Hauspost' ).
			'</div>';

		$content .= '<div><strong>Kommentar/Ergänzung</strong></div>';
		$content .= '<pre>'.$inputData['comment'].'</pre>';

		// save everything in session
		$GLOBALS['TSFE']->fe_user->setKey('ses','inputData', $inputData);

		$content .= '<h3>Daten übermitteln</h3>';
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="post" enctype="multipart/form-data" name="'.$this->extKey.'">';

		// TODO should be switched to sessions, to dangerous for data loss cause of typos
		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value="'.self::kSAVE.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[auth]'.'" value="'.$hash.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[lecture]'.'" value="'.$lecture.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[lecture_type]'.'" value="'.$inputData['lecture_type'].'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[participants]'.'" value="'.$inputData['participants'].'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[reshipment]'.'" value="'.$inputData['reshipment'].'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[comment]'.'" value="'.$inputData['comment'].'" />';

		$assistants = array ();
		foreach($inputData['assistants'] as $tutor)
			array_push($assistants,implode(',',$tutor));
		$content .= '<input type="hidden" name="'.$this->extKey.'[assistants]'.'" value="'.implode("\n",$assistants).'" />';

		for ($i=1; $i<=3; $i++) {
			$content .= '<input type="hidden" name="'.$this->extKey.'[eval_date_'.$i.']" value="'.date('d-m-Y',$inputData["eval_".$i]['date']).'" />';
			$content .= '<input type="hidden" name="'.$this->extKey.'[eval_time_'.$i.']" value="'.date('H:i',$inputData["eval_".$i]['date']).'" />';
			$content .= '<input type="hidden" name="'.$this->extKey.'[eval_room_'.$i.']" value="'.$inputData["eval_".$i]['room'].'" />';
		}


		$content .= '<input type="submit" name="'.$this->extKey.'[back_button]"
				value="'.htmlspecialchars('Eingaben ändern').'"> ';
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]"
				value="'.htmlspecialchars('Daten speichern').'">';

		$content .= '</form>';

		return $content;
	}

	/**
	 * Get all input values, escaped and clean; secure agains all common attacks
	 */
	function getInputValuesToArray () {
		$POSTdata = t3lib_div::_POST($this->extKey);
		$inputData = array ();

		// participants
		$inputData['participants'] = intval($POSTdata['participants']);
		
		// lecture type
		$inputData['lecture_type'] = intval($POSTdata['lecture_type']);

		// assistants
		$tutorArr = explode("\n",htmlspecialchars($POSTdata['assistants']));
		$inputData['assistants'] = array ();
		foreach($tutorArr as $tutor)
			array_push($inputData['assistants'],explode(',',$tutor));

		// vkrit dates
		for ($i=1; $i<=3; $i++) {
			$inputData['eval_'.$i]['room'] = htmlspecialchars($POSTdata['eval_room_'.$i]);

			if ($POSTdata['eval_date_'.$i]=='')
				continue;

			$inputData['eval_'.$i]['date'] = strtotime(
						htmlspecialchars($POSTdata['eval_date_'.$i]).' '.htmlspecialchars($POSTdata['eval_time_'.$i]).':00');
		}

		$inputData['reshipment'] = intval($POSTdata['reshipment']);
		$inputData['comment'] = htmlspecialchars($POSTdata['comment']);

		return $inputData;
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
//	TODO useless here, lecturer cannot modify dates anymore
//		// inform if eval-date is not in survey time periode
//		for ($i=1; $i<=3; $i++) {
//			if (
//				($inputData['eval_'.$i]['date'] < $surveyUID['eval_start'] ||
//				$inputData['eval_'.$i]['date'] > $surveyUID['eval_end'] )
//				&&
//				$inputData['eval_'.$i]['date'] != 0
//			) {
//				$content .= tx_fsmivkrit_div::printSystemMessage(
//									tx_fsmivkrit_div::kSTATUS_WARNING,
//									'Im '.$i.'-ten Eingabefeld haben Sie einen Termin angegeben, welcher außerhalb des Evaluationszeitraumes liegt.');
//			}
//		}

		// update Lecture
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
									'tx_fsmivkrit_lecture',
									'uid=\''.$lecture.'\'',
									array (	'tstamp' => time(),
											'participants' 	=> $inputData['participants'],
											'lecture_type'	=> $inputData['lecture_type'],
											'eval_date_1' 	=> $inputData['eval_1']['date'],
											'eval_date_2' 	=> $inputData['eval_2']['date'],
											'eval_date_3'	=> $inputData['eval_3']['date'],
											'eval_room_1'	=> $inputData['eval_1']['room'],
											'eval_room_2'	=> $inputData['eval_2']['room'],
											'eval_room_3'	=> $inputData['eval_3']['room'],
											'eval_state'	=> tx_fsmivkrit_div::kEVAL_STATE_COMPLETED,
											'comment'		=> $inputData['comment']
									));
		if (!$res)
			return tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_ERROR,
							'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.');

		// clear up tutors if lecture maybe was saved before
		// this could be the case if lecturere saves and starts edit again
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
										'tx_fsmivkrit_tutorial','lecture='.$lecture
								);

		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
									'tx_fsmivkrit_lecturer',
									'uid=\''.$lecturerUID['uid'].'\'',
									array (	'tstamp' => time(),
											'reshipment' => $inputData['reshipment']
									));
		if (!$res)
			return tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_ERROR,
							'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.');


		// insert tutorials
		foreach ($inputData['assistants'] as $tutor) {
			if ($tutor[0]=='' && $tutor[1]=='')
				continue;

			if (!isset($tutor[2]))
				$tutor[2]='';

			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
									'tx_fsmivkrit_tutorial',
									array (	'pid' => $lectureUID['pid'],
											'crdate' => time(),
											'tstamp' => time(),
											'assistant_name' => trim($tutor[0]),
											'assistant_forename' => trim($tutor[1]),
											'assistant_title' => trim($tutor[2]),
											'lecture' => $lectureUID['uid'],
									));
			if (!$res)
				return tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_ERROR,
							'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.');

			// set system log
			t3lib_div::sysLog (
					'Lecturer updated data for '.$lectureUID['name'].' ['.$lectureUID['uid'].'].',
					$this->extKey);
		}

		$content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_OK,
							'Daten erfolgreich gespeichert.');

		// give oppurtinity to insert another lecture
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0 AND hidden=0
												AND survey=\''.$surveyUID['uid'].'\'
												AND (eval_state='.tx_fsmivkrit_div::kEVAL_STATE_CREATED.'
													OR eval_state='.tx_fsmivkrit_div::kEVAL_STATE_NOTIFIED.')
												AND lecturer=\''.$lecturerUID['uid'].'\'');
		$lectureArr = array();

		if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res)>0) {
			$content .= '<h3>Eingabe fortsetzen</h3>
				<div>Sie können direkt mit weiteren Eintragungen fortfahren:</div>';
			$content .= '<ul>';

			while ($res && $row = mysql_fetch_assoc($res)) {
				$content .= '<li>'.$this->pi_linkTP('<strong>'.$row['name'].'</strong>',
									array (
										$this->extKey.'[auth]' => $row['inputform_verify'],
										$this->extKey.'[lecture]' => $row['uid']
									)).'</li>';
			}
			$content .= '</ul>';

		} else
			$content .= '<div>Vielen Dank für Ihre Eintragungen.</div>';

		return $content;
	}

	function fillPiVarsWithDBValues ($lecture) {
		$lectureDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);

		// set participants
		if ($this->piVars["participants"]=='' && $lectureDATA['participants']!=0)
			$this->piVars["participants"] = $lectureDATA['participants'];

		// set comment
		if ($this->piVars["comment"]=='' && $lectureDATA['comment']!='')
			$this->piVars["comment"] = $lectureDATA['comment'];

		if ($this->piVars['reshipment']=='' && $lectureDATA['reshipment']!='')
			$this->piVars['reshipment'] = $lectureDATA['reshipment'];

		// set dates
		for ($i=1; $i<=3; $i++) {
			if ($this->piVars["eval_date_".$i]=='' && $lectureDATA['eval_date_'.$i] > 24*60*60)
				$this->piVars["eval_date_".$i] = date('d.m.Y',$lectureDATA['eval_date_'.$i]);

			if ($this->piVars["eval_time_".$i]=='' && $lectureDATA['eval_date_'.$i]!=0)
				$this->piVars["eval_time_".$i] = date('h:i',$lectureDATA['eval_date_'.$i]);

			if ($this->piVars["eval_room_".$i]=='' && $lectureDATA['eval_room_'.$i]!='')
				$this->piVars["eval_room_".$i] = $lectureDATA['eval_room_'.$i];
		}

		if ($this->piVars["assistants"]=='') {
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
													FROM tx_fsmivkrit_tutorial
													WHERE deleted=0 AND hidden=0
													AND lecture=\''.$lectureDATA['uid'].'\'');
			while ($res && $row = mysql_fetch_assoc($res)) {
				$this->piVars["assistants"] .= "\n".$row['assistant_name'].','.$row['assistant_forename'];
			}
		}
	}

	function fillPiVarsWithPostValues ($data) {
		$this->piVars['participants'] = intval($data['participants']);
		$this->piVars['comment'] = strip_tags($data['comment']);
		$this->piVars['reshipment'] = intval($data['reshipment']);
		$this->piVars["assistants"]=strip_tags($data['assistants']);
			// set dates
		for ($i=1; $i<=3; $i++) {
			if ($data['eval_date_'.$i] && $data['eval_date_'.$i]!='01.01.1970')	 // is greater one day?
				$this->piVars["eval_date_".$i] = strip_tags($data['eval_date_'.$i]);
			$this->piVars["eval_time_".$i] = strip_tags($data['eval_time_'.$i]);
			$this->piVars["eval_room_".$i] = strip_tags($data['eval_room_'.$i]);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi1/class.tx_fsmivkrit_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi1/class.tx_fsmivkrit_pi1.php']);
}

?>
