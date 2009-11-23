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
	
	const kLOCKTIME		= 300;	// system locking time in seconds
	
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
	
		//TODO 
		$survey = 1;
		
		$content .= '<h1>Coordination</h1>';
		
		$content = '<h2 align="center">VKrit-Übersicht</h2>';
		$content .= $this->printTable($survey);
		
		// TODO here: check if edit, admin etc.
		
	
		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
	 * This function locks modification of survey 
	 * @return unknown_type
	 */
	function unlock() {
		// TODO unlock survey modification
		// war irgendwas mit lock auf 0	
		//if (!$mysqlfs->query("update VkritLock set locktime=".time()." where formular like 'vkritplan'")) echo 'ERR1';
	}
	
	function lock() {
		//TODO
		//if (!$mysqlfs->query("update VkritLock set locktime=".time()." where formular like 'vkritplan'")) echo 'ERR1';
	}
	
	function islocked() {
		// TODO implement
//    		global $mysqlfs, $locktime;
//    		if ($mysqlfs->query("select * from VkritLock where formular like 'vkritplan'")) {
//    			if ($row = $mysqlfs->fetch()) {
//    				return ($row->locktime != 0) && (time() - $row->locktime < $locktime);
//    			}
//    			$mysqlfs->free();
//    		} else echo 'ERR3';
//    		return false;
		return false;
    }
    
	function getlocktime() {
		//TODO implement
//    			global $mysqlfs;
//   			if ($mysqlfs->query("select * from VkritLock where formular like 'vkritplan'")) {
//    				if ($row = $mysqlfs->fetch()) {
//    					if ($row->locktime == 0) return 0;
//    					return time() - $row->locktime;
//    				}
//    				$mysqlfs->free();
//    			} else echo 'ERR4';
//    			return 0;
		return 0;
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
		$content .= '	<td align="center" style="color:white; border-left:4px solid black"><b>Bilder</b></td>';
  	
		$content .= '	<td align="center" style="color:white"><b>Tipper</b></td>';
		$content .= '	<td align="center" style="color:white; border-left:4px solid black"><b>Getippt</b></td>';
//		$content .= '	<td align="center" style="color:white"><b>am korrigieren</b></td>';
//		$content .= '	<td align="center" style="color:white"><b>bereit zum verschicken</b></td>';
		$content .= '</tr>';
		
		return $content ;
   	}

/*
   		$zuspaet = false;
   		if (isset($_REQUEST['eintragen']) || isset($_REQUEST['verwerfen'])) {
   			if ((time() - $_REQUEST['edittime']) > $locktime) $zuspaet = true;
   		}
   		
   		if (isset($_REQUEST['eintragen'])) {
   		
   			if (!$zuspaet) {
   		
   				$idList = array();
   				if ($mysqlfs->query('select id from VkritPlan')) {
   				while ($row = $mysqlfs->fetch()) {
   						array_push($idList, $row->id);
   					}
   					$mysqlfs->free();
  				}	
   	
   				foreach ($idList as $muell => $id) {//Dozent
   	
   					$q = 'update VkritPlan set ';
   	
   					for ($i = 1; $i < 5; $i++)
   						$q .= 'kritter'.$i." = '".htmlspecialchars($_REQUEST['kritter'.$id.'_'.$i])."', ";
   	
   					$q .= 'gewicht = '.null(htmlspecialchars($_REQUEST['gewicht'.$id])).', ';
   	
   					$q .= 'getippt = '.$nulleins[isset($_REQUEST['getippt'.$id])].', ';
   	
   					$q .= 'bilder = '.null(htmlspecialchars($_REQUEST['bilder'.$id])).', ';
  	
   					$q .= 'tipper = '.htmlspecialchars($_REQUEST['tipper'.$id]).', ';
   					$q .= 'pate = '.htmlspecialchars($_REQUEST['pate'.$id]).' ';
   	
   					
   					
   					if ($_REQUEST['adminform'] == 1) {
   					
   						$q .= ", date = '".htmlspecialchars($_REQUEST['date'.$id])."', ";
   						$q .= "time = '".htmlspecialchars($_REQUEST['time'.$id])."', ";
   						$q .= "raum = '".htmlspecialchars($_REQUEST['raum'.$id])."', ";
  						$q .= "vorlesung = '".htmlspecialchars($_REQUEST['vorlesung'.$id])."', ";
 						$q .= 'dozent = '.htmlspecialchars($_REQUEST['dozent'.$id]).', ';
 						$q .= "teilnehmer = ".null(htmlspecialchars($_REQUEST['teilnehmer'.$id])).", ";
   						$q .= "kommentar = '".htmlspecialchars($_REQUEST['kommentar'.$id])."', ";
 						$q .= 'gedruckt = '.$nulleins[isset($_REQUEST['gedruckt'.$id])].', ';
  						$q .= 'verteilt = '.$nulleins[isset($_REQUEST['verteilt'.$id])].' ';
   					
   					}
   	
   					$q .= 'where id = '.$id;
   	
   	//				echo $q.'<br>';
  					if (!$mysqlfs->query($q)) {
   					$efehler = 1;
   						break;
  					}
   				}
   
  				if ($fehler == 1)	
  					echo '<h3 align="center"><font color="#ff0000">Fehler beim Eintragen der Daten</font></h3>';
   			else
   					echo '<h3 align="center"><font color="#00aa00">Daten erfolgreich eingetragen</font></h3>';
   	
   				unlock();
   				
   			} else {
   				echo '<h3 align="center"><font color="#ff0000">Daten zu sp�t eingetragen (Tabelle war schon wieder freigegeben!)</font></h3>';
   			}
   		} else
   		if (isset($_REQUEST['verwerfen'])) {
   			if (!$zuspaet) unlock();
   		}
   			
   		if ($edit && islocked()) {
   			$edit = false;	
   		}
   			
   		if (!$edit) $admin = false;
   			
   		if ($edit) {
   		
   			lock();
   		
  			$tipperList = array();
   			if ($mysqlfs->query('select * from VkritTipper order by name')) {
   				while ($row = $mysqlfs->fetch()) {
   					array_push($tipperList, array($row->name, $row->id));
   				}
   				$mysqlfs->free();
   			}	
   	
   			$dozentList = array();
   			if ($mysqlfs->query('select * from VkritDozent order by name')) {
   				while ($row = $mysqlfs->fetch()) {
   					array_push($dozentList, array($row->name, $row->id));
   				}
   				$mysqlfs->free();
   			}	
   ENDE PHP
   					
   	<script type="text/javascript">
   	
  	    function alarm() {
   	       alert('Achtung\n\n' +
   	             'Du hast noch eine Minute Zeit, die Daten einzutragen bevor die Tabelle\n' +
   				 'wieder freigegeben wird und du die Daten nicht mehr eintragen kannst.');
   	    }
   	    window.setTimeout("alarm()", 4*60000);
   	//
   	</script>
   	
   	PHP		
   			echo '<h2 align="center">VKrit-�bersicht �ndern</h2>';
   	
   		} else {*/
   		

	function printTable ($survey) {
	
		// check if table is locked
		if ($this->islocked()) {
   				
   				$content .= '<h3 align="center"><font color="#ff0000">Die Tabelle zur Zeit gesperrt</font></h3>';
   				$content .= '<h4 align="center"><font color="#ff0000">Sie wird spätestens in '.
   					($locktime - $this->getlocktime()).' Sek. freigegeben</font></h4>';
   		} 	
 		// table head
 		
   		//TODO next must be reworked: edit
//	   	if ($edit) {
//   			echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
//   			echo '<div style="text-align:center;margin:20px"><input type="submit" name="eintragen" value=" Eintragen ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="verwerfen" value=" Verwerfen "></div>';
//   			echo '<input type="hidden" name="edittime" value="'.time().'">';
//  		}
   		//TODO here we need the current number of pictures per evaluater
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
												FROM tx_fsmivkrit_lecture 
												WHERE deleted=0 
													AND hidden=0
													AND survey= \''.$survey.'\'
													AND eval_state BETWEEN '.
														tx_fsmivkrit_div::kEVAL_STATE_APPROVED.
														' AND '.tx_fsmivkrit_div::kEVAL_STATE_FINISHED.'
													AND NOT eval_date_fixed=0 
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
	  		$content .= '<td align="left">'.$row['name'].'</td>';
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
	
				$content .= '<td align="center" style="border-left:4px solid black">'.$this->nix($row['godfather']).'</td>';
				$content .= '<td align="center">'.$this->nix($this->ohnenull($row['weight'])).'</td>';
				$content .= '<td align="center" style="border-left:4px solid black">'.$this->nix($this->ohnenull($row['pictures'])).'</td>';
				$content .= '<td align="center">'.$this->nix($row['tipper']).'</td>';
				// TODO check by state!
				$content .= '<td align="center" style="border-left:4px solid black">'.$this->nix($this->ohnenull($getippt)).'</td>';
			}		
		}	
		
		$content .= '</table>';
		
		return $content;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi3/class.tx_fsmivkrit_pi3.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi3/class.tx_fsmivkrit_pi3.php']);
}

?>