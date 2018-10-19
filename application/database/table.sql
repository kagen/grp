CREATE TABLE grp_user (
id integer NOT NULL PRIMARY KEY auto_increment,
userid text NOT NULL,
password text NOT NULL,
password_default text NOT NULL,
realname text NOT NULL,
authority text NOT NULL,
user_group integer,
user_groupname text,
user_email text,
user_code text,
user_skype text,
user_ruby text,
user_postcode text,
user_address text,
user_addressruby text,
user_phone text,
user_mobile text,
user_birthday DATE NOT NULL,
last_login DATETIME NULL,
user_joindate DATE NOT NULL,
user_hiretype TINYINT NOT NULL,
user_openhour MEDIUMTEXT NULL,
user_openminute MEDIUMTEXT NULL,
user_closehour MEDIUMTEXT NULL,
user_closeminute MEDIUMTEXT NULL,
user_overtime_flg TINYINT(1) NOT NULL DEFAULT 0,
remark LONGTEXT NULL,
user_retired DATE NULL,
user_order integer,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE UNIQUE INDEX grp_index_userid ON grp_user (userid(255));
CREATE INDEX grp_index_user_group ON grp_user (user_group);

CREATE TABLE grp_group (
id integer NOT NULL PRIMARY KEY auto_increment,
group_name text NOT NULL,
group_leader MEDIUMTEXT NULL,
parent_id INT NOT NULL,
group_order integer,
add_level integer NOT NULL,
add_group text,
add_user text,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);

CREATE TABLE grp_folder (
id integer NOT NULL PRIMARY KEY auto_increment,
folder_type text NOT NULL,
folder_id integer NOT NULL,
folder_caption text NOT NULL,
folder_name text,
folder_date text,
folder_order integer,
add_level integer,
add_group text,
add_user text,
public_level integer,
public_group text,
public_user text,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_folder_type ON grp_folder (folder_type(255));
CREATE INDEX grp_index_folder_id ON grp_folder (folder_id);
CREATE INDEX grp_index_folder_owner ON grp_folder (owner(255));

CREATE TABLE grp_schedule (
id integer NOT NULL PRIMARY KEY auto_increment,
schedule_type integer NOT NULL,
schedule_title text,
schedule_name text,
schedule_comment text,
schedule_year integer,
schedule_month integer,
schedule_day integer,
schedule_date text,
schedule_time text,
schedule_endtime text,
schedule_allday text,
schedule_repeat text,
schedule_everyweek text,
schedule_everymonth text,
schedule_begin text,
schedule_end text,
schedule_facility integer,
schedule_level integer NOT NULL,
schedule_group text,
schedule_user text,
public_level integer NOT NULL,
public_group text,
public_user text,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_schedule_type ON grp_schedule (schedule_type);
CREATE INDEX grp_index_schedule_date ON grp_schedule (schedule_date(255));
CREATE INDEX grp_index_schedule_repeat ON grp_schedule (schedule_repeat(255));
CREATE INDEX grp_index_schedule_begin ON grp_schedule (schedule_begin(255));
CREATE INDEX grp_index_schedule_end ON grp_schedule (schedule_end(255));
CREATE INDEX grp_index_schedule_level ON grp_schedule (schedule_level);
CREATE INDEX grp_index_schedule_owner ON grp_schedule (owner(255));

CREATE TABLE grp_message (
id integer NOT NULL PRIMARY KEY auto_increment,
folder_id integer NOT NULL,
message_type text NOT NULL,
message_to text NOT NULL,
message_from text NOT NULL,
message_toname text,
message_fromname text,
message_title text,
message_comment text,
message_date text,
message_file text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_message_folder_id ON grp_message (folder_id);
CREATE INDEX grp_index_message_type ON grp_message (message_type(255));
CREATE INDEX grp_index_message_owner ON grp_message (owner(255));

CREATE TABLE grp_forum (
id integer NOT NULL PRIMARY KEY auto_increment,
folder_id integer NOT NULL,
forum_parent integer NOT NULL,
forum_title text,
forum_name text,
forum_comment text,
forum_date text,
forum_file text,
forum_lastupdate text,
forum_node integer,
public_level integer NOT NULL,
public_group text,
public_user text,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_forum_folder_id ON grp_forum (folder_id);
CREATE INDEX grp_index_forum_parent ON grp_forum (forum_parent);

CREATE TABLE grp_storage (
id integer NOT NULL PRIMARY KEY auto_increment,
storage_type text NOT NULL,
storage_folder integer NOT NULL,
storage_title text,
storage_name text,
storage_comment text,
storage_date text,
storage_file text,
storage_size text,
add_level integer,
add_group text,
add_user text,
public_level integer NOT NULL,
public_group text,
public_user text,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_storage_type ON grp_storage (storage_type(255));
CREATE INDEX grp_index_storage_folder ON grp_storage (storage_folder);

CREATE TABLE grp_bookmark (
id integer NOT NULL PRIMARY KEY auto_increment,
folder_id integer NOT NULL,
bookmark_title text,
bookmark_name text,
bookmark_url text,
bookmark_date text,
bookmark_comment text,
bookmark_order integer,
public_level integer NOT NULL,
public_group text,
public_user text,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_bookmark_folder_id ON grp_bookmark (folder_id);

CREATE TABLE grp_project (
id integer NOT NULL PRIMARY KEY auto_increment,
folder_id integer NOT NULL,
project_parent integer NOT NULL,
project_title text,
project_begin text,
project_end text,
project_name text,
project_progress integer,
project_comment text,
project_date text,
project_file text,
public_level integer NOT NULL,
public_group text,
public_user text,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_project_folder_id ON grp_project (folder_id);
CREATE INDEX grp_index_project_parent ON grp_project (project_parent);

CREATE TABLE grp_addressbook (
id integer NOT NULL PRIMARY KEY auto_increment,
folder_id integer NOT NULL,
addressbook_type integer NOT NULL,
addressbook_name text,
addressbook_ruby text,
addressbook_company text,
addressbook_companyruby text,
addressbook_department text,
addressbook_position text,
addressbook_postcode text,
addressbook_address text,
addressbook_addressruby text,
addressbook_phone text,
addressbook_fax text,
addressbook_mobile text,
addressbook_email text,
addressbook_url text,
addressbook_comment text,
addressbook_parent integer,
public_level integer NOT NULL,
public_group text,
public_user text,
edit_level integer,
edit_group text,
edit_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_addressbook_folder_id ON grp_addressbook (folder_id);
CREATE INDEX grp_index_addressbook_type ON grp_addressbook (addressbook_type);

CREATE TABLE grp_todo (
id integer NOT NULL PRIMARY KEY auto_increment,
folder_id integer NOT NULL,
todo_parent integer,
todo_title text,
todo_name text,
todo_term text,
todo_noterm text,
todo_priority integer,
todo_comment text,
todo_complete integer,
todo_completedate text,
todo_user text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_todo_folder_id ON grp_todo (folder_id);
CREATE INDEX grp_index_todo_owner ON grp_todo (owner(255));

CREATE TABLE grp_timecard (
id integer NOT NULL PRIMARY KEY auto_increment,
timecard_year integer,
timecard_month integer,
timecard_day integer,
timecard_date text,
timecard_open text,
timecard_close text,
timecard_interval text,
timecard_originalopen text,
timecard_originalclose text,
timecard_originalinterval text,
timecard_time text,
timecard_timeinterval text,
timecard_comment text,
timecard_work TIME NULL,
timecard_overtime TIME NULL,
timecard_latenightovertime TIME NULL,
timecard_hd_work TIME NULL,
timecard_hd_overtime TIME NULL,
timecard_hd_latenightovertime TIME NULL,
timecard_latecome TIME NULL,
timecard_earlyleave TIME NULL,
timecard_reason_open INT NULL,
timecard_reason_close INT NULL,
timecard_admin_comment LONGTEXT NULL,
timecard_vacation_from TIME NULL,
timecard_vacation_to TIME NULL,
timecard_vacation_substitute_id INT NULL,
timecard_vacation_type INT NULL,
timecard_vacation_comment LONGTEXT NULL,
timecard_vacation_requested DATETIME NULL,
timecard_vacation_approved DATETIME NULL,
timecard_vacation_supervisor_comment LONGTEXT NULL,
timecard_vacation_supervisor_userid MEDIUMTEXT NULL,
timecard_workday_flg TINYINT NOT NULL DEFAULT 0,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_timecard_year ON grp_timecard (timecard_year);
CREATE INDEX grp_index_timecard_month ON grp_timecard (timecard_month);
CREATE INDEX grp_index_timecard_owner ON grp_timecard (owner(255));

CREATE TABLE grp_config (
id integer NOT NULL PRIMARY KEY auto_increment,
config_type text NOT NULL,
config_key text NOT NULL,
config_value text,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text);
CREATE INDEX grp_index_config_type ON grp_config (config_type(255));


CREATE TABLE `grp_reason` (
id INT NOT NULL,
reason_desc MEDIUMTEXT NOT NULL,
syanai TINYINT(1) NOT NULL,
INDEX `id` (`id`)
);

INSERT INTO `grp_reason` (`id`, `reason_desc`, `syanai`) VALUES
(1,'打刻忘れ', 0),
(2,'電車遅延', 1),
(3,'直行直帰', 0),
(4,'打刻障害', 0),
(99,'その他', 0);


CREATE TABLE `grp_vacation` (
id INT NOT NULL,
vacation_name MEDIUMTEXT NOT NULL,
paid TINYINT(1) NOT NULL,
allday TINYINT(1) NOT NULL,
shortname MEDIUMTEXT NULL,
vacation_comment TEXT NULL,
INDEX `id` (`id`)
);

INSERT INTO `grp_vacation` (`id`, `vacation_name`, `paid`,`allday`,`shortname`,`vacation_comment`) VALUES
(1,'有給休暇', 1, 1, '有給', NULL),
(5,'時間有給', 1, 0, '時間', NULL),
(10,'振替休日', 1, 1, '振替',  '振替休日を取得する場合は、必ず振替となる休日出勤の日付をコメント欄に書いてください。'),
(15,'時間振休', 1, 0, '時振', '時間振休を取得する場合は、必ず振替となる休日出勤の日付と時間帯をコメント欄に書いてください。'),
(20,'特別休暇', 1, 1, '特別', NULL),
(25,'欠勤・その他休', 0, 1, '欠他', '欠勤・その他休暇の場合は必ず理由をコメント欄に書いてください。');


CREATE TABLE `grp_timecard_fixed` (
timecard_year integer NOT NULL,
timecard_month integer NOT NULL,
fixed_from DATE NOT NULL,
fixed_to DATE NOT NULL,
owner text NOT NULL,
fixed_by text NOT NULL,
fixed_at DATETIME NOT NULL
);

/*
ALTER TABLE `grp_timecard_fixed`
	ADD COLUMN `fixed_from` DATE NOT NULL AFTER `timecard_month`,
	ADD COLUMN `fixed_to` DATE NOT NULL AFTER `fixed_from`;

update grp_timecard_fixed set
grp_timecard_fixed.fixed_from = DATE_ADD(STR_TO_DATE(CONCAT(grp_timecard_fixed.timecard_year,'-',grp_timecard_fixed.timecard_month,'-11'),'%Y-%m-%d'),INTERVAL -1 month),
grp_timecard_fixed.fixed_to = STR_TO_DATE(CONCAT(grp_timecard_fixed.timecard_year,'-',grp_timecard_fixed.timecard_month,'-10'),'%Y-%m-%d')
*/




CREATE TABLE `grp_holiday` (
holiday_date DATE NOT NULL,
holiday_desc text NULL
);


CREATE TABLE `grp_access` (
id integer NOT NULL PRIMARY KEY auto_increment,
access_table text NOT NULL,
access_id integer NOT NULL,
access_userid text NOT NULL,
access_at DATETIME NOT NULL
);

CREATE TABLE `grp_news` (
id integer NOT NULL PRIMARY KEY auto_increment,
news_title text NOT NULL,
news_body text NOT NULL,
owner text NOT NULL,
news_begin DATETIME NOT NULL,
news_end DATETIME NOT NULL,
news_hide TINYINT(1) NOT NULL DEFAULT 0,
recorder_disp TINYINT(1) NOT NULL DEFAULT 0,
editor text,
created text NOT NULL,
updated text
);


CREATE TABLE grp_user_group (
id integer NOT NULL PRIMARY KEY auto_increment,
group_id INT NOT NULL,
userid text NOT NULL);


CREATE TABLE grp_overtime (
id integer NOT NULL PRIMARY KEY auto_increment,
overtime_date DATE NOT NULL,
overtime_time_requested TIME NOT NULL,
overtime_time_approved TIME NULL,
overtime_approved_at DATETIME NULL,
overtime_supervisor_userid MEDIUMTEXT NULL,
overtime_fixed_normal_overtime TIME NULL,
overtime_fixed_latenightovertime TIME NULL,
overtime_fixed_hd_worktime TIME NULL,
overtime_fixed_hd_latenightovertime TIME NULL,
owner text NOT NULL,
editor text,
created text NOT NULL,
updated text,
UNIQUE(overtime_date, owner(255))
);
CREATE INDEX grp_index_overtime_date ON grp_overtime (overtime_date);
CREATE INDEX grp_index_overtime_owner ON grp_overtime (owner(255));


CREATE TABLE `grp_overtime_comment` (
id integer NOT NULL PRIMARY KEY auto_increment,
timecard_year integer NOT NULL,
timecard_month integer NOT NULL,
date_from DATE NOT NULL,
date_to DATE NOT NULL,
comment TEXT NOT NULL,
timecard_overtime_approved DATETIME NULL,
timecard_overtime_supervisor_comment LONGTEXT NULL,
timecard_overtime_supervisor_userid MEDIUMTEXT NULL,
owner text NOT NULL,
created text NOT NULL,
updated text,
UNIQUE(timecard_year, timecard_month, owner(255))
);
CREATE INDEX grp_index_overtime_comment_timecard_year ON grp_overtime_comment (timecard_year);
CREATE INDEX grp_index_overtime_comment_timecard_month ON grp_overtime_comment (timecard_month);
CREATE INDEX grp_index_overtime_comment_owner ON grp_overtime_comment (owner(255));


CREATE TABLE `grp_event` (
id INT NOT NULL,
event_name MEDIUMTEXT NOT NULL,
bg_color MEDIUMTEXT NULL,
INDEX `id` (`id`)
);


INSERT INTO `grp_event` (`id`, `event_name`, `bg_color`) VALUES
(1,'休暇', '#ff80f7'),
(10,'休出', '#700099'),
(15,'内会', '#0400ff'),
(16,'外出', '#ffae00'),
(20,'営業', '#00ffb3'),
(25,'講習', '#96ff66'),
(30,'中間', '#fcff66'),
(35,'完了', '#ffa366'),
(40,'取込', '#ff1a1a'),
(99,'其他', '#adadad');


ALTER TABLE `grp_schedule`
ADD COLUMN `schedule_event` INT(11) NULL AFTER `schedule_user`,
ADD COLUMN `event_local` TEXT NULL,
ADD COLUMN `event_canceled` TINYINT(1) NOT NULL,
ADD COLUMN `event_temp` TINYINT(1) NOT NULL;

ALTER TABLE `grp_timecard`
ADD COLUMN `timecard_hayade` TIME NULL AFTER `timecard_work`;
