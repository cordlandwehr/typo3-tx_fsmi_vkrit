<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 Andreas Cord-Landwehr
 * Fachschaft Mathematik/Informatik, Uni Paderborn
 *
 * You can redistribute this file and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation;
 * either version 2 of the License, or (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This file is distributed in the hope that it will be useful for ministry,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the file!
 ***************************************************************/


class tx_fsmivkrit_reminder_organizer_scheduler extends tx_scheduler_Task {
	var $uid;

	/**
	 * next function fixes PHP4 issue
	 */
// 	function tx_cal_calendar_scheduler() {
// 		$this->__construct();
// 	}

	public function execute() {

		$fullmail = 'ping';

		$send = $this->sendNotifyEmail (
			$msg='[TEST] Organizer Notification'."\n". // first line is subject
					$fullMail,
			$recipients='cola@upb.de',
			$email_from='criticus@uni-paderborn.de',
			$email_fromName='Orga V-Krit <criticus@uni-paderborn.de>',
			$replyTo='');

		if ($send)
			return true;
		else
			return false;

		// if we come here, something went really wrong
		return false;
	}

	/**
	 * This is a full copy of tslib::sendNotifyEmail, but without frontend-lib usage
	 **/
	private function sendNotifyEmail($msg, $recipients, $cc, $email_from, $email_fromName='', $replyTo='')  {
		// Sends order emails:
		$headers=array();
		if ($email_from)        {$headers[]='From: '.$email_fromName.' <'.$email_from.'>';}
		if ($replyTo)           {$headers[]='Reply-To: '.$replyTo;}

		$recipients=implode(',',t3lib_div::trimExplode(',',$recipients,1));

		$emailContent = trim($msg);
		if ($emailContent)      {
			$parts = @split(chr(10),$emailContent,2);                // First line is subject //TODO change split to something else
			$subject=trim($parts[0]);
			$plain_message=trim($parts[1]);

			if ($recipients)        t3lib_div::plainMailEncoded($recipients, $subject, $plain_message, implode(chr(10),$headers));
			if ($cc)        t3lib_div::plainMailEncoded($cc, $subject, $plain_message, implode(chr(10),$headers));
			return true;
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/cron/class.tx_fsmivkrit_reminder_organizer_scheduler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/cron/class.tx_fsmivkrit_reminder_organizer_scheduler.php']);
}
?>
