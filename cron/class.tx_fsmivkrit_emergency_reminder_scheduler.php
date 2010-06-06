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


class tx_fsmivkrit_emergency_reminder_scheduler extends tx_scheduler_Task {
	var $uid;

	/**
	 * next function fixes PHP4 issue
	 */
// 	function tx_cal_calendar_scheduler() {
// 		$this->__construct();
// 	}

	public function execute() {
		$emergency = false;
		// dirty hack
		$survey = 3;

		$fullMail =
'Statusinformationen zur V-Krit:'."\n".
'=============='."\n\n";

		// state problems first
		$lecturesWithoutKritter = $this->lecturesWithoutKritter($survey,2);//TODO set survey
		if (count($lecturesWithoutKritter)>0)
			$fullMail .=
'Veranstaltungen ohne Kritter (kommende 2 Tage):'."\n".
'--------------'."\n";
		foreach ($lecturesWithoutKritter as $lecture) {
			$lectureDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
			$fullMail .= '* '.$lectureDATA['name']."\n";
			$fullMail .= '  '.$lectureDATA['participants'].' Teilnehmer'."\n";
			$fullMail .= '  '.date('d.m.Y. / H:i',$lectureDATA['eval_date_fixed']).' / '.$lectureDATA['eval_room_fixed']."\n";
		}
  		if (count($lecturesWithoutKritter)==0)
			$fullMail .= ' -- keine --'."\n";
		else
			$emergency = true;
		$fullMail .= "\n";

		// next the status information
		// state problems first
		$lecturesAll = $this->lecturesInNextDays($survey,2);//TODO set survey
		$none=true;
		$fullMail .=
'Unterbesetzte Veranstaltungen (kommende 2 Tage):'."\n".
'--------------'."\n";
		foreach ($lecturesAll as $lecture) {
			$lectureDATA = t3lib_BEfunc::getRecord('tx_fsmivkrit_lecture', $lecture);
			// count helpers
			$helper=0;
			for ($i=1; $i<=4; $i++)
				if ($lectureDATA['kritter_feuser_'.$i]!=0 && $lectureDATA['kritter_feuser_'.$i]!='')
					$helper++;
			// warning for lectures with 1 helper for more then 50 guys
			if ($helper!=0 && $lectureDATA['participants']/$helper>50 && $helper<4) {
				$fullMail .= '* '.date('d.m.-H:i',$lectureDATA['eval_date_fixed']).' '.
					$lectureDATA['name'].
					' ('.$lectureDATA['eval_room_fixed'].','.$lectureDATA['participants'].'TN)'."\n".
					'  '.$helper.' Kritter'."\n";
				$none=false;
			}
		}
		if ($none==true)
			$fullMail .= ' -- keine --'."\n";
		else
			$emergency = true;
		$fullMail .= "\n";

		// statistical/administrative data
		$moderationWarning = false;
		$fullMail .=
'Moderationswarnung'."\n".
'--------------'."\n";
		$fullMail .=
'Veranstaltungen, bei denen letztes vorgeschlagenes Datum innerhalb der nächsten 3 Tage liegt.'."\n";

		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmivkrit_lecture
												WHERE deleted=0 AND hidden=0
												  AND no_eval=0
												  AND eval_date_1 <'.(time()+3*24*60*60).'
												  AND NOT eval_date_1 IS NULL
												  AND eval_date_2 <'.(time()+3*24*60*60).'
												  AND NOT eval_date_2 IS NULL
												  AND eval_date_3 <'.(time()+3*24*60*60).'
												  AND NOT eval_date_3 IS NULL
												  AND eval_date_fixed=0
												  AND survey='.$survey);

		while ($res && $row = mysql_fetch_assoc($res)) {
			$moderationWarning = true;
			$emergency = true;
			$fullMail .= '* '.$lectureDATA['name'].', '.$lectureDATA['participants'].'TN'."\n";
		}

		if ($moderationWarning==false)
			$fullMail .= ' -- keine --'."\n";

		// if nothing to say, simply keep your mouth shut
		if ($emergency==false)
			return true;

		$send = $this->sendNotifyEmail (
			$msg='V-Krit Warnung'."\n". // first line is subject
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/cron/class.tx_fsmivkrit_emergency_reminder_scheduler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_vkrit/cron/class.tx_fsmivkrit_emergency_reminder_scheduler.php']);
}
?>