<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Andreas Cord-Landwehr <fsmi@uni-paderborn.de>
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
 * Plugin 'Data Import/Export' for the 'fsmi_vkrit' extension.
 *
 * @author	Andreas Cord-Landwehr <fsmi@uni-paderborn.de>
 * @package	TYPO3
 * @subpackage	tx_fsmivkrit
 */
class tx_fsmivkrit_pi4 extends tslib_pibase {
	var $prefixId      	= 'tx_fsmivkrit_pi4';		// Same as class name
	var $scriptRelPath 	= 'pi4/class.tx_fsmivkrit_pi4.php';	// Path to this script relative to the extension dir.
	var $extKey        	= 'fsmi_vkrit';	// The extension key.
	var $survey			= 0;	// saves the given survey UID, if given
	var $emailOrganizer;
	var $emailHelper;

    var $lecture_type = array(
		'Fach', 
		'Service', 
		'Didaktik'
	);

	// types
	const kIMPORT		= 1;
	const kEXPORT		= 2;

	// csv Columns
	const kCSV_FUNKTION 	= 0;
	const kCSV_ANREDE 		= 1;
	const kCSV_TITEL		= 2;
	const kCSV_VORNAME		= 3;
	const kCSV_NACHNAME		= 4;
	const kCSV_EMAIL		= 5;
	const kCSV_LV_NAME		= 6;
	const kCSV_LV_KENNUNG	= 7;
	const kCSV_LV_ORT		= 8;
	const kCSV_STUDIENGANG	= 9;
	const kCSV_LV_ART		= 10;
	const kCSV_TEILNEHMER	= 11;
	const kCSV_ORGAEINHEIT	= 12;

	// values for sex-field
	const kDB_SEX_UNKNOWN	= 0;
	const kDB_SEX_FEMALE	= 1;
	const kDB_SEX_MALE		= 2;

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

        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fsmi_vkrit']);
        $this->emailOrganizer = ($confArr['emailHelper'] ? $confArr['emailHelper'] : 'organizer@nomail.com');   
        $this->emailHelper = ($confArr['emailHelper'] ? $confArr['emailHelper'] : 'helper@nomail.com');

		$content = '';

		$content .= '<h1>Import and Export of Evaluation Data</h1>';

		// select input type
		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$this->survey = intval($GETcommands['survey']);

		// get selector for survey or if set, show backlink
		if ($this->survey==0) {
			$content .= $this->createSurveyList();
			$content .= '<p>Die entsprechende Umfrage muss über das Backend bereits angelegt worden sein.</p>';
		} else {
			$content .= $this->pi_linkTP('< zurück zur Auswahl', array () );
			$content .= $this->createTypeSelector($this->survey);
		}

		// create main content
		switch (intval($GETcommands['type'])) {
			case self::kIMPORT: {
				$content .= '<h2>Import Data from CSV file</h2>';

				// check for POST data
				if (t3lib_div::_POST($this->extKey)) {
					// form files
					$formDataFiles = $_FILES[$this->extKey];
					
                    // force one character
                    $delimiter = $GETcommands['delimiter'];
                    if (strlen($delimiter)==0 || strlen($delimiter)>1)
                        $delimiter = ',';

					// get files
					if ($GETcommands['file_confirmed']) {
						$csvArray = $this->loadImportData(htmlspecialchars($GETcommands['file_confirmed']), $delimiter);
						t3lib_div::unlink_tempfile($GETcommands['file_confirmed']);
					} else {
						$filepath = t3lib_div::upload_to_tempfile($formDataFiles['tmp_name']['file']);
						$csvArray = $this->loadImportData($filepath, $delimiter);
					}

					// set status message
					if (count($csvArray)==0)
						$content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_ERROR,
								'Leere Datei/konnte Datei nicht lesen');
					else {
						// is there a confirmed filed
						if ($GETcommands['file_confirmed']) {
							$this->saveImportData($csvArray, intval($GETcommands['survey']));
							//TODO check save info output
							$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_OK,
									'Daten gespeichert.');
							$content .= $this->printImportData($csvArray);
						} else {
							$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_WARNING,
									'Datei Eingelesen, aber noch nicht gespeichert!');
							$content .= $this->createImportDataConfirmForm( $filepath, intval($GETcommands['survey']), $delimiter);
							$content .= $this->printImportData($csvArray);
						}
					}
				}
				else
					$content .= $this->createImportDataForm();
				break;
			}
			case self::kEXPORT: {
				$content .= '<h2>Export Data to EvaSys XML Format</h2>';
				$surveyDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $this->survey);
 				$content .= '<div>Datenexport für die Veranstaltung '.$surveyDATA['name'].' '.$surveyDATA['semester'].'</div>';
 				$content .= '<ul>';
//				$content .= tx_fsmivkrit_div::printSystemMessage(
//													tx_fsmivkrit_div::kSTATUS_INFO,
//													'Noch nicht validiert!');
				$content .= '<li>'.$this->storeOutputDataXML($this->createOutputDOM($this->survey)).'</li>';;
				$content .= '<li>'.$this->storeOutputLecturesList($this->survey).'</li>';
				$content .= '</ul>';
				break;
			}
			default:
				break;
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Creates UL list in HTML to display selection of import/export data
	 * \param $survey UID of survey
	 * \return string of output HTML
	 */
	function createTypeSelector ($survey) {
		$content = '<span> | ';
		$content .= $this->pi_linkTP('Import PAUL Data',
								array (
									$this->extKey.'[type]' => self::kIMPORT,
									$this->extKey.'[survey]' => $survey
								));
		$content .= ' | ';
		$content .= $this->pi_linkTP('Export EvaSys Data',
								array (
									$this->extKey.'[type]' => self::kEXPORT,
									$this->extKey.'[survey]' => $survey
								));
		$content .= '</span>';

		return $content;
	}

	/**
	 * This function creates a list of survey elements to access the different
	 * surveys to import or export data from this tool
	 * \return string of HTML code
	 */
	function createSurveyList () {
		$content = '';

		// get all surveys that are not deleted or hidden
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_survey
												WHERE deleted=0 AND hidden=0');
		$content .= '<ul>';
		while ($res && $row = mysql_fetch_assoc($res))
			$content .= '<li>'.$this->pi_linkTP(
											$row['semester'].' - '.$row['name'],
											array( $this->extKey.'[survey]' => $row['uid'] )
									  ).
						'</li>';
		$content .= '</ul>';

		return $content;
	}

	function createImportDataForm() {
		$content = '';
		$surveyDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $this->survey);

		// present warning iff data is already contained in selected survey
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT COUNT(*) as number
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0 AND hidden=0
													AND survey='.$this->survey);
		if ($res && $row = mysql_fetch_assoc($res))
			if ($row['number']>0)
				$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_WARNING,
									'Es sind schon Daten für die ausgewählte Umfrage vorhanden. Ein Datenimport kann daher zu Dateninkonsistenzen führen.');


		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';

		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value="'.self::kIMPORT.'" />';

            // survey
        $content .='
            <fieldset>
                <label for="'.$this->extKey.'_import_storage">Gewählte Umfrage:</label> '.$surveyDATA['name'].' '.$surveyDATA['semester'];
        $content .= '<input type="hidden" name="'.$this->extKey.'[survey]"
                          id="'.$this->extKey.'_storage"
                          value="'.$this->survey.'"
                          readonly="readonly" />';
        $content .= '</fieldset>';

            // Importfile
		$content .= '<fieldset>
			<label for="'.$this->extKey.'_file">Importdatei:</label>
			<input type="file" name="'.$this->extKey.'[file]" id="'.$this->extKey.'_file"
					value="'.htmlspecialchars($this->piVars["file"]).'" />
			<br />
            
            <label for="'.$this->extKey.'_delimiter">Feldtrenner:</label>
            <select name="'.$this->extKey.'[delimiter]" id="'.$this->extKey.'_delimiter">
                <option>,</option>
                <option>;</option>
                <option>|</option>
                <option>&</option>
            </select>
            <hr />
			<div>
				Erwartet wird eine CSV Datei mit EXAKT den spezifizierten Werten.
				Vor einer endgültigen Speicherung werden die zu speichernden Daten angezeigt. Statt dem Standardfeldtrenner &quot;,&quot; kann optional ein 
				anderer Feldtrenner gewählt werden.<br />
				<pre>Funktion,Anrede,Titel,Vorname,Nachname,Email,LV-Name,LV-Kennung,LV-Ort,Studiengang,LV-Art,Teilnehmer,Orgaeinheit</pre>
				Aufgrund desolater PAUL-Daten muss jede VL exakt einmal in der Liste stehen. Die erste Zeile der Eingabedatei wird ignoriert.
			</div>
			</fieldset>';



		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]"
				value="'.htmlspecialchars('Datei überprüfen').'">';
		$content .= '</form>';

		return $content;
	}

	function createImportDataConfirmForm ($filepath, $survey, $delimiter) {
		$content = '';
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';

		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value='.self::kIMPORT.' />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[file_confirmed]'.'" value="'.$filepath.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[survey]'.'" value="'.$survey.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[delimiter]'.'" value="'.$delimiter.'" />';
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]"
				value="'.htmlspecialchars('Import abschließen').'">';

		$content .= '</form>';

		return $content;
	}

	/**
	 * Function reads CSV File from $_FILES[fsmi_vkrit][file] and
	 * imports everything as array
	 *
	 * @return array with imported data, called $csvArray
	 */
	function loadImportData ($filepath, $delimiter) {
		if ($filepath=='')
			return array ();

		$csvArray = array ();
		$file = fopen($filepath, 'r');
		// delete first line
		$data = fgetcsv($file);
		while (($data = fgetcsv($file, 0, $delimiter)))
    		array_push($csvArray, $data);
    	fclose($file);

		return $csvArray;
	}

	function printImportData ($csvArray) {
		$content = '<table>';
		$content .= '<tr><th>NACHNAME</th><th>EMAIL</th><th>LV_NAME</th><th>ORGAEINHEIT</th></tr>';
		for ($i=0; $i<count($csvArray); $i++) {
            if (count($csvArray[$i])<4)
                $content .= '<tr colspan="4"><td>
                    <strong>Zeile '.($i+2).' ist fehlerhaft und konnte nicht gelesen werden.</strong></td></tr>';
            else {
                $content .= '<tr>';
				$content .= '<td>'.$csvArray[$i][self::kCSV_NACHNAME].'</td>';
				$content .= '<td>'.(($csvArray[$i][self::kCSV_EMAIL]!='') ? $csvArray[$i][self::kCSV_EMAIL] : '<strong>FEHLT</strong>').'</td>';
				$content .= '<td>'.$csvArray[$i][self::kCSV_LV_NAME].'</td>';
				$content .= '<td>'.$csvArray[$i][self::kCSV_ORGAEINHEIT].'</td>';
				$contetn .= '</tr>';
			}
				
        }
		$content .= '</table>';

		return $content;
	}

	/**
	 * This function performs database MYSQL INSERT queries for lecturer and lecture
	 * @param array		$csvArray
	 * @param array		$survey
	 * @return unknown_type
	 */
	function saveImportData ($csvArray, $survey) {
		// get lecturers
		$lecturerArr = $this->createLecturerArray ($csvArray);

		// get storage
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_fsmivkrit_survey
											WHERE deleted=0 AND hidden=0
											AND uid=\''.$survey.'\'');
		if ($res && $row = mysql_fetch_assoc($res))
			$storage=$row['storage'];

		// save lecturers
		foreach ($lecturerArr as $lecturer) {

			// try to calculate sex
			if ($lecturer[self::kCSV_ANREDE] == 'Frau')
				$lecturer['sex'] = self::kDB_SEX_FEMALE;
			else if ($lecturer[self::kCSV_ANREDE] == 'Herr')
				$lecturer['sex'] = self::kDB_SEX_MALE;
			else
				$lecturer['sex'] = self::kDB_SEX_UNKNOWN;

			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
									'tx_fsmivkrit_lecturer',
									array (	'pid' => $storage,
											'crdate' => time(),
											'tstamp' => time(),
											'title' => $lecturer[self::kCSV_ANREDE],
											'sex' => $lecturer['sex'],
											'name' => $lecturer[self::kCSV_NACHNAME],
											'forename' => $lecturer[self::kCSV_VORNAME],
											'email' => $lecturer[self::kCSV_EMAIL],
											'foreign_id' => $lecturer['hash'],
											'organizational_unit' => $lecturer[self::kCSV_ORGAEINHEIT],
									));
			// break on error
			if(!$res)
				return false;
		}

		// save lectures
		foreach ($csvArray as $lecture) {

			// get lecturer ID
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_lecturer
												WHERE deleted=0 AND hidden=0
												AND foreign_id=\''.$lecture['lecturer_hash'].'\'');
			if ($res && $row = mysql_fetch_assoc($res))
				$lecturerUID=$row['uid'];

			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
									'tx_fsmivkrit_lecture',
									array (	'pid' => $storage,
											'crdate' => time(),
											'tstamp' => time(),
											'name' => $lecture[self::kCSV_LV_NAME],
											'lecturer' => $lecturerUID,
											'survey' => $survey,
											'foreign_id' => $lecture[self::kCSV_LV_KENNUNG],
									));
		}
		return true;
		//TODO break before if not everything went fine!
	}

	/**
	 * This function creates an array with lecturers and sets corresponding IDs into the CSV-array (CSV-array is expected by reference)
	 * @param $csvArray
	 * @return $lecturerArr array with keys
	 */
	function createLecturerArray (&$csvArray) {
		$lecturerArr = array ();

		for ($i=0; $i<count($csvArray); $i++) {
			// hash it to get a good
			$hash = hash('sha256', $csvArray[$i][self::kCSV_VORNAME].$csvArray[$i][self::kCSV_NACHNAME].$csvArray[$i][self::kCSV_EMAIL]);
			if (!array_key_exists($hash, $lecturerArr)) {
				$lecturerArr[$hash] = $csvArray[$i];
				$lecturerArr[$hash]['hash'] = $hash;
				$csvArray[$i]['lecturer_hash'] = $hash;
			} else
			// hash really identifies same person
			if ($lecturerArr[$hash][self::kCSV_NACHNAME]==$csvArray[$i][self::kCSV_NACHNAME]
						&& $lecturerArr[$hash][self::kCSV_VORNAME]==$csvArray[$i][self::kCSV_VORNAME]
						&& $lecturerArr[$hash][self::kCSV_EMAIL]==$csvArray[$i][self::kCSV_EMAIL]) {
				$csvArray[$i]['lecturer_hash'] = $hash;
				continue;
			} else {
				debug('Oh my god, we found a SHA-256 collision! Report it and become famous!');
				// TODO do some thing but this is not likely to happen...
				
			}
		}
		return $lecturerArr;
	}

	/**
	 * This function creates an object of type DomDocument that represents a given survey in XSD style as requested by
	 * "Handbuch für den XML-Import V4.0" by EvaSys.
	 * Please note that only the data is included that is contained in the database.
	 * @param Integer for $survey UID
	 * @return DocumentDOM as object
	 */
	function createOutputDOM($survey) {
		$surveyUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $survey);

		// set organization root
		if ($surveyUID['orgroot']=='')
			$orgroot = 'notSet';
		else
			$orgroot = $surveyUID['orgroot'];

		// we expect data
		if (!$surveyUID)
			return false;

		$document = new DomDocument('1.0', 'utf-8');	// *THE* return value
		$document->formatOutput = true;

		$evasysDOM = $document->appendChild(
			$document->createElement('EvaSys')
		);

		/*
		 * Frist Step:
		 * Create Survey
		 */
//		$surveyDOM = $evasysDOM->appendChild(
//			$document->createElement('Survey')
//		);
//		$surveyDOM->setAttribute('key', $this->getKeyForSurvey($surveyUID['uid']));
//		$surveyDOM->appendChild(
//			$document->createElement(
//				'survey_period',
//				$surveyUID['semester'])
//			);
		//TODO type? no idea if mandatory, ask SVK team!

		//TODO testroom
		$roomDOM = $evasysDOM->appendChild(
			$document->createElement('Room')
		);
		$roomDOM->setAttribute('key', 'Raum1');
		$roomDOM->appendChild(
			$document->createElement(
				'name',
				'SVK Büro')
			);


		/*
		 * Second Step:
		 * Create Lecturers
		 */
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT tx_fsmivkrit_lecturer.uid as uid,
													tx_fsmivkrit_lecturer.name as name,
													tx_fsmivkrit_lecturer.forename as forename,
													tx_fsmivkrit_lecturer.email as email,
													tx_fsmivkrit_lecturer.sex as sex
												FROM tx_fsmivkrit_lecture, tx_fsmivkrit_lecturer
												WHERE tx_fsmivkrit_lecturer.deleted=0
													AND tx_fsmivkrit_lecture.lecturer =  tx_fsmivkrit_lecturer.uid
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.hidden=0
													AND tx_fsmivkrit_lecture.no_eval=0
												GROUP BY tx_fsmivkrit_lecturer.uid, tx_fsmivkrit_lecturer.name, tx_fsmivkrit_lecturer.forename, tx_fsmivkrit_lecturer.email');

		while ($res && $lecturer = mysql_fetch_assoc($res)) {
			$newLecturer = $evasysDOM->appendChild(
				$document->createElement('Person')
			);
			// set key
			$newLecturer->setAttribute('key', $this->getKeyForLecturer($lecturer['uid']));
			// set name
			$newLecturer->appendChild(
				$document->createElement(
					'firstname',
					$lecturer['forename']
				)
			);
			$newLecturer->appendChild(
				$document->createElement(
					'lastname',
					$lecturer['name']
				)
			);
			// set email
			$newLecturer->appendChild(
				$document->createElement(
					'email',
				//TODO	$lecturer['email']
					$this->emailOrganizer
				)
			);
			// TODO hack for username, should be PAUL username
			$newLecturer->appendChild(
				$document->createElement(
					'username',
					$this->getKeyForLecturer($lecturer['uid'])
				)
			);
			// set gender
			$newLecturer->appendChild(
				$document->createElement(
					'gender',
					($lecturer['sex']==self::kDB_SEX_MALE ? 'm' : 'f')
				)
			);
		}

		// TODO question: what to do with tutors: send them mails directly, or to lecturers?
		// talk to Vkrit-Team
		/*
		 * Third Step:
		 * Create Assistants
		 */
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT tx_fsmivkrit_tutorial.uid as uid,
													tx_fsmivkrit_tutorial.assistant_name as name,
													tx_fsmivkrit_tutorial.assistant_forename as forename
												FROM tx_fsmivkrit_lecture, tx_fsmivkrit_tutorial
												WHERE tx_fsmivkrit_tutorial.deleted=0
												AND tx_fsmivkrit_lecture.uid =  tx_fsmivkrit_tutorial.lecture
												AND tx_fsmivkrit_lecture.no_eval=0
												AND tx_fsmivkrit_lecture.hidden=0
												AND tx_fsmivkrit_lecture.deleted=0
												AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'');

		while ($res && $tutor = mysql_fetch_assoc($res)) {
			$newTutor = $evasysDOM->appendChild(
				$document->createElement('Person')
			);
			// set key
			$newTutor->setAttribute('key', $this->getKeyForTutor($tutor['uid']));
			// set name
			$newTutor->appendChild(
				$document->createElement(
					'firstname',
					$tutor['forename']
				)
			);
			$newTutor->appendChild(
				$document->createElement(
					'lastname',
					($tutor['name']=='' ? 'NN' : $tutor['name'])
				)
			);
			// TODO here: mail
			// set email
			$newTutor->appendChild(
				$document->createElement(
					'email',
					$this->emailOrganizer
				)
			);
			// TODO hack for username
			$newTutor->appendChild(
				$document->createElement(
					'username',
					$this->getKeyForTutor($tutor['uid'])
				)
			);
		}

		/*
		 * Fourth Step:
		 * Create Lectures
		 */
		$resLecture = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0 AND hidden=0
												AND no_eval=0
												AND survey=\''.$survey.'\'');

		while ($resLecture && $lecture = mysql_fetch_assoc($resLecture)) {
			$newLecture = $evasysDOM->appendChild(
				$document->createElement('Lecture')
			);
			$newLecture->setAttribute('key', $this->getKeyForLecture($lecture['uid']));
			
			
			// set p_o_field
			// this is used for sorting of the data at EvaSys
			$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lecture["lecturer"]);
			$newLecture->appendChild(
                $document->createElement(
                    "p_o_study", 
                    $this->lecture_type[$lecture['lecture_type']].
                    "|".
                    $lecturerUID['organizational_unit'].
                    "|".
                    $lecture['name']
                )
            );

			// container for lecturer and tutors
			$newDozs = $newLecture->appendChild(
				$document->createElement('dozs')
			);

			// include lecturer
			$newSingleDoz = $newDozs->appendChild(
				$document->createElement('doz')
			);
			$newEvaRef = $newSingleDoz->appendChild(
				$document->createElement('EvaSysRef')
			);
			$newEvaRef->setAttribute('type', 'Person');
			$newEvaRef->setAttribute('key',$this->getKeyForLecturer($lecture['lecturer']));

			// lecture information (name, term...)
			$newLecture->appendChild(
				$document->createElement(
					'name',
					htmlspecialchars($lecture['name'])
				)
			);
//			$newLecture->appendChild(
//				$document->createElement(
//					'period',
//					htmlspecialchars($surveyUID['semester'])
//				)
//			);
			$newLecture->appendChild(
				$document->createElement(
					'short',
					$surveyUID['semester'].'-'.$lecture['foreign_id']
				)
			);
			$newLecture->appendChild(
				$document->createElement(
					'orgroot',
					htmlspecialchars($orgroot)
				)
			);
			$newLecture->appendChild(
				$document->createElement(
					'type',
					'Vorlesung'
				)
			);
			// TODO HACK to fix missing room
			$newRoom = $newLecture->appendChild(
				$document->createElement('room')
			);
			$newEvaRef = $newRoom->appendChild(
				$document->createElement('EvaSysRef')
			);
			$newEvaRef->setAttribute('type', 'Room');
			$newEvaRef->setAttribute('key','Raum1');


			// set tutors
			// each tutorial is one "lecture" for its own with lecturer as "Sekundärdozent"
			$resTutors = $GLOBALS['TYPO3_DB']->sql_query('SELECT uid
												FROM tx_fsmivkrit_tutorial
												WHERE deleted=0
												AND lecture=\''.$lecture['uid'].'\'');
			while ($resTutors && $tutor = mysql_fetch_assoc($resTutors)) {
				// create new tutorial
				$newLecture = $evasysDOM->appendChild(
					$document->createElement('Lecture')
				);
				// use key: lectureUID.tutorUID
				$newLecture->setAttribute('key', $this->getKeyForLecture($lecture['uid']).$this->getKeyForTutor($tutor['uid']));

				// container for lecturer and tutors
				$newDozs = $newLecture->appendChild(
					$document->createElement('dozs')
				);

				$newSingleDoz = $newDozs->appendChild(
					$document->createElement('doz')
				);
				$newEvaRef = $newSingleDoz->appendChild(
					$document->createElement('EvaSysRef')
				);
				$newEvaRef->setAttribute('type', 'Person');
				$newEvaRef->setAttribute('key',$this->getKeyForTutor($tutor['uid']));

				// include lecturer
				$newSingleDoz = $newDozs->appendChild(
					$document->createElement('doz')
				);
				$newEvaRef = $newSingleDoz->appendChild(
					$document->createElement('EvaSysRef')
				);
				$newEvaRef->setAttribute('type', 'Person');
				$newEvaRef->setAttribute('key',$this->getKeyForLecturer($lecture['lecturer']));

				// lecture information (name, term...)
				$newLecture->appendChild(
					$document->createElement(
						'name',
						htmlspecialchars($lecture['name'].' (Übung)')
					)
				);
//				$newLecture->appendChild(
//					$document->createElement(
//						'period',
//						htmlspecialchars($surveyUID['semester'])
//					)
//				);
				$newLecture->appendChild(
					$document->createElement(
						'short',
						$surveyUID['semester'].'-'.$lecture['foreign_id'].'_tutorial'
					)
				);
				$newLecture->appendChild(
					$document->createElement(
						'orgroot',
						htmlspecialchars($orgroot)
					)
				);
				$newLecture->appendChild(
					$document->createElement(
						'type',
						'Übung'
					)
				);
				// TODO HACK to fix missing room
				$newRoom = $newLecture->appendChild(
					$document->createElement('room')
				);
				$newEvaRef = $newRoom->appendChild(
					$document->createElement('EvaSysRef')
				);
				$newEvaRef->setAttribute('type', 'Room');
				$newEvaRef->setAttribute('key','Raum1');
			}
		}

		return $document->saveXML();
	}

	/**
	 * Stores data to file system an returns link
	 * @param $data
	 * @return unknown_type
	 */
	function storeOutputDataXML ($data) {
		// writing file
		t3lib_div::writeFileToTypo3tempDir (
										PATH_site."typo3temp/".'fsmivkrit_export.xml',
										$data
										);

		return '<a href="typo3temp/fsmivkrit_export.xml">XML Datei downloaden</a>';
	}

	function storeOutputLecturesList($survey) {
		$resLecture = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0
												AND no_eval=0
												AND survey=\''.$survey.'\'
												ORDER BY name');

		$list = '';
		while ($resLecture && $lecture = mysql_fetch_assoc($resLecture)) {
			$lecturer = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lecture['lecturer']);

			$search = array ('/\(/', '/\)/');
			$replace = array ('[', ']');
			$list .= preg_replace($search, $replace,$lecture['name']);

			$list .= ' ';
			$list .= '('.
				($lecturer['title']!=''?$lecturer['title'].' ':'')	//title
				.$lecturer['forename'].		//forename
				' '.$lecturer['name'].')';	//name
			$list .= "\n";

		}
		t3lib_div::writeFileToTypo3tempDir (
										PATH_site."typo3temp/".'fsmivkrit_lecture_list.txt',
										$list
										);


		return '<a href="typo3temp/fsmivkrit_lecture_list.txt">Vorlesungsliste speichern</a>';
	}

	/**
	 * Function creates unique key for lecture according to Electric Paper guidances
	 * @param $lecture UID as integer
	 * @return key as string
	 */
	function getKeyForLecture($lecture) {
		//$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		return 'lec'.$lecture;
	}

	/**
	 * Function creates unique key for lecturer according to Electric Paper guidances.
	 * @param $lecturer UID as integer
	 * @return key as string
	 */
	function getKeyForLecturer($lecturer) {
		//$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		return 'doz'.$lecturer;
	}

	/**
	 * Function creates unique key for teaching assistant according to Electric Paper guidances.
	 * @param $tutor UID as integer
	 * @return key as string
	 */
	function getKeyForTutor($tutor) {
		//$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		return 'tut'.$tutor;
	}

	/**
	 * Function creates unique key for survey according to Electric Paper guidances.
	 * @param $survey UID as integer
	 * @return key as string
	 */
	function getKeyForSurvey($survey) {
		return 'sur'.$survey;
	}


}




if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi4/class.tx_fsmivkrit_pi4.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi4/class.tx_fsmivkrit_pi4.php']);
}

?>