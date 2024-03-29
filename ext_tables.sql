#
# Table structure for table 'tx_fsmivkrit_lecturer'
#
CREATE TABLE tx_fsmivkrit_lecturer (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title tinytext,
	sex int(11) DEFAULT '0' NOT NULL,
	name tinytext,
	forename tinytext,
	email tinytext,
	reshipment tinyint(3) DEFAULT '0' NOT NULL,
	foreign_id tinytext,
	organizational_unit tinytext,

	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_fsmivkrit_lecture'
#
CREATE TABLE tx_fsmivkrit_lecture (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	name tinytext,
	lecturer text,
	survey text,
	participants int(11) DEFAULT '0' NOT NULL,
	lecture_type int(11) DEFAULT '0' NOT NULL,
	eval_date_1 int(11) DEFAULT '0' NOT NULL,
	eval_date_2 int(11) DEFAULT '0' NOT NULL,
	eval_date_3 int(11) DEFAULT '0' NOT NULL,
	eval_room_1 tinytext,
	eval_room_2 tinytext,
	eval_room_3 tinytext,
	eval_date_fixed int(11) DEFAULT '0' NOT NULL,
	eval_room_fixed tinytext,
	no_eval tinyint(3) DEFAULT '0' NOT NULL,
	foreign_id tinytext,
	kritter_1 tinytext,
	kritter_2 tinytext,
	kritter_3 tinytext,
	kritter_4 tinytext,
	kritter_feuser_1 tinytext,
	kritter_feuser_2 tinytext,
	kritter_feuser_3 tinytext,
	kritter_feuser_4 tinytext,
	weight int(11) DEFAULT '0' NOT NULL,
	pictures int(11) DEFAULT '0' NOT NULL,
	godfather tinytext,
	tipper text,
	to_scan_office tinyint(3) DEFAULT '0' NOT NULL,
	eval_state tinyint(3) DEFAULT '0' NOT NULL,
	inputform_verify tinytext,
	comment text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_fsmivkrit_tutorial'
#
CREATE TABLE tx_fsmivkrit_tutorial (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	assistant_name tinytext,
	assistant_forename tinytext,
	assistant_title tinytext,
	lecture text,
	foreign_id tinytext,

	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_fsmivkrit_survey'
#
CREATE TABLE tx_fsmivkrit_survey (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	name tinytext,
	semester tinytext,
	orgroot tinytext,
	storage text,
	importdata_origin int(11) DEFAULT '0' NOT NULL,
	eval_start int(11) DEFAULT '0' NOT NULL,
	eval_end int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for extension to table 'fe_users'
#
CREATE TABLE fe_users (
	tx_fsmivkrit_fsmivkrit_helper_for_survey text,
);
