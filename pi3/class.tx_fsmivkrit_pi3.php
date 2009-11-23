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
	

		
		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$this->survey = intval($GETcommands['survey']);
		
		//TODO 
		$this->survey = 1;
		
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
				break;
			}
			default: {	// could also be kLIST
				$content .= '<h2>Koordination - Übersicht</h2>';
				$content .= $this->printTable($this->survey);
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
	
	//TODO change layout, text, LL etc.
	function printTableHead() {
		$content .= '<tr bgcolor="#526FEB">';
		$content .= '	<td align="center" style="color:white"><b>Tag</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Zeit</b></td>';
		$content .= '	<td align="center" style="color:white; border-right:4px solid black;"><b>Raum</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Vorlesung</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Dozent</b></td>';
		$content .= '	<td align="center" style="color:white"><b>#</b></td>';
		$content .= '	<td align="center" style="color:white; border-right:4px solid black"><b>Kommentar</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Kritter&nbsp;1</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Kritter&nbsp;2</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Kritter&nbsp;3</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Kriter&nbsp;4</b></td>';
		$content .= '	<td align="center" style="color:white; border-left:4px solid black"><b>PATE</b></td>';
		$content .= '	<td align="center" style="color:white"><b>Gewicht</b></td>';
//		$content .= '	<td align="center" style="color:white; border-left:4px solid black"><b>Bilder</b></td>';
  	
		$content .= '	<td align="center" style="color:white"><b>Tipper</b></td>';
//		$content .= '	<td align="center" style="color:white; border-left:4px solid black"><b>Getippt</b></td>';
//		$content .= '	<td align="center" style="color:white"><b>am korrigieren</b></td>';
//		$content .= '	<td align="center" style="color:white"><b>bereit zum verschicken</b></td>';
		$content .= '	<td align="center" style="color:white"><b>EDIT</b></td>';
		$content .= '</tr>';
		
		return $content ;
   	}
   	 		

	function printTable ($survey) {
 		// table head

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

		$count = 0;

		$rowcol = array(true => array(0 => '#ff9e9e', 1 => '#ffcbcb'), 
  						false => array(0 => '#d9e2ec', 1 => '#eaedf4'));
   		$olddate = '';
   		$vor15minuten = mktime()-15*60;
			
		$content .= '<table style="border: 2px solid #000; border-collapse: collapse;" border="1" align="center" cellpadding="3" cellspacing="0">';
		$content .= $this->printTableHead();
		
		while ($res && $row = mysql_fetch_assoc($res)) {
	
			//TODO überarbeiten!
			if ($row['eval_date_fixed'] < $vor15minuten) $old = true; else $old = false;
	
			// this tests if date is new and should be displayed (only disply once)
			$newdate = date('D d.m.',$row['eval_date_fixed']);
	   		if ($olddate == $newdate) 
	   			$newdate = '&nbsp;<br />&nbsp;';
	  		else {
	   			$olddate = $newdate;
	   			$this->printTableHead();
	  		}	
	   				
	  		// set row color
	   		switch ($row['eval_state']) {
	   			case tx_fsmivkrit_pi2::kEVAL_STATE_APPROVED: {
	   				$content .= '<tr bgcolor="#FFE500">'; break; // yellow not correct one
	   				//TODO colorize each second row by ... light/dark red
	   			}
	   			case tx_fsmivkrit_pi2::kEVAL_STATE_EVALUATED: $content .= '<tr bgcolor="#99FF99">'; break; // light green
	   			case tx_fsmivkrit_pi2::kEVAL_STATE_FINISHED: $content .= '<tr bgcolor="#00B233">'; break; // dark green: finished
	   			default: $content .= '<tr>';
	   		}
				
	   		// get lecturer
	   		$lecturerUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecturer', $row['lecturer']);
	   		
	   		// print row
			$content .= '<td align="left">'.$this->nix($newdate).'</td>';
	   		$content .= '<td align="center">'.date('H:i',$row['eval_date_fixed']).'</td>';
			$content .= '<td align="center" style="border-right:4px solid black">'.$row['eval_room_fixed'].'</td>';
	  		$content .= '<td align="left">'.$this->pi_linkTP($row['name'],
														array (	
															$this->extKey.'[survey]' => $this->survey,
															$this->extKey.'[lecture]' => $row['uid'],
															$this->extKey.'[type]' => self::kASSIGN_KRITTER_FORM
														)).'</td>';
			$content .= '<td align="left">'.$lecturerUID['name'].'</td>';
			$content .= '<td align="center">'.$row['participants'].'</td>';
			$content .= '<td align="left" style="border-right:4px solid black">'.$row['comment'].'</td>';
	
	   				
			if ($this->edit) {
				// kritter
				for ($i = 1; $i < 5; $i++) {
					$content .= '<td align="center"><input style="background-color:';
	   				if (trim($row['kritter_'.$i]) == '') 
	   					$content .= '#FFFFFF'; else echo $rowcol[$old][$count % 2];
	   					//TODO not valid style
	   				$content .= ';" type="text" name="kritter_'.$row['uid'].'_'.$i.'" value="'.$row['kritter_'.$i].'" size="8" maxlength="32"></td>';
	   			}
	   			
	  			
	   			$content .= '<td align="center" style="border-left:4px solid black"><select style="background-color:';
	  			if ($row['weight'] == 0) $content .= '#FFFFFF'; 
	  			else $content .= $rowcol[$old][$count % 2];
	  			
	  			if ($row['godfather']== '') $content .= '#FFFFFF'; 
	  			else $content .= $rowcol[$old][$count % 2];
	
	  			// selection for helper
	  			$content .=  ';" name="godfather'.$row->id.'" size="1">';
	   			$content .= '<option value="0"></option>';
				$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
													FROM tx_fsmivkrit_helper 
													WHERE deleted=0 AND hidden=0
													ORDER BY name');
	   					while ($res && $rowHelper = mysql_fetch_assoc($res)) {
	   						// TODO change submit value
	   						$content .= '<option value="'.$rowHelper['uid'].'" '.$selected[$t[1] == $row->pate].' >'.$rowHelper['name'].'</option>';
	   					}
	   			$content .= '</select></td>';
	   					
	   			// weight
	   			$content .= '<td align="center"><input style="background-color:';
	   			if ($row->gewicht == 0) $content .= '#FFFFFf'; 
	   			else $content .= $rowcol[$old][$count % 2];
	   			$content .= ';" type="text" name="weight'.$row['uid'].'" value="'.ohnenull($row['weight']).'" size="5" maxlength="4"></td>';
	   			
	   			// number of pictures		
	   			$content .= '<td style="border-left:4px solid black" align="center"><input style="background-color:';
	   			if ($row->bilder == 0) $content .= '#FFFFFF'; else $content .= $rowcol[$old][$count % 2];
	   			$content .= ';" type="text" name="bilder'.$row['uid'].'" value="'.ohnenull($row['pictures']).'" size="5" maxlength="4"></td>';
	   				
	  			// selection for tipper
	  			$content .=  ';" name="tipper'.$row->id.'" size="1">';
	   			$content .= '<option value="0"></option>';
				$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
													FROM tx_fsmivkrit_helper 
													WHERE deleted=0 AND hidden=0
													ORDER BY name');
	   					while ($res && $rowHelper = mysql_fetch_assoc($res)) {
	   						// TODO change submit value
	   						$content .= '<option value="'.$rowHelper['uid'].'" '.$selected[$t[1] == $row->pate].' >'.$rowHelper['name'].'</option>';
	   					}
	   			$content .= '</select></td>';
	
	   			//TODO needs to recrteate		
	   			$content .= '<td style="border-left:4px solid black" align="center"><input type="checkbox" name="getippt'.$row->id.'" value="1" '.$checked[$row->getippt == 1].' ></td>';
	   	
			}
			// no edit
			else {
	  			for ($i = 1; $i < 5; $i++)
					$content .= '<td align="center">'.$this->nix($row['kritter_'.$i]).'</td>';
	
				$godfatherUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_helper', $row['godfather']);
				$content .= '<td align="center" style="border-left:4px solid black">'.$godfatherUID['name'].'</td>';
				$content .= '<td align="center" style="border-left:4px solid black">'.$this->nix($this->ohnenull($row['weight'])).'</td>';
//				$content .= '<td align="center" style="border-left:4px solid black">'.$this->nix($this->ohnenull($row['pictures'])).'</td>';
				$tipperUID = t3lib_BEfunc::getRecord('tx_fsmivkrit_helper', $row['tipper']);
				$content .= '<td align="center">'.$tipperUID['name'].'</td>';
				// TODO check by state!
//				$content .= '<td align="center" style="border-left:4px solid black">'.$this->nix($this->ohnenull($getippt)).'</td>';
	  			$content .= '<td align="left">'.$this->pi_linkTP('editieren',
														array (	
															$this->extKey.'[survey]' => $this->survey,
															$this->extKey.'[lecture]' => $row['uid'],
															$this->extKey.'[type]' => self::kASSIGN_KRITTER_FORM
														)).'</td>';
			}		
		}	
		
		$content .= '</table>';
		
		return $content;
	}
	
	function printLectureEditForm($lecture) {
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
							<input type="text" name="'.$this->extKey.'[kritter_'.$i.']" id="'.$this->extKey.'_kritter_'.$i.'"  	
								value="'.$lectureUID["kritter_".$i].'" size="20" />
							</td></tr>';
		}
		
		// Godfather
	   	$content .= '<tr><td><label for="'.$this->extKey.'_godfather">Pate</label></td><td>
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
											'kritter_1' 	=> htmlspecialchars($GETcommands['kritter_1']),
											'kritter_2' 	=> htmlspecialchars($GETcommands['kritter_2']),
											'kritter_3' 	=> htmlspecialchars($GETcommands['kritter_3']),
											'kritter_4' 	=> htmlspecialchars($GETcommands['kritter_4']),
											'godfather'		=> intval($GETcommands['godfather']),
											'tipper'		=> intval($GETcommands['tipper']),
											'weight'		=> intval($GETcommands['weight'])
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