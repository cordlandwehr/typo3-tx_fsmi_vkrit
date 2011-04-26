<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
$TCA['tx_fsmivkrit_lecturer'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer',
		'label'     => 'name',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY name',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_fsmivkrit_lecturer.gif',
	),
);

$TCA['tx_fsmivkrit_lecture'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture',
		'label'     => 'name',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY name',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_fsmivkrit_lecture.gif',
	),
);

$TCA['tx_fsmivkrit_tutorial'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_tutorial',
		'label'     => 'assistant_name',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY assistant_name',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_fsmivkrit_tutorial.gif',
	),
);

$TCA['tx_fsmivkrit_survey'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_survey',
		'label'     => 'name',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_fsmivkrit_survey.gif',
	),
);

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
t3lib_extMgm::addPlugin(array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tt_content.list_type_pi1',$_EXTKEY . '_pi1'),'list_type');


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';
t3lib_extMgm::addPlugin(array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tt_content.list_type_pi2',	$_EXTKEY . '_pi2'),'list_type');
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi2']='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi2', 'FILE:EXT:fsmi_vkrit/flexform/flexform_pi2.xml');


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi3']='layout,select_key';
t3lib_extMgm::addPlugin(array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tt_content.list_type_pi3',$_EXTKEY . '_pi3'),'list_type');
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi3']='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi3', 'FILE:EXT:fsmi_vkrit/flexform/flexform_pi3.xml');


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi4']='layout,select_key';
t3lib_extMgm::addPlugin(array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tt_content.list_type_pi4',$_EXTKEY . '_pi4'),'list_type');

// add extension fields to frontend users
$tempColumns = array (
    'tx_fsmivkrit_fsmivkrit_helper_for_survey' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.survey',
		'config' => array (
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'tx_fsmivkrit_survey',
			'size' => 5,
			'minitems' => 0,
			'maxitems' => 30,
		)
    ),
);

t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_users','tx_fsmivkrit_fsmivkrit_helper_for_survey;;;;1-1-1');


// add scheduler jobs
$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_fsmivkrit_reminder_organizer_scheduler'] = array(
	'extension' => 'fsmi_vkrit',
	'title' => 'Information mails for Organizer',
	'description' => 'Sends notification mails each evening to tell which tasks are open for current evaluation.',
    'additionalFields' => 'tx_fsmivkrit_emergency_reminder_scheduler'
);
$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_fsmivkrit_emergency_reminder_scheduler'] = array(
	'extension' => 'fsmi_vkrit',
	'title' => 'Emergency Information for Organizer',
	'description' => 'Sends Email, if something goes really wrong.',
	'additionalFields' => 'tx_fsmivkrit_emergency_reminder_scheduler'
);
$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_fsmivkrit_reminder_helper_scheduler'] = array(
	'extension' => 'fsmi_vkrit',
	'title' => 'Remember helpers to be helper',
	'description' => 'Sends Email to remember of beeing a helper.',
    'additionalFields' => 'tx_fsmivkrit_emergency_reminder_scheduler'
);

// include statics
t3lib_extMgm::addStaticFile($_EXTKEY,"static/css/","Vkrit CSS Style");
?>
