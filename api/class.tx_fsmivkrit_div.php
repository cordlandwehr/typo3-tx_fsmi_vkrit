<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Andreas Cord-Landwehr (cola@uni-paderborn.de)
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
* This class provides a huge amount on utility functions, e.g. for database access...
*
* @author Andreas Cord-Landwehr <cola@uni-paderborn.de>
*/



require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');

/**
 * Script Class to download files as defined in reports
 *
 */
class tx_fsmivkrit_div {
	const kSTATUS_INFO 		= 0;
	const kSTATUS_WARNING 	= 1;
	const kSTATUS_ERROR 	= 2;
	const kSTATUS_OK 		= 3;
	const imgPath			= 'typo3conf/ext/fsmi_vkrit/gfx/'; // absolute path to images

	const kEVAL_STATE_CREATED	= 0;
	const kEVAL_STATE_NOTIFIED	= 1;
	const kEVAL_STATE_COMPLETED	= 2;
	const kEVAL_STATE_APPROVED	= 3;
	const kEVAL_STATE_EVALUATED	= 4;
	const kEVAL_STATE_FINISHED	= 5;
	
	/**
	 *
	 * @param integer $status from constants
	 * @param string $text information text
	 * @return string of HTML div box
	 */
	function printSystemMessage($status, $text) {
		
		// TODO it would be nice if the info boxes may be hidden on click
		
		$content = '';
		$content .= '<div style="min-height:30px; " ';
		switch ($status) {
			case self::kSTATUS_INFO: { 
				$content .= 'class="fsmivkrit_notify_info">';
				$content .=  '<img src="'.self::imgPath.'info.png" width="30" style="float:left; margin-right:10px;" />';
				break;
			}
			case self::kSTATUS_WARNING: { 
				$content .= 'class="fsmivkrit_notify_warning">'; 
				$content .=  '<img src="'.self::imgPath.'warning.png" width="30" style="float:left; margin-right:10px;" />';
				break;
			}
			case self::kSTATUS_ERROR: { 
				$content .= 'class="fsmivkrit_notify_error">'; 
				$content .=  '<img src="'.self::imgPath.'error.png" width="30" style="float:left; margin-right:10px;" />';
				break;
			}
			case self::kSTATUS_OK: { 
				$content .= 'class="fsmivkrit_notify_ok">'; 
				$content .=  '<img src="'.self::imgPath.'ok.png" width="30" style="float:left; margin-right:10px;" />';
				break; 
			}
		}
		// TODO switch $status
		$content .= $text;
		$content .= '</div>';
		
		return $content;
	}

	/**
	 * Creates a list of time steps from 7am to 8pm in steps of a quarter.
	 * @param comperator string to see if value should be marked as selected
	 * @return list of <option>...</option> entries for a HTML selector.
	 */
	function printOptionListTime($selected) {
		$content = '';
		for ($hour=7; $hour<20; $hour++)
			for ($min=0; $min<60; $min+=15) {
				$hour<10? $hourPrint='0'.$hour: $hourPrint=$hour;
				$min<10? $minPrint='0'.$min: $minPrint=$min;
				
				if ($selected==$hourPrint.':'.$minPrint)
					$content .= '<option selected="selected" value="'.$hourPrint.':'.$minPrint.'">'.$hourPrint.':'.$minPrint.'</option>'."\n";
				else
					$content .= '<option value="'.$hourPrint.':'.$minPrint.'">'.$hourPrint.':'.$minPrint.'</option>'."\n";
			}
		return $content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/api/class.tx_fsmivkrit_div.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/api/class.tx_fsmivkrit_div.php']);
}
?>