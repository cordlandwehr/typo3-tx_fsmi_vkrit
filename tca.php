<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_fsmivkrit_lecturer'] = array (
	'ctrl' => $TCA['tx_fsmivkrit_lecturer']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,name,forename,email,reshipment,foreign_id'
	),
	'feInterface' => $TCA['tx_fsmivkrit_lecturer']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		"sex" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.sex",
			"config" => Array (
				"type" => "radio",
				"items" => Array (
					Array("LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.sex.I.0", "0"),
					Array("LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.sex.I.1", "1"),
					Array("LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.sex.I.2", "2"),
				),
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'forename' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.forename',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'email' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.email',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'reshipment' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.reshipment',
			'config' => array (
				'type' => 'check',
			)
		),
		'foreign_id' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.foreign_id',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'uniqueInPid',
			)
		),
        'organizational_unit' => array (
            'exclude' => 0,
            'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecturer.organizational_unit',
            'config' => array (
                'type' => 'input',
                'size' => '30',
            )
        ),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, title, sex, name, forename, email, reshipment, foreign_id, organizational_unit')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);



$TCA['tx_fsmivkrit_lecture'] = array (
	'ctrl' => $TCA['tx_fsmivkrit_lecture']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,name,lecturer,participants,eval_date_1,eval_date_2,eval_date_3,eval_room_1,eval_room_2,no_eval,foreign_id,kritter_1,kritter_2,kritter_3,kritter_4,weight,pictures,godfather,tipper,to_scan_office'
	),
	'feInterface' => $TCA['tx_fsmivkrit_lecture']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'lecturer' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.lecturer',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_fsmivkrit_lecturer',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'survey' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.survey',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_fsmivkrit_survey',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			)
		),
		'participants' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.participants',
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
        "lecture_type" => Array (
            "exclude" => 0,
            "label" => "LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.lecture_type",
            "config" => Array (
                "type" => "radio",
                "items" => Array (
                    Array("LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.lecture_type.I.0", "0"),
                    Array("LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.lecture_type.I.1", "1"),
                    Array("LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.lecture_type.I.2", "2"),
                ),
            )
        ),
		'eval_date_1' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_date_1',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'datetime',
			)
		),
		'eval_date_2' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_date_2',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'datetime',
			)
		),
		'eval_date_3' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_date_3',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'datetime',
			)
		),
		'eval_room_1' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_room_1',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'eval_room_2' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_room_2',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'eval_room_3' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_room_3',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'eval_date_fixed' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_date_fixed',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'datetime',
			)
		),
		'eval_room_fixed' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_room_fixed',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'no_eval' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.no_eval',
			'config' => array (
				'type' => 'check',
			)
		),
		'foreign_id' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.foreign_id',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'kritter_1' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.kritter_1',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'kritter_2' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.kritter_2',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'kritter_3' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.kritter_3',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'kritter_4' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.kritter_4',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'weight' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.weight',
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'pictures' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.pictures',
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'godfather' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.godfather',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'tipper' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.tipper',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_fsmivkrit_helper',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'eval_state' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state.I.0', '0'),
					array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state.I.1', '1'),
					array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state.I.2', '2'),
					array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state.I.3', '3'),
					array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state.I.4', '4'),
					array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state.I.5', '5'),
					array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state.I.6', '6'),
					array('LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.eval_state.I.7', '7'),
				),
			)
		),
		'godfather' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.inputform_verify',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		"comment" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_lecture.comment",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, name, lecturer, survey, participants, lecture_type, eval_date_1, eval_date_2, eval_date_3, eval_room_1, eval_room_2, eval_room_3, eval_date_fixed, eval_room_fixed, no_eval, foreign_id, kritter_1, kritter_2, kritter_3, kritter_4, weight, pictures, godfather, tipper, eval_state,  comment')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);



$TCA['tx_fsmivkrit_tutorial'] = array (
	'ctrl' => $TCA['tx_fsmivkrit_tutorial']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,assistant_name,assistant_forename,lecture,foreign_id'
	),
	'feInterface' => $TCA['tx_fsmivkrit_tutorial']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'assistant_name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_tutorial.assistant_name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'assistant_forename' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_tutorial.assistant_forename',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'assistant_title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_tutorial.assistant_title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'lecture' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_tutorial.lecture',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_fsmivkrit_lecture',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'foreign_id' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_tutorial.foreign_id',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, assistant_title, assistant_name, assistant_forename, lecture, foreign_id')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);



$TCA['tx_fsmivkrit_survey'] = array (
	'ctrl' => $TCA['tx_fsmivkrit_survey']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,name,semester,storage,importdata_origin'
	),
	'feInterface' => $TCA['tx_fsmivkrit_survey']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_survey.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'semester' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_survey.semester',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'orgroot' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_survey.orgroot',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'storage' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_survey.storage',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'importdata_origin' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_survey.importdata_origin',
			'config' => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
		'eval_start' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_survey.eval_start',
			'config' => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
		'eval_end' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:fsmi_vkrit/locallang_db.xml:tx_fsmivkrit_survey.eval_end',
			'config' => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, name, semester, orgroot, storage, importdata_origin, eval_start, eval_end')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

?>
