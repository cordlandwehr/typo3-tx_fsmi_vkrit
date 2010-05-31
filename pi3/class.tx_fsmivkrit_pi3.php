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
require_once(t3lib_extMgm::extPath('fsmi_vkrit').'pi2/class.tx_fsmivkrit_pi2.php');

/**
 * Plugin 'Coordination' for the 'fsmi_vkrit' extension.
 *
 * @author	Andreas Cord-Landwehr <fsmi@uni-paderborn.de>
 * @package	TYPO3
 * @subpackage	tx_fsmivkrit
 */
class tx_fsmivkrit_pi3 extends tslib_pibase {
	var $prefixId      = 'tx_fsmivkrit_pi3';		// Same as class name
	var $scriptRelPath = 'pi3/class.tx_fsmivkrit_pi3.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'fsmi_vkrit';	// The extension key.

	//TODO check if the followin is really needed
	var $checked = array(false => '', true => 'checked');
	var $selected = array(false => '', true => 'selected');
	var $xxx = array(0 => '', 1 => 'X');
	var $nulleins = array(true => 1, false => 0);
	var $editMode = false;

	var $survey;

	const kLOCKTIME		= 300;	// system locking time in seconds

	const kLIST					= 1;	// mode: assign kritter form
	const kASSIGN_KRITTER_FORM	= 2;	// mode: assign kritter form
	const kASSIGN_KRITTER_SAVE	= 3;	// mode: assign kritter form

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


		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$this->survey = intval($GETcommands['survey']);

		//TODO
		$this->survey = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'uidSurvey'));

		switch(intval($GETcommands['type'])) {
			case self::kASSIGN_KRITTER_FORM: {
				$content .= '<h2>Kritter-Daten ändern</h2>';
				$content .= $this->printLectureEditForm(intval($GETcommands['lecture']));
				break;
			}
			case self::kASSIGN_KRITTER_SAVE: {
				$content .= '<h2>Koordination - Übersicht</h2>';
				$content .= $this->saveKritterData(intval($GETcommands['lecture']));
				$content .= $this->printTable($this->survey);
				$content .= '<h3>Tipper-Ranking</h3>';
				$content .= $this->printTipperRanking($this->survey);
				$content .= '<h3>Paten-Ranking</h3>';
				$content .= $this->printGodfatherRanking($this->survey);
				break;
			}
			default: {	// could also be kLIST
				$content .= '<h2>Koordination - Übersicht</h2>';
				$content .= $this->printTable($this->survey);
				$content .= '<h3>Tipper-Ranking</h3>';
				$content .= $this->printTipperRanking($this->survey);
				$content .= '<h3>Paten-Ranking</h3>';
				$content .= $this->printGodfatherRanking($this->survey);
				$content .= '<h3>Kritter-Ranking</h3>';
				$content .= $this->printKritterRanking($this->survey);
				break;
			}
		}


		return $this->pi_wrapInBaseClass($content);
	}


	function nix($s) {	//TODO rework
		if (trim($s) == '') return '&nbsp;'; else return $s;
	}

	function null($s) { //TODO rework
		if (trim($s) == '') return '0'; else return $s;
	}

	function ohnenull($s) { // TODO rework
		if ($s == 0) return ''; else return $s;
	}

	function nospace($s) { //TODO rework
		return str_replace(' ', '&nbsp;', $s);
	}

	function printTipperRanking ($survey) {

		// get sum
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT SUM(weight) as total_weight
												FROM tx_fsmivkrit_helper, tx_fsmivkrit_lecture
												WHERE tx_fsmivkrit_helper.deleted=0
													AND tx_fsmivkrit_lecture.deleted=0
													AND tx_fsmivkrit_helper.hidden=0
													AND tx_fsmivkrit_helper.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.tipper = tx_fsmivkrit_helper.uid');
		if ($res && $row = mysql_fetch_assoc($res))
			$total_weight = $row['total_weight'];
		else
			$total_weight = 1; // 0 would be 'division by zero' ;)

		// select total-weight
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT tx_fsmivkrit_helper.uid AS uid, tx_fsmivkrit_helper.name AS name, SUM(weight) as weight
												FROM tx_fsmivkrit_helper, tx_fsmivkrit_lecture
												WHERE tx_fsmivkrit_helper.deleted=0
													AND tx_fsmivkrit_lecture.deleted=0
													AND tx_fsmivkrit_helper.hidden=0
													AND tx_fsmivkrit_helper.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.tipper = tx_fsmivkrit_helper.uid
												GROUP BY tx_fsmivkrit_helper.uid
												ORDER BY name');

		$ranking = array ();
		$maxWeight = 0;
		while ($res && $row = mysql_fetch_assoc($res)) {
			$ranking[$row['uid']]['weight'] = $row['weight'];
			if ($maxWeight < $row['weight'])
				$maxWeight = $row['weight'];
			$ranking[$row['uid']]['name'] = $row['name'];
		}

		if ($maxWeight == 0)
			$maxWeight = $total_weight;

		// select unfinished weight
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT tx_fsmivkrit_helper.uid AS uid, tx_fsmivkrit_helper.name AS name, SUM(weight) as weight
												FROM tx_fsmivkrit_helper, tx_fsmivkrit_lecture
												WHERE tx_fsmivkrit_helper.deleted=0
													AND tx_fsmivkrit_lecture.deleted=0
													AND tx_fsmivkrit_helper.hidden=0
													AND tx_fsmivkrit_helper.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.tipper = tx_fsmivkrit_helper.uid
													AND tx_fsmivkrit_lecture.eval_state < \''.tx_fsmivkrit_div::kEVAL_STATE_ANONYMIZED.'\'
												GROUP BY tx_fsmivkrit_helper.uid
												ORDER BY name');

		while ($res && $row = mysql_fetch_assoc($res)) {
			$ranking[$row['uid']]['todo_weight'] = $row['weight'];
		}

		$content .= '<table>';
		$content .= '<tr bgcolor="#526feb" style="color:white; width: 40px;"><th>Name</th><th style="width:250px">Gewicht</th></tr>';
		foreach ($ranking as $tipper) {
			$content .= '<tr><td width="150">'.$tipper['name'].'</td>
				<td><span style="width:50px; font-weight: bold;">'.$tipper['weight'].'g</span><br />';
			if ($tipper['weight']-$tipper['todo_weight'] > 0)
				$content .= '<div style="background-color: blue; float:left; padding-left: 3px; width:'.intval(($tipper['weight']-$tipper['todo_weight'])/$maxWeight*200).'px;">&nbsp;</div>';
			if ($tipper['todo_weight'] > 0)
				$content .= '<div style="background-color: yellow; float:left; width:'.intval($tipper['todo_weight']/$maxWeight*200).'px;">&nbsp;</div>';
			$content .= '&nbsp;</div></td></tr>';
		}

		$content .= '</table>';

		return $content;
	}

	function printGodfatherRanking ($survey) {

		// get sum
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT SUM(weight) as total_weight
												FROM tx_fsmivkrit_helper, tx_fsmivkrit_lecture
												WHERE tx_fsmivkrit_helper.deleted=0
													AND tx_fsmivkrit_lecture.deleted=0
													AND tx_fsmivkrit_helper.hidden=0
													AND tx_fsmivkrit_helper.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.godfather = tx_fsmivkrit_helper.uid');
		if ($res && $row = mysql_fetch_assoc($res))
			$total_weight = $row['total_weight'];
		else
			$total_weight = 1; // 0 would be 'division by zero' ;)

		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT tx_fsmivkrit_helper.name AS name, SUM(weight) as weight
												FROM tx_fsmivkrit_helper, tx_fsmivkrit_lecture
												WHERE tx_fsmivkrit_helper.deleted=0
													AND tx_fsmivkrit_lecture.deleted=0
													AND tx_fsmivkrit_helper.hidden=0
													AND tx_fsmivkrit_helper.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND tx_fsmivkrit_lecture.godfather = tx_fsmivkrit_helper.uid
												GROUP BY tx_fsmivkrit_helper.uid
												ORDER BY name');

		$content .= '<table>';
		$content .= '<tr bgcolor="#526feb" style="color:white; width: 40px;"><th>Name</th><th style="width:250px">Gewicht</th></tr>';
		while ($res && $row = mysql_fetch_assoc($res))
			$content .= '<tr><td width="150">'.$row['name'].'</td>
							<td><span style="width:50px; font-weight: bold;">'.$row['weight'].'g</span>
								<div style="background-color: green; padding-left: 3px; width:'.intval($row['weight']/$total_weight*200).'px;">&nbsp;</div></td></tr>';

		$content .= '</table>';

		return $content;
	}

	function printKritterRanking ($survey) {
		// get sum
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT fe_users.uid as kritter,
													fe_users.name as krittername,
													fe_users.username as kritterusername,
													COUNT(tx_fsmivkrit_lecture.uid) as number
												FROM fe_users, tx_fsmivkrit_lecture
												WHERE fe_users.deleted=0
													AND tx_fsmivkrit_lecture.deleted=0
													AND tx_fsmivkrit_lecture.hidden=0
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND (
														fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_1
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_2
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_3
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_4)
												GROUP BY kritter, fe_users.name, fe_users.username
												ORDER BY number
													');
		// TODO create intersection -> get lectures that are ready and those who are not
		$content = '<ul>';
		while ($res && $row = mysql_fetch_assoc($res))
			$content .= '<li>'.($row['krittername']!=''?$row['krittername']:$row['kritterusername']).': '.$row['number'].'</li>';

		$content .= '</ul>';
		return $content;

	}

	//TODO change layout, text, LL etc.
	function printTableHead() {
		$content .= '<tr bgcolor="#526feb">';
		$content .= '	<td align="center" style="color:white; width: 20px;"></td>';
		$content .= '	<td align="center" style="color:white; width: 40px;"><b>Tag</b></td>';
		$content .= '	<td align="center" style="color:white; width: 35px;"><b>Zeit</b></td>';
		$content .= '	<td align="center" style="color:white;"><b>Raum</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Vorlesung</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Dozent</b></td>';
		$content .= '	<td align="center" style="color:white"><b>#</b></td>';
		$content .= '	<td align="center" style="color:white; width:100px;"><b>Kommentar</b></td>';
		$content .= '	<td align="center" style="color:white; width: 100px;"><b>Kritter</b></td>';
		$content .= '	<td align="center" style="color:white; width: 100px;"><b>Sortierer</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Gewicht</b></td>';
		$content .= '	<td align="center" style="color:white; width:100px;"><b>Tipper</b></td>';
		$content .= '	<td align="center" style="color:white"><b>EDIT</b></td>';
		$content .= '</tr>';

		return $content ;
   	}


	function printTable ($survey) {
 		$content = '';

   		//TODO here we need the current number of pictures per evaluater
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0
													AND hidden=0
													AND survey= \''.$survey.'\'
													AND eval_state BETWEEN '.
														tx_fsmivkrit_div::kEVAL_STATE_APPROVED.
														' AND '.tx_fsmivkrit_div::kEVAL_STATE_FINISHED.'
													AND no_eval=0
												ORDER BY eval_date_fixed');

		// counter for lectures
		$count = 0;

		$content .= '<table cellpadding="3">';

		while ($res && $row = mysql_fetch_assoc($res)) {
			$count++;

			// we do not want to see finished evaluations at coordination page by default, maybe some extensible JavaScript..
	   		if ($row['eval_state']==tx_fsmivkrit_div::kEVAL_STATE_FINISHED)
				continue;

			// this tests if date is new and should be displayed (only disply once)
			$newdate = date('D d.m.',$row['eval_date_fixed']);
	   		if ($olddate == $newdate)
	   			$newdate = '&nbsp;<br />&nbsp;';
	  		else {
	   			$olddate = $newdate;
	   			$content .= $this->printTableHead();
	  		}

	  		// set row color
	   		switch ($row['eval_state']) {
				case tx_fsmivkrit_div::kEVAL_STATE_APPROVED: {
	   				$content .= '<tr bgcolor="'.tx_fsmivkrit_div::kCOLOR_COORDINATION_APPROVED.'">';
	   				break;
	   			}
				case tx_fsmivkrit_div::kEVAL_STATE_EVALUATED: {
	   				$content .= '<tr bgcolor="'.tx_fsmivkrit_div::kCOLOR_COORDINATION_EVALUATED.'">';
	   				break;
				}
				case tx_fsmivkrit_div::kEVAL_STATE_SORTED: {
					$content .= '<tr bgcolor="'.tx_fsmivkrit_div::kCOLOR_COORDINATION_SORTED.'">';
	   				break;
				}
	   			case tx_fsmivkrit_div::kEVAL_STATE_SCANNED: {
	   				$content .= '<tr bgcolor="'.tx_fsmivkrit_div::kCOLOR_COORDINATION_SCANNED.'">';
	   				break;
	   			}
	   			case tx_fsmivkrit_div::kEVAL_STATE_ANONYMIZED: {
	   				$content .= '<tr bgcolor="'.tx_fsmivkrit_div::kCOLOR_COORDINATION_ANONYMIZED.'">';
	   				break;
	   			}
	   			default: $content .= '<tr>';
	   		}

	   		// get lecturer
	   		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $row['lecturer']);

	   		// print row
	   		$content .= '<td>'.tx_fsmivkrit_div::print8State($row['eval_state']).'</td>';
			$content .= '<td align="left">'.$this->nix($newdate).'</td>';
	   		$content .= '<td align="center">'.date('H:i',$row['eval_date_fixed']).'</td>';
			$content .= '<td align="center">'.$row['eval_room_fixed'].'</td>';
	  		$content .= '<td align="left">'.$this->pi_linkTP($row['name'],
														array (
															$this->extKey.'[survey]' => $this->survey,
															$this->extKey.'[lecture]' => $row['uid'],
															$this->extKey.'[type]' => self::kASSIGN_KRITTER_FORM
														)).'</td>';
			$content .= '<td align="left">'.$lecturerUID['name'].'</td>';
			$content .= '<td align="center">'.$row['participants'].'</td>';
			$content .= '<td align="left">'.$row['comment'].'</td>';

	   		trim($row['kritter_feuser_1'])==0 ?
				$content .= '<td bgcolor="red" style="color:#fff;"><ol style="padding-left: 1.5em; margin-left: 0px;">':	// red, because kritter needed
				$content .= '<td><ol style="padding-left: 1.5em; margin: 0px;">';						// standard
	  		for ($i = 1; $i < 5; $i++) {
	  			if ($row['kritter_feuser_'.$i]!=0 ) {
					$kritter = t3lib_BEfunc::getRecord('fe_users', $row['kritter_feuser_'.$i]);
					$content .= '<li style="padding:0px">'.($kritter['name']!=''? $kritter['name'] : $kritter['username']).'</li>';
				}
				else if ($i==1)
					$content .= '<li><strong>fehlt</strong></li>';
	  		}
			$content .= '</ol></td>';

			$godfatherUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_helper', $row['godfather']);
			$content .= (
				($row['eval_state']==tx_fsmivkrit_div::kEVAL_STATE_EVALUATED && $row['godfather']==0) ?
					'<td align="center;" bgcolor="red">':
					'<td align="center">'.$godfatherUID['name']
				)
				.'</td>';
			$content .= '<td align="center">'.$this->nix($this->ohnenull($row['weight'])).'</td>';
//			$content .= '<td align="center" style="border-left:4px solid black">'.$this->nix($this->ohnenull($row['pictures'])).'</td>';
			$tipperUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_helper', $row['tipper']);
			$content .= (
				($row['eval_state']==tx_fsmivkrit_div::kEVAL_STATE_SCANNED && $row['tipper']==0) ?
					'<td align="center;" bgcolor="red">':
					'<td align="center">'.$tipperUID['name']
				)
				.'</td>';
			// TODO check by state!
//			$content .= '<td align="center" style="border-left:4px solid black">'.$this->nix($this->ohnenull($getippt)).'</td>';
	  		$content .= '<td align="left">'.$this->pi_linkTP('editieren',
													array (
														$this->extKey.'[survey]' => $this->survey,
														$this->extKey.'[lecture]' => $row['uid'],
														$this->extKey.'[type]' => self::kASSIGN_KRITTER_FORM
													)).'</td>';
			$content .= '</tr>'."\n";
		}

		$content .= '</table>';

		$content .= '<p>Insgesamt sind '.$count.' Veranstaltungen in dieser V-Krit enthalten.</p>';

		return $content;
	}

	function printLectureEditForm ($lecture) {
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $lectureUID['lecturer']);
		$surveyUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_survey', $this->survey);

		$content = '';

		// the user probably wants to have a way out:
		$content .= '<div style="margin:10px;"><strong>'.$this->pi_linkTP('Eingabe abbrechen!',
						array (
							$this->extKey.'[type]' => self::kLIST,
							$this->extKey.'[survey]' => $this->survey
						)).'</strong></div>';

		// head information
		$content .= '<h3>Allgemeine Daten</h3>';
		$content .= '<ul>'.
					'<li><strong>Veranstaltung:</strong> '.$lectureUID['name'].'</li>'.
					'<li><strong>PAUL-ID:</strong> '.$lectureUID['foreign_id'].'</li>'.
					'<li><strong>Dozent:</strong> '.$lecturerUID['name'].', '.$lecturerUID['forename'].'</li>'.
					'<li><strong>Teilnehmer:</strong> '.$lectureUID['participants'].'</li>'.
					'<li><strong>V-Krit Termin:</strong> '.date('d.m.Y - H:i',$lectureUID['eval_date_fixed']).'</li>'.
					'<li><strong>Raum:</strong> '.$lectureUID['eval_room_fixed'].'</li>'.
					'</ul>';
		$content .= '<pre>'.$lectureUID['comment'].'</pre>';

		$content .= '<h3>Kritter- und Krit-Daten</h3>';
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';

		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value='.self::kASSIGN_KRITTER_SAVE.' />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[lecture]'.'" value="'.$lecture.'" />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[survey]'.'" value="'.$this->survey.'" />';

		$content .= '<fieldset>';
		$content .= '<table>';
		// here all three input fields and one additional ...
		for ($i=1; $i<=4; $i++) {
			$content .= '<tr><td><label for="'.$this->extKey.'_kritter_'.$i.'">Kritter '.$i.'</label></td><td>
							<select name="'.$this->extKey.'[kritter_feuser_'.$i.']" size="1" id="'.$this->extKey.'_kritter_'.$i.'"'.
							'<option value="0"></option>';
				$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
													FROM fe_users
													WHERE disable=0 AND deleted=0
													ORDER BY name,username');
	   					while ($res && $rowKritter = mysql_fetch_assoc($res)) {
	   						$content .= '<option value="'.$rowKritter['uid'].'" '.(
	   							$rowKritter['uid']==$lectureUID['kritter_feuser_'.$i] ?
	   								'selected="selected"':
	   								''
	   						).' >'.($rowKritter['name']!=''? $rowKritter['name']: $rowKritter['username']).'</option>';
	   					}
	   			$content .= '</select>
							</td></tr>';
		}

		// Godfather
	   	$content .= '<tr><td><label for="'.$this->extKey.'_godfather">Sortierer</label></td><td>
	   		<select name="'.$this->extKey.'[godfather]" id="'.$this->extKey.'_godfather" size="1">';
	   			$content .= '<option value="0"></option>';
				$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
													FROM tx_fsmivkrit_helper
													WHERE deleted=0 AND hidden=0
														AND survey='.$lectureUID['survey'].'
													ORDER BY name');
	   					while ($res && $rowHelper = mysql_fetch_assoc($res)) {
	   						$content .= '<option value="'.$rowHelper['uid'].'" '.(
	   							$rowHelper['uid']==$lectureUID['godfather'] ?
	   								'selected="selected"':
	   								''
	   						).' >'.$rowHelper['name'].'</option>';
	   					}
	   			$content .= '</select></td></tr>';

		// Tipper
	   	$content .= '<tr><td><label for="'.$this->extKey.'_tipper">Tipper</label></td>
	   		<td><select name="'.$this->extKey.'[tipper]" id="'.$this->extKey.'_tipper" size="1">';
	   			$content .= '<option value="0"></option>';
				$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
													FROM tx_fsmivkrit_helper
													WHERE deleted=0 AND hidden=0
														AND survey='.$lectureUID['survey'].'
													ORDER BY name');
	   					while ($res && $rowHelper = mysql_fetch_assoc($res)) {
	   						// TODO change submit value
	   						$content .= '<option value="'.$rowHelper['uid'].'" '.(
	   							$rowHelper['uid']==$lectureUID['tipper'] ?
	   								'selected="selected"':
	   								''
	   						).' >'.$rowHelper['name'].'</option>';
	   					}
	   			$content .= '</select></td></tr>';

	   	// weight
	   	$content .= '<tr><td><label for="'.$this->extKey.'_weight">Gewicht</label></td><td>
							<input type="text" name="'.$this->extKey.'[weight]" id="'.$this->extKey.'_weight"
								value="'.($lectureUID["weight"]>0 ? $lectureUID["weight"]:'').'" size="10" />
						</td></tr>';
	   	// state
	   	$content .= '<tr><td>Status</td><td>';
		// created
	   	$content .= '<input type="radio" name="'.$this->extKey.'[eval_state]" ';
	   	if ($lectureUID['eval_state']==tx_fsmivkrit_div::kEVAL_STATE_APPROVED) $content .= ' checked="checked" ';
	   	$content .= '				id="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_APPROVED.'" value="'.tx_fsmivkrit_div::kEVAL_STATE_APPROVED.'" />'.
	   				'<label for ="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_APPROVED.'">Termin zugewiesen</label><br />';
		// evaluated
		$content .= '<input type="radio" name="'.$this->extKey.'[eval_state]" ';
	   	if ($lectureUID['eval_state']==tx_fsmivkrit_div::kEVAL_STATE_EVALUATED) $content .= ' checked="checked" ';
	   	$content .= '				id="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_EVALUATED.'" value="'.tx_fsmivkrit_div::kEVAL_STATE_EVALUATED.'" />'.
	   				'<label for ="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_EVALUATED.'">gekrittet</label><br />';
		// sorted
		$content .= '<input type="radio" name="'.$this->extKey.'[eval_state]" ';
	   	if ($lectureUID['eval_state']==tx_fsmivkrit_div::kEVAL_STATE_SORTED) $content .= ' checked="checked" ';
	   	$content .= '				id="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_SORTED.'" value="'.tx_fsmivkrit_div::kEVAL_STATE_SORTED.'" />'.
	   				'<label for ="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_SORTED.'">sortiert</label><br />';
	   	// scanned
		$content .= '<input type="radio" name="'.$this->extKey.'[eval_state]" ';
	   	if ($lectureUID['eval_state']==tx_fsmivkrit_div::kEVAL_STATE_SCANNED) $content .= ' checked="checked" ';
	   	$content .= '				id="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_SCANNED.'" value="'.tx_fsmivkrit_div::kEVAL_STATE_SCANNED.'" />'.
	   				'<label for ="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_SCANNED.'">gescanned</label><br />';
	   	// anonymized
		$content .= '<input type="radio" name="'.$this->extKey.'[eval_state]" ';
	   	if ($lectureUID['eval_state']==tx_fsmivkrit_div::kEVAL_STATE_ANONYMIZED) $content .= ' checked="checked" ';
	   	$content .= '				id="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_ANONYMIZED.'" value="'.tx_fsmivkrit_div::kEVAL_STATE_ANONYMIZED.'" />'.
	   				'<label for ="'.$this->extKey.'_eval_state_'.tx_fsmivkrit_div::kEVAL_STATE_ANONYMIZED.'">alle Bilder getippt</label><br />';

	   	$content .= '</td></tr>';
		$content .= '</table>';
	   	$content .= '</fieldset>';

		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]"
				value="'.htmlspecialchars('Speichern').'">';
		$content .= '</form>';

		return $content;
	}

	function saveKritterData ($lecture) {
		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$lectureUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);

		// update Lecture
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
									'tx_fsmivkrit_lecture',
									'uid=\''.$lecture.'\'',
									array (	'crdate' => time(),
											'tstamp' => time(),
											'kritter_feuser_1' 	=> intval($GETcommands['kritter_feuser_1']),
											'kritter_feuser_2' 	=> intval($GETcommands['kritter_feuser_2']),
											'kritter_feuser_3' 	=> intval($GETcommands['kritter_feuser_3']),
											'kritter_feuser_4' 	=> intval($GETcommands['kritter_feuser_4']),
											'godfather'		=> intval($GETcommands['godfather']),
											'tipper'		=> intval($GETcommands['tipper']),
											'weight'		=> intval($GETcommands['weight']),
											'eval_state'	=> intval($GETcommands['eval_state'])
									));
		if (!$res) {
			return $content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_ERROR,
							'Daten konnten nicht gespeichert werden. Bitte informieren Sie den Administrator.');
		}
		else {
			return $content .= tx_fsmivkrit_div::printSystemMessage(
							tx_fsmivkrit_div::kSTATUS_INFO,
							'Vorlesung '.$lectureUID['name'].' wurde editiert.');
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi3/class.tx_fsmivkrit_pi3.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi3/class.tx_fsmivkrit_pi3.php']);
}

?>