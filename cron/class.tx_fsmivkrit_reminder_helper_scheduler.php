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


class tx_fsmivkrit_reminder_helper_scheduler extends tx_scheduler_Task {
	var $uid;

	/**
	 * next function fixes PHP4 issue
	 */
// 	function tx_cal_calendar_scheduler() {
// 		$this->__construct();
// 	}

	public function execute() {

		// dirty hack
		$survey = 3;

		$fullMail =
'Hier einer Erinnerung, denn du hast dich/wurdest
für morgen zum Kritten eingetragen:'."\n";

		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT fe_users.uid as kritter,
													fe_users.name as krittername,
													fe_users.username as kritterusername,
													tx_fsmivkrit_lecture.uid as lectureUid
												FROM fe_users, tx_fsmivkrit_lecture
												WHERE fe_users.deleted=0
													AND tx_fsmivkrit_lecture.deleted=0
													AND tx_fsmivkrit_lecture.hidden=0
													AND tx_fsmivkrit_lecture.eval_date_fixed > '.time().'
												    AND eval_date_fixed < '.(time()+5*24*60*60).'
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND (
														fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_1
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_2
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_3
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_4)
												GROUP BY kritter, fe_users.name, fe_users.username
												ORDER BY number
													');

		while ($res && $row = mysql_fetch_assoc($res)) {
			$mailPartIndividual = '';
			foreach ($row['lectureUid'] as $lecture) {
				$lectureDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
				$mailPartIndividual .= '* '.$lectureDATA['name']."\n";
				$mailPartIndividual .= '  '.$lectureDATA['participants'].' Teilnehmer'."\n";
				$mailPartIndividual .= '  '.date('d.m.Y. / H:i',$lectureDATA['eval_date_fixed']).' / '.$lectureDATA['eval_room_fixed']."\n";
			}

			$send = $this->sendNotifyEmail (
				$msg='V-Krit Kritter Erinnerung'."\n". // first line is subject
						$fullMail,
				$recipients='cola@uni-paderborn.de',
				$cc='cola@upb.de',
				$email_from='criticus@uni-paderborn.de',
				$email_fromName='V-Krit Orga',
				$replyTo='');
		}
		return true;
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/cron/class.tx_fsmivkrit_reminder_helper_scheduler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/cron/class.tx_fsmivkrit_reminder_helper_scheduler.php']);
}
?>