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

require_once(t3lib_extMgm::extPath('fsmi_vkrit').'api/class.tx_fsmivkrit_div.php');

class tx_fsmivkrit_reminder_helper_scheduler extends tx_scheduler_Task {
	var $uid;
    var $emailOrganizer;
    var $emailHelper;

	/**
	 * next function fixes PHP4 issue
	 */
// 	function tx_cal_calendar_scheduler() {
// 		$this->__construct();
// 	}

	public function execute() {

        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fsmi_vkrit']);
        $this->emailOrganizer = ($confArr['emailHelper'] ? $confArr['emailHelper'] : 'organizer@nomail.com');   
        $this->emailHelper = ($confArr['emailHelper'] ? $confArr['emailHelper'] : 'helper@nomail.com');

		// dirty hack
		$survey = 4; //FIXME

		$mailClosing = "\n\n".'Rückfragen bitte an <'.$this->emailOrganizer.'>'."\n\n".'    Vielen Dank, deine V-Krit Orga';

		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT fe_users.uid as user,
													GROUP_CONCAT(DISTINCT tx_fsmivkrit_lecture.uid ORDER BY eval_date_fixed SEPARATOR \',\') as lectures
												FROM fe_users, tx_fsmivkrit_lecture
												WHERE fe_users.deleted=0
													AND tx_fsmivkrit_lecture.deleted=0
													AND tx_fsmivkrit_lecture.hidden=0
													AND tx_fsmivkrit_lecture.eval_date_fixed > '.time().'
												    AND eval_date_fixed < '.(time()+24*60*60).'
													AND tx_fsmivkrit_lecture.survey = \''.$survey.'\'
													AND (
														fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_1
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_2
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_3
														OR fe_users.uid = tx_fsmivkrit_lecture.kritter_feuser_4)
												GROUP BY user
													');

		while ($res && $row = mysql_fetch_assoc($res)) {
			$fe_user = t3lib_BEfunc::getRecord('fe_users', $row['user']);
			$fullMail =
				'Hallo '.$fe_user['name'].','."\n".
				'du bist morgen für die folgenden Veranstaltungen zum Kritten eingetragen:'."\n";

			$lectures = explode(',',$row['lectures']);
			$mailPartIndividual = '';
			foreach ($lectures as $lecture) {
				$lectureDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
				$mailPartIndividual .= '* '.$lectureDATA['name']."\n";
				$mailPartIndividual .= '  '.$lectureDATA['participants'].' Teilnehmer'."\n";
				$mailPartIndividual .= '  '.tx_fsmivkrit_div::weekdayLong(date('N',$lectureDATA['eval_date_fixed']))." / ".
					date('d.m.Y / H:i',$lectureDATA['eval_date_fixed']).' / '.
					$lectureDATA['eval_room_fixed']."\n";
			}

//TODO check if this is really a mail-address
			$send = $this->sendNotifyEmail (
				$msg='V-Krit Kritter Erinnerung '."\n". // first line is subject
						$fullMail."\n".$mailPartIndividual.$mailClosing,
				$recipients=$fe_user['email'],
				$cc='',
				$email_from=$this->emailOrganizer,
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