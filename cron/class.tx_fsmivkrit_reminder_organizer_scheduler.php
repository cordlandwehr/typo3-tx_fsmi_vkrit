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

		// dirty hack
		$survey = 3;

		$fullMail =
'Statusinformationen zur V-Krit:'."\n".
'=============='."\n\n";

		// state problems first
		$lecturesWithoutKritter = $this->lecturesWithoutKritter($survey,7);//TODO set survey
		if (count($lecturesWithoutKritter)>0)
			$fullMail .=
'Veranstaltungen ohne Kritter (kommende 7 Tage):'."\n".
'--------------'."\n";
		foreach ($lecturesWithoutKritter as $lecture) {
			$lectureDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
			$fullMail .= '* '.$lectureDATA['name']."\n";
			$fullMail .= '  '.$lectureDATA['participants'].' Teilnehmer'."\n";
			$fullMail .= '  '.date('d.m.Y / H:i',$lectureDATA['eval_date_fixed']).' / '.$lectureDATA['eval_room_fixed']."\n";
		}
  		if (count($lecturesWithoutKritter)==0)
			$fullMail .= ' -- keine --'."\n";
		$fullMail .= "\n";

		// next the status information
		// state problems first
		$lecturesAll = $this->lecturesInNextDays($survey,7);//TODO set survey
		if (count($lecturesAll)>0)
			$fullMail .=
'Alle Veranstaltungen (kommende 7 Tage):'."\n".
'--------------'."\n";
		foreach ($lecturesAll as $lecture) {
			$lectureDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
			$fullMail .= '* '.date('d.m.-H:i',$lectureDATA['eval_date_fixed']).' '.
				$lectureDATA['name'].
				' ('.$lectureDATA['eval_room_fixed'].' / '.$lectureDATA['participants'].'TN)'."\n";
		}
		if (count($lecturesAll)==0)
			$fullMail .= ' -- keine --'."\n";
		$fullMail .= "\n";

		// statistical/administrative data
		$fullMail .=
'Allgemeines'."\n".
'--------------'."\n";
		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT SUM(participants)
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0 AND hidden=0
												  AND survey='.$survey.'
												  AND eval_date_fixed > '.time());
		if ($res && $row = mysql_fetch_assoc($res))
			$fullMail .= '* insgesamt noch benötigte Bögen: '.$row['SUM(participants)']."\n";

		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT SUM(participants)
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0 AND hidden=0
												  AND survey='.$survey.'
												  AND eval_date_fixed > '.time().'
												  AND eval_date_fixed < '.(time()+7*24*60*60));
		if ($res && $row = mysql_fetch_assoc($res))
			$fullMail .= '* benötigte Bögen in kommenden 7 Tagen: '.($row['SUM(participants)']!=''? $row['SUM(participants)'] : 0)."\n";

		$send = $this->sendNotifyEmail (
			$msg='V-Krit Status'."\n". // first line is subject
					$fullMail,
			$recipients='criticus@uni-paderborn.de',
			$cc='cola@upb.de',
			$email_from='criticus@uni-paderborn.de',
			$email_fromName='V-Krit Orga',
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

	/**
	 * For a given survey, selects UIDs for all lectures without Kritter
	 * @param $survey UID of survey
	 * @param $daysInFuture limit this to any number of days in the future, 0 is unlimited
	 * @return array of UIDs
	 **/
	function lecturesWithoutKritter($survey, $daysInFuture=1) {
		if ($daysInFuture>0) {
			$dateLimit = time() + $daysInFuture*24*60*60;
			$dateLimitWhere = ' AND eval_date_fixed < '.$dateLimit.' ';
		}
		else $dateLimitWhere = '';

		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0 AND hidden=0
												  AND survey='.$survey.'
												  AND (kritter_feuser_1=0 OR kritter_feuser_1 IS NULL)
												  AND (kritter_feuser_2=0 OR kritter_feuser_2 IS NULL)
												  AND (kritter_feuser_3=0 OR kritter_feuser_3 IS NULL)
												  AND (kritter_feuser_4=0 OR kritter_feuser_4 IS NULL) '.
												  $dateLimitWhere.'
												  AND eval_date_fixed > '.time().'
												  ORDER BY eval_date_fixed');

		$lectures = array ();
		while ($res && $row = mysql_fetch_assoc($res))
			$lectures[] = $row['uid'];

		return $lectures;
	}

	/**
	 * For a given survey, selects UIDs for all lectures in next days for a given kritter
	 * @param $survey UID of survey
	 * @param $daysInFuture limit this to any number of days in the future, 0 is unlimited
	 * @param $kritter UID, if zero then for all kritters
	 * @return array of UIDs
	 **/
	function lecturesInNextDays($survey, $daysInFuture=1,$kritter=0) {
		if ($daysInFuture>0) {
			$dateLimit = time() + $daysInFuture*24*60*60;
			$dateLimitWhere = ' AND eval_date_fixed<'.$dateLimit.' ';
		}
		else $dateLimitWhere = '';

		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0 AND hidden=0
												  AND survey='.$survey.
												  $dateLimitWhere.'
												  AND eval_date_fixed > '.time().'
												  ORDER BY eval_date_fixed');

		$lectures = array ();
		while ($res && $row = mysql_fetch_assoc($res))
			$lectures[] = $row['uid'];

		return $lectures;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/cron/class.tx_fsmivkrit_reminder_organizer_scheduler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/cron/class.tx_fsmivkrit_reminder_organizer_scheduler.php']);
}
?>
