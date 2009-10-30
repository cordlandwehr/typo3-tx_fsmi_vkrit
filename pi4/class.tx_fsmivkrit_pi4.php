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
	
	// types
	var $kIMPORT		= 1;
	var $kEXPORT		= 2;
	
	// csv Columns
	var $kCSV_FUNKTION 		= 0;
	var $kCSV_ANREDE 		= 1;
	var $kCSV_TITEL			= 2;
	var $kCSV_VORNAME		= 3;
	var $kCSV_NACHNAME		= 4;
	var $kCSV_EMAIL			= 5;
	var $kCSV_LV_NAME		= 6;
	var $kCSV_LV_KENNUNG	= 7;
	var $kCSV_LV_ORT		= 8;
	var $kCSV_STUDIENGANG	= 9;
	var $kCSV_LV_ART		= 10;
	var $kCSV_TEILNEHMER	= 11;
	var $kCSV_ORGAEINHEIT	= 12;

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
		
		
		// type selection head
		$content .= $this->createTypeSelector();
		
		// select input type
		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		switch (intval($GETcommands['type'])) {
			case $this->kIMPORT: {
				// check for POST data
				if (t3lib_div::_POST($this->extKey)) {
					// form files
					$formDataFiles = $_FILES[$this->extKey];
					
					// get files
					if ($GETcommands['file_confirmed']) {
						$csvArray = $this->loadImportData(htmlspecialchars($GETcommands['file_confirmed']));
						t3lib_div::unlink_tempfile($GETcommands['file_confirmed']);
					} else {
						$filepath = t3lib_div::upload_to_tempfile($formDataFiles['tmp_name']['file']);		
						$csvArray = $this->loadImportData($filepath);
					}
						
					// set status message
					if (count($csvArray)==0)
						$content .= tx_fsmivkrit_div::printSystemMessage(
								tx_fsmivkrit_div::kSTATUS_ERROR, 
								'Leere Datei/konnte Datei nicht lesen');
					else {
						// is there a confirmed filed
						if ($GETcommands['file_confirmed']) {
							$this->saveImportData($csvArray, intval($GETcommands['storage']));
							$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_INFO, 
									'Daten gespeichert.');
							$content .= $this->printImportData($csvArray);
						} else {
							$content .= tx_fsmivkrit_div::printSystemMessage(
									tx_fsmivkrit_div::kSTATUS_INFO, 
									'Datei Eingelesen, aber noch nicht gespeichert!');
							$content .= $this->createImportDataConfirmForm($filepath);
							$content .= $this->printImportData($csvArray);
						}
					}
				}
				else
					$content .= $this->createImportDataForm();
				break;
			}
			default: 
				break;
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function createTypeSelector () {
		$content = '<div>';
		$content .= $this->pi_linkTP('Import PAUL Data', 
								array (	$this->extKey.'[type]' => $this->kIMPORT));
		$content .= ' | ';
		$content .= $this->pi_linkTP('Export EvaSys Data', 
								array (	$this->extKey.'[type]' => $this->kEXPORT));
		$content .= '</div>';
		
		return $content;
								
	}
	
	function createImportDataForm() {
		$content = '';
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';
		
		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value='.$this->kIMPORT.' />';
		
		$content .= '<fieldset>
			<label for="'.$this->extKey.'_file">Importdatei:</label>
			<input type="file" name="'.$this->extKey.'[file]" id="'.$this->extKey.'_file"  	
					value="'.htmlspecialchars($this->piVars["file"]).'" />
			<div>
				Erwartet wird eine CSV Datei mit EXAKT den spezifizierten Werten. 
				Vor einer endgültigen Speicherung werden die zu speichernden Daten angezeigt.<br />
				<pre>Funktion,Anrede,Titel,Vorname,Nachname,Email,LV-Name,LV-Kennung,LV-Ort,Studiengang,LV-Art,Teilnehmer,Orgaeinheit</pre>
				Aufgrund desolater PAUL-Daten muss jede VL exakt einmal in der Liste stehen. Die erste Zeile wird ignoriert.				
			</div>
			</fieldset>
			
			<fieldset>
				<label for="'.$this->extKey.'_import_survey">Umfrage:</label>
				<select name="'.$this->extKey.'[survey]" id="'.$this->extKey.'_survey"  	
					value="'.htmlspecialchars($this->piVars["survey"]).'">
					';
		
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT * 
												FROM tx_fsmivkrit_survey 
												WHERE deleted=0 AND hidden=0');
		
		while ($res && $row = mysql_fetch_assoc($res))
			$content .= '<option value="'.$row['uid'].'">'.$row['semester'].' - '.$row['name'].'</option>';
		
		$content .= '</select>';
		$content .= '<div>Die entsprechende Umfrage muss über das Backend bereits angelegt worden sein.</div>
			</fieldset>';
		
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]" 
				value="'.htmlspecialchars('Datei überprüfen').'">';
		$content .= '</form>';
		
		return $content;	
	}
	
	function createImportDataConfirmForm ($filepath) {
		$content = '';
		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'" method="POST" enctype="multipart/form-data" name="'.$this->extKey.'">';
		
		// hidden field to tell system, that IMPORT data is coming
		$content .= '<input type="hidden" name="'.$this->extKey.'[type]'.'" value='.$this->kIMPORT.' />';
		$content .= '<input type="hidden" name="'.$this->extKey.'[file_confirmed]'.'" value="'.$filepath.'" />';
		$content .= '<input type="submit" name="'.$this->extKey.'[submit_button]" 
				value="'.htmlspecialchars('Import abschließen').'">';
		
		$content .= '</form>';
		
		return $content;
	}
	
	/**
	 * Function reads CSV File from $_FILES[fsmi_vkrit][file] and
	 * imports everything as array
	 * 
	 * @return array $csvArray
	 */
	function loadImportData ($filepath) {		
		if ($filepath=='')
			return array ();
			
		$csvArray = array ();	
		$file = fopen($filepath, 'r');
		while (($data = fgetcsv($file)))
    		array_push($csvArray, $data);
    	fclose($file);
		
		return $csvArray;
	}
	
	function printImportData ($csvArray) {
		$content = '<table>';
		for ($i=0; $i<count($csvArray); $i++)
			$content .= '<tr>'.
				'<td>'.$csvArray[$i][$this->kCSV_NACHNAME].'</td>'.
				'<td>'.$csvArray[$i][$this->kCSV_EMAIL].'</td>'.
				'<td>'.$csvArray[$i][$this->kCSV_LV_NAME].'</td>'.
				'</tr>';
		$content .= '</table>';
				
		return $content;
	}
	
	function saveImportData ($csvArray, $storage) {
		// dismiss first line
//TODO until now everything prepared to create lecturers and lectures
//		for ($i=1; $i<count($csvArray); $i++) {
//		
//			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(	
//									'tx_fsmivkrit_lecture',
//									array (	'pid' => $this->storageLecturer,
//											'crdate' => time(),
//											'tstamp' => time(),
//											'firstname' => $GLOBALS['TYPO3_DB']->quoteStr($formData['firstname'], 'tx_fsmiexams_lecturer'),
//											'lastname' => $GLOBALS['TYPO3_DB']->quoteStr($formData['lastname'], 'tx_fsmiexams_lecturer'),
//									));
//		//TODO check if $res exists 
//		}
	}
	
}




if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi4/class.tx_fsmivkrit_pi4.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/pi4/class.tx_fsmivkrit_pi4.php']);
}

?>