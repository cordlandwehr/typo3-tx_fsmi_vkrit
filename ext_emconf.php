<?php

########################################################################
# Extension Manager/Repository config file for ext "fsmi_vkrit".
#
# Auto generated 27-04-2011 21:54
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'FSMI Evaluation Organization',
	'description' => 'This tools provides an easy way to organize paper based evaluations for student courses.
Import of CampusNet data and export of EvaSys data is provided.',
	'category' => 'plugin',
	'author' => 'Andreas Cord-Landwehr',
	'author_email' => 'fsmi@uni-paderborn.de',
	'shy' => '',
	'dependencies' => 'date2cal',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.3.1',
	'constraints' => array(
		'depends' => array(
			'date2cal' => '7.1.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:74:{s:9:"ChangeLog";s:4:"9e37";s:10:"README.txt";s:4:"ee2d";s:8:"TODO.txt";s:4:"dbe9";s:16:"ext_autoload.php";s:4:"334b";s:21:"ext_conf_template.txt";s:4:"545d";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"f852";s:14:"ext_tables.php";s:4:"27ae";s:14:"ext_tables.sql";s:4:"cebd";s:16:"fsmi_vkrit.kdev4";s:4:"3199";s:28:"icon_tx_fsmivkrit_helper.gif";s:4:"475a";s:29:"icon_tx_fsmivkrit_lecture.gif";s:4:"475a";s:30:"icon_tx_fsmivkrit_lecturer.gif";s:4:"475a";s:28:"icon_tx_fsmivkrit_survey.gif";s:4:"1e24";s:30:"icon_tx_fsmivkrit_tutorial.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"6672";s:7:"tca.php";s:4:"f6fa";s:30:"api/class.tx_fsmivkrit_div.php";s:4:"c89a";s:56:"cron/class.tx_fsmivkrit_emergency_reminder_scheduler.php";s:4:"ee03";s:53:"cron/class.tx_fsmivkrit_reminder_helper_scheduler.php";s:4:"4ade";s:56:"cron/class.tx_fsmivkrit_reminder_organizer_scheduler.php";s:4:"5da2";s:80:"cron/class.tx_fsmivkrit_reminder_organizer_scheduler_additionalfieldprovider.php";s:4:"9651";s:20:"doc/liste_aktiv.jpeg";s:4:"fa3d";s:26:"doc/liste_aktiv_anony.jpeg";s:4:"4a98";s:14:"doc/manual.sxw";s:4:"225c";s:15:"doc/ranking.jpg";s:4:"ae26";s:15:"doc/ranking.png";s:4:"a5d9";s:19:"doc/wizard_form.dat";s:4:"f9ae";s:20:"doc/wizard_form.html";s:4:"ae67";s:33:"doc/screenshots/create_survey.png";s:4:"7985";s:35:"doc/screenshots/data_import_csv.png";s:4:"2c07";s:25:"flexform/flexform_pi2.xml";s:4:"5fb6";s:25:"flexform/flexform_pi3.xml";s:4:"dec3";s:15:"gfx/comment.png";s:4:"70b1";s:16:"gfx/disabled.png";s:4:"dff3";s:15:"gfx/enabled.png";s:4:"e4af";s:13:"gfx/error.png";s:4:"c870";s:12:"gfx/info.png";s:4:"dea5";s:10:"gfx/ok.png";s:4:"9569";s:15:"gfx/state_0.png";s:4:"4404";s:15:"gfx/state_1.png";s:4:"ae99";s:15:"gfx/state_2.png";s:4:"f4f4";s:15:"gfx/state_3.png";s:4:"c31e";s:15:"gfx/state_4.png";s:4:"58dc";s:15:"gfx/state_5.png";s:4:"bdf5";s:15:"gfx/state_6.png";s:4:"064a";s:15:"gfx/state_7.png";s:4:"9a30";s:15:"gfx/state_8.png";s:4:"fe15";s:15:"gfx/warning.png";s:4:"d1ff";s:29:"gfx/source/Achtung-yellow.svg";s:4:"8510";s:34:"gfx/source/Ambox_blue_question.svg";s:4:"5100";s:29:"gfx/source/Speech_balloon.svg";s:4:"ca4b";s:25:"gfx/source/Start_hand.svg";s:4:"0cb5";s:28:"gfx/source/Stop_x_nuvola.svg";s:4:"a36b";s:22:"gfx/source/comment.png";s:4:"fc4f";s:20:"gfx/source/state.svg";s:4:"f808";s:22:"gfx/source/state_0.svg";s:4:"6601";s:22:"gfx/source/state_1.svg";s:4:"2db1";s:22:"gfx/source/state_2.svg";s:4:"ecb9";s:22:"gfx/source/state_3.svg";s:4:"809c";s:22:"gfx/source/state_4.svg";s:4:"09fc";s:22:"gfx/source/state_5.svg";s:4:"6f6d";s:22:"gfx/source/state_6.svg";s:4:"ba43";s:22:"gfx/source/state_7.svg";s:4:"347a";s:22:"gfx/source/state_8.svg";s:4:"608b";s:30:"pi1/class.tx_fsmivkrit_pi1.php";s:4:"ea5f";s:17:"pi1/locallang.xml";s:4:"9e2f";s:30:"pi2/class.tx_fsmivkrit_pi2.php";s:4:"32c7";s:17:"pi2/locallang.xml";s:4:"8fa9";s:30:"pi3/class.tx_fsmivkrit_pi3.php";s:4:"5c35";s:17:"pi3/locallang.xml";s:4:"d60f";s:30:"pi4/class.tx_fsmivkrit_pi4.php";s:4:"041b";s:17:"pi4/locallang.xml";s:4:"b372";s:20:"static/css/setup.txt";s:4:"a7e4";}',
	'suggests' => array(
	),
);

?>