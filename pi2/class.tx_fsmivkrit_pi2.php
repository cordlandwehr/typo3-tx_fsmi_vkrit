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
	
	// global const
	const kLIST			= 1;
	const kASK_INPUT 	= 2;
	
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
		
		$content = '';

		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		
		// type selection head
		$content .= $this->createTypeSelector();
		
		// subselecter head
		$this->survey = intval($GETcommands['survey']);
		$content .= $this->createSurveySelector();
		
		// select input type
		
		switch (intval($GETcommands['type'])) {
			case self::kLIST: {
				// check for POST data
				debug('adsf');
				break;
			}
			default: 
				$content .= '<div>Diese Ansicht dient zur Organisation der Eintragung, Information der
				Dozenten und Bearbeitung der Eintragungen.</div>';
				break;
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function createSurveySelector () {
		
		// no survey selected, yet
		if ($this->survey==0) {
			$content = '<div>Wähle Umfrage:<ul>';
			
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
				$content .= '<div>Aktuelle Umfrage: '.$row['name'].' - '.$row['semester'].'</div>';
		}
		return $content;
	}
	
	function createTypeSelector () {
		$content = '<div>';
		$content .= $this->pi_linkTP('Listenansicht', 
								array (	$this->extKey.'[type]' => self::kLIST));
		$content .= ' | ';
		$content .= $this->pi_linkTP('Eintragung anfordern (alle)', 
								array (	$this->extKey.'[type]' => self::kASK_INPUT));
		$content .= '</div>';
		
		return $content;							
	}
	
	function printLectureList() {
		if ($this->survey==0)
			return '';
		
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
												FROM tx_fsmivkrit_lecture 
												WHERE deleted=0 AND hidden=0
												AND survey=\''.$this->survey.'\'');
			while ($res && $row = mysql_fetch_assoc($res))
				$content .= '<div>Aktuelle Umfrage: '.$row['name'].' - '.$row['semester'].'</div>';
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi2/class.tx_fsmivkrit_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi2/class.tx_fsmivkrit_pi2.php']);
}

?>