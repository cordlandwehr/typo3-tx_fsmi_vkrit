<?php

########################################################################
# Extension Manager/Repository config file for ext: "fsmi_vkrit"
#
# Auto generated 30-10-2009 19:27
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'FSMI Evaluation Organization',
	'description' => 'This tools provides an easy way to organize paper based evaluations for student courses.
Import of CampusNet data and export of EvaSys data is provided.',
	'category' => 'plugin',
	'author' => 'Andreas Cord-Landwehr',
	'author_email' => 'fsmi@uni-paderborn.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.1',
	'constraints' => array(
		'depends' => array(
			'rlmp_dateselectlib' => '0.1.8-'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:23:{s:9:"ChangeLog";s:4:"9e37";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"f852";s:14:"ext_tables.php";s:4:"72e3";s:14:"ext_tables.sql";s:4:"e237";s:28:"icon_tx_fsmivkrit_helper.gif";s:4:"475a";s:29:"icon_tx_fsmivkrit_lecture.gif";s:4:"475a";s:30:"icon_tx_fsmivkrit_lecturer.gif";s:4:"475a";s:28:"icon_tx_fsmivkrit_survey.gif";s:4:"1e24";s:30:"icon_tx_fsmivkrit_tutorial.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"b482";s:7:"tca.php";s:4:"854a";s:30:"pi2/class.tx_fsmivkrit_pi2.php";s:4:"9ded";s:17:"pi2/locallang.xml";s:4:"8fa9";s:30:"pi4/class.tx_fsmivkrit_pi4.php";s:4:"9d0f";s:17:"pi4/locallang.xml";s:4:"b372";s:30:"pi1/class.tx_fsmivkrit_pi1.php";s:4:"1bd2";s:17:"pi1/locallang.xml";s:4:"9e2f";s:30:"pi3/class.tx_fsmivkrit_pi3.php";s:4:"225c";s:17:"pi3/locallang.xml";s:4:"d60f";s:19:"doc/wizard_form.dat";s:4:"f9ae";s:20:"doc/wizard_form.html";s:4:"ae67";}',
);

?>