ALTER TABLE `family_custom_master` MODIFY `fam_custom_comment` text NULL default NULL COMMENT 'comment for GDPR';
ALTER TABLE `person_custom_master` MODIFY `custom_comment` text NULL default NULL COMMENT 'comment for GDPR';

-- mise à jour supplémentaire
-- SET sql_mode='';
-- SET GLOBAL sql_mode='';

-- SET sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';
-- SET GLOBAL sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `user_usr` ADD `usr_TwoFaSecret` VARCHAR(255) NULL AFTER `usr_EDrive`;
ALTER TABLE `user_usr` ADD `usr_TwoFaSecretConfirm` BOOLEAN NOT NULL default 0;
ALTER TABLE `user_usr` ADD `usr_TwoFaRescuePasswords` VARCHAR(255) NULL;
ALTER TABLE `user_usr` ADD `usr_TwoFaRescueDateTime` datetime NOT NULL default '2000-01-01 00:00:00' COMMENT 'Only 60 seconds to validate the rescue password';

-- SHOW VARIABLES LIKE 'sql_mode';

-- ALTER TABLE `events_event` MODIFY `event_start` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';
-- ALTER TABLE `events_event` MODIFY `event_end` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

-- ALTER TABLE `event_types` MODIFY `type_defrecurDOY` date NOT NULL DEFAULT '2000-01-01';

-- ALTER TABLE `istlookup_lu` MODIFY `lu_LookupDateTime` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

-- ALTER TABLE `note_nte` MODIFY `nte_DateEntered` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

-- ALTER TABLE `person_per` MODIFY `per_DateEntered` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

-- ALTER TABLE `pledge_plg` MODIFY `plg_DateLastEdited` date NOT NULL DEFAULT '2000-01-01';

-- ALTER TABLE `user_usr` MODIFY `usr_LastLogin` datetime NOT NULL default '2000-01-01 00:00:00';
-- ALTER TABLE `user_usr` MODIFY `usr_showSince` date NOT NULL default '2018-01-01';
-- ALTER TABLE `user_usr` MODIFY `usr_showTo` date NOT NULL default '2019-01-01';


-- SET sql_mode='IGNORE_SPACE,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
-- SET GLOBAL sql_mode='IGNORE_SPACE,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';


ALTER TABLE `events_event` ADD `event_creator_user_id` mediumint(9) DEFAULT NULL COMMENT 'the owner is the creator';
ALTER TABLE `user_usr` ADD `usr_ManageCalendarResources` tinyint(1) unsigned NOT NULL default '0';

-- 2022-01-06
ALTER TABLE `note_nte` MODIFY `nte_Title` varchar(1000) DEFAULT '';

-- 2022-01-11
ALTER TABLE `user_usr` ADD `usr_HtmlSourceEditor` tinyint(1) unsigned NOT NULL default '0';


-- 2022-02-07
DROP TABLE `personlastmeeting_plm`;
DROP TABLE `personmeeting_pm`;

--
-- Table structure for table `plugin`
--

CREATE TABLE `plugin` (
  `plgn_ID` mediumint(8) unsigned NOT NULL auto_increment,
  `plgn_Name` varchar(255) DEFAULT '',
  `plgn_Description` text,
  `plgn_Category` enum('Dashboard', 'Personal', 'GDPR', 'Events','PEOPLE','GROUP', 'SundaySchool', 'Meeting', 'PastoralCare', 'Mail', 'Deposit', 'Funds', 'FreeMenu') NOT NULL default 'Personal' COMMENT 'For the left side menu bar',
  `plgn_UserRole_Dashboard_Availability` BOOLEAN NOT NULL default 0 COMMENT 'role choice (none/user/admin) available for dashboard plugins only ',
  `plgn_position` enum('inside_category_menu', 'after_category_menu') NOT NULL default 'after_category_menu' COMMENT 'Inside category menu or after',
  `plgn_securities` INT(40) DEFAULT 0 COMMENT 'See for this point EcclesiaCRM/User.php model class in : SecurityOptions 0 mean not dashboard',
  `plgn_default_orientation` enum('top', 'left', 'center', 'right') NOT NULL default 'left' COMMENT 'only for dashboard plugins',
  `plgn_default_color` enum('bg-gradient-blue text-white', 'bg-gradient-indigo text-white', 'bg-gradient-navy text-white', 'bg-gradient-maroon text-white', 'bg-gradient-purple text-white', 'bg-gradient-pink text-white', 'bg-gradient-red text-white', 'bg-gradient-orange text-black', 'bg-gradient-yellow text-black', 'bg-gradient-lime text-black', 'bg-gradient-green text-black', 'bg-gradient-teal text-black', 'bg-gradient-cyan text-black', 'bg-gradient-gray text-white') NOT NULL default 'bg-gradient-blue text-white' COMMENT 'Default Background dashboard color',
  `plgn_image` varchar(255) default NULL COMMENT 'Presentation image',
  `plgn_installation_path` varchar(5000) DEFAULT '' COMMENT 'path of the plugin',
  `plgn_activ` BOOLEAN NOT NULL default 0 COMMENT 'activation status',
  `plgn_version` varchar(50) NOT NULL default '',
  `plgn_prefix` varchar(50) NOT NULL default '' COMMENT 'prefix of the database tables, to avoid conflicts',
  `plgn_mailer` BOOLEAN NOT NULL default 0 COMMENT 'is a plugin mailer',
  PRIMARY KEY  (`plgn_ID`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `plugin`
--


--
-- Table structure for table `plugin_menu_bar`
--

CREATE TABLE `plugin_menu_bar` (
     `plgn_mb_ID` mediumint(8) unsigned NOT NULL auto_increment,
     `plgn_mb_plugin_name` varchar(255) DEFAULT '',
     `plgn_mb_plugin_Display_name` varchar(255) DEFAULT '',
     `plgn_mb_url` varchar(255) DEFAULT '' COMMENT 'URL Menubar',
     `plgn_bm_icon` varchar(255) DEFAULT '' COMMENT 'Icon MenuBar',
     `plgn_bm_grp_sec` varchar(255) DEFAULT '' COMMENT 'In lower case : usr_AddRecords, usr_EditRecords, usr_DeleteRecords, usr_ShowCart, usr_ShowMap, usr_EDrive, usr_MenuOptions, usr_ManageGroups, usr_ManageCalendarResources, usr_HtmlSourceEditor, usr_Finance, usr_Notes, usr_EditSelf, usr_Canvasser, usr_Admin, usr_showMenuQuery, usr_CanSendEmail, usr_ExportCSV, usr_CreateDirectory, usr_ExportSundaySchoolPDF, usr_ExportSundaySchoolCSV, usr_MainDashboard, usr_SeePrivacyData, usr_MailChimp, usr_GDRP_DPO, usr_PastoralCare',
     PRIMARY KEY  (`plgn_mb_ID`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `plugin_menu_bar`
--

-- 2022-02-19

--
-- Table structure for table `plugin_user_role`
--

CREATE TABLE `plugin_user_role` (
    `plgn_usr_rl_ID` mediumint(8) unsigned NOT NULL auto_increment,
    `plgn_usr_rl_user_id` mediumint(9) unsigned NOT NULL default '0',
    `plgn_usr_rl_plugin_id` mediumint(8) unsigned NOT NULL default '0',
    `plgn_usr_rl_role` enum('none', 'user', 'admin') NOT NULL default 'none' COMMENT 'user role : can be the thee enum parts',
    `plgn_usr_rl_visible` BOOLEAN NOT NULL default 1 COMMENT 'visible on dashboard (only for dashboard plugins)',
    `plgn_usr_rl_orientation` enum('top', 'left', 'center', 'right') NOT NULL default 'left' COMMENT 'only for dashboard plugins',
    `plgn_usr_rl_place` mediumint(9) unsigned NOT NULL default '0' COMMENT 'position on the dashboard',
    `plgn_usr_rl_color` enum('bg-gradient-blue text-white', 'bg-gradient-indigo text-white', 'bg-gradient-navy text-white', 'bg-gradient-maroon text-white', 'bg-gradient-purple text-white', 'bg-gradient-pink text-white', 'bg-gradient-red text-white', 'bg-gradient-orange text-black', 'bg-gradient-yellow text-black', 'bg-gradient-lime text-black', 'bg-gradient-green text-black', 'bg-gradient-teal text-black', 'bg-gradient-cyan text-black', 'bg-gradient-gray text-white') NOT NULL default 'bg-gradient-blue text-white' COMMENT 'Background dashboard color',
    `plgn_collapsed` BOOLEAN NOT NULL default 0 COMMENT 'the plugin is collapse on the dashboard by the default no',
    PRIMARY KEY  (`plgn_usr_rl_ID`),
    CONSTRAINT fk_plgn_usr_rl_user_id FOREIGN KEY (plgn_usr_rl_user_id) REFERENCES user_usr(usr_per_ID) ON DELETE CASCADE,
    CONSTRAINT fk_plgn_usr_rl_plugin_id FOREIGN KEY (plgn_usr_rl_plugin_id) REFERENCES plugin(plgn_ID) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `plugin_user_role`
--

-- 2022-03-20  B22

ALTER TABLE `volunteeropportunity_vol` ADD `vol_parent_ID` mediumint(8) unsigned DEFAULT NULL COMMENT 'parent volunteeropportunity_vol id';

ALTER TABLE `volunteeropportunity_vol`
    ADD CONSTRAINT fk_vol_parent_ID
        FOREIGN KEY (vol_parent_ID)
            REFERENCES volunteeropportunity_vol(vol_ID)
            ON DELETE SET NULL;

ALTER TABLE `volunteeropportunity_vol`
    DROP COLUMN vol_Order;

-- 2022-03-22

ALTER TABLE `volunteeropportunity_vol` ADD `vol_color` enum('bg-blue text-white', 'bg-indigo text-white', 'bg-navy text-white', 'bg-maroon text-white', 'bg-purple text-white', 'bg-pink text-white', 'bg-red text-white', 'bg-orange text-black', 'bg-yellow text-white', 'bg-lime text-white', 'bg-green text-white', 'bg-teal text-white', 'bg-cyan text-white', 'bg-gray text-black') NOT NULL default 'bg-blue text-white' COMMENT 'Color for a volunteer opportunity';
ALTER TABLE `volunteeropportunity_vol` ADD `vol_icon` enum('fas fa-layer-group','fas fa-users','fas fa-desktop','fas fa-file','fas fa-comment','fas fa-music','fas fa-photo-video','fas fa-envelope','fas fa-headset', 'fas fa-book-reader' ) NOT NULL default 'fas fa-file' COMMENT 'icon of the volunteer opportunity';


CREATE TABLE `tokens_password` (
   `tok_pwd_ID` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
   `tok_pwd_token_ID` VARCHAR(99) NOT NULL,
   `tok_pwd_must_change_PWD` BOOLEAN NOT NULL default 1,
   `tok_pwd_password` varchar(255) NOT NULL default '',
   `tok_pwd_ip` varchar(255) NOT NULL default '',
   PRIMARY KEY (`tok_pwd_ID`),
   CONSTRAINT fk_tok_pwd_token_ID
       FOREIGN KEY (tok_pwd_token_ID) REFERENCES tokens(token)
           ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- 2022-04-15
ALTER TABLE `events_event` ADD `event_link` varchar(255) default NULL;


-- 2022-09-12
ALTER TABLE `event_types` ADD `type_color` VARBINARY(10) DEFAULT '#000000';

-- 2022-09-28
ALTER TABLE `user_usr` ADD `usr_is_logged_in` tinyint(1) unsigned NOT NULL default '0';

-- 2022-10-03
ALTER TABLE `user_usr` ADD `usr_tLastOperation_date` datetime NOT NULL default '2000-01-01 00:00:00';

-- 2022-11-03
ALTER TABLE `events_event` ADD `event_allday` BOOLEAN NOT NULL default 0;

-- 2023-02-19
ALTER TABLE `user_usr` ADD `usr_jwt_secret` VARCHAR(255) default NULL;
ALTER TABLE `user_usr` ADD `usr_jwt_token` VARCHAR(2000) default NULL;

-- 2023-04-19

--
-- Table structure for table `send_news_letter_user_update`
--

CREATE TABLE `send_news_letter_user_update` (
  `snl_ID` mediumint(9) unsigned NOT NULL auto_increment,
  `snl_person_ID` mediumint(9) unsigned NOT NULL,
  `snl_state` enum('Add','Delete') NOT NULL default 'Add',
  PRIMARY KEY  (`snl_ID`),
  CONSTRAINT fk_snl_person_ID FOREIGN KEY (snl_person_ID) REFERENCES person_per(per_id) ON DELETE CASCADE  
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci PACK_KEYS=0 AUTO_INCREMENT=1 ;

--
-- update for new qry v2 person view and familyview : 2023-05-08
--

UPDATE `query_qry` SET qry_SQL = 'SELECT CONCAT(''<a href=v2/people/family/view/'',fam_ID,''>'',fam_Name,''</a>'') AS ''Family Name''   FROM family_fam Where fam_WorkPhone != ""' WHERE qry_ID = 1;
UPDATE `query_qry` SET qry_SQL = 'SELECT CONCAT(''<a href=v2/people/family/view/'',fam_ID,''>'',fam_Name,''</a>'') AS ''Family Name'', COUNT(*) AS ''No.''\nFROM person_per\nINNER JOIN family_fam\nON fam_ID = per_fam_ID\nGROUP BY per_fam_ID\nORDER BY ''No.'' DESC' WHERE qry_ID = 3;
UPDATE `query_qry` SET qry_SQL = 'SELECT per_ID as AddToCart,CONCAT(''<a href=v2/people/person/view/'',per_ID,''>'',per_FirstName,'' '',per_LastName,''</a>'') AS Name, CONCAT(per_BirthMonth,''/'',per_BirthDay,''/'',per_BirthYear) AS ''Birth Date'', DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(CONCAT(per_BirthYear,''-'',per_BirthMonth,''-'',per_BirthDay))),''%Y'')+0 AS ''Age'', per_DateDeactivated as "GDPR" FROM person_per WHERE DATE_ADD(CONCAT(per_BirthYear,''-'',per_BirthMonth,''-'',per_BirthDay),INTERVAL ~min~ YEAR) <= CURDATE() AND DATE_ADD(CONCAT(per_BirthYear,''-'',per_BirthMonth,''-'',per_BirthDay),INTERVAL (~max~ + 1) YEAR) >= CURDATE() ORDER by Age , Name DESC' WHERE qry_ID = 4;
UPDATE `query_qry` SET qry_SQL = 'SELECT per_ID as AddToCart, CONCAT(''<a href=v2/people/person/view/'',per_ID,''>'',COALESCE(`per_FirstName`,''''),'' '',COALESCE(`per_MiddleName`,''''),'' '',COALESCE(`per_LastName`,''''),''</a>'') AS Name, fam_City as City, fam_State as State, fam_Zip as ZIP, per_HomePhone as HomePhone, per_Email as Email, per_WorkEmail as WorkEmail,per_DateDeactivated as ''GDPR'' FROM person_per RIGHT JOIN family_fam ON family_fam.fam_id = person_per.per_fam_id WHERE ~searchwhat~ LIKE ''%~searchstring~%''' WHERE qry_ID = 15;
UPDATE `query_qry` SET qry_SQL = 'SELECT per_ID as AddToCart, CONCAT(''<a href=v2/people/person/view/'',per_ID,''>'',per_FirstName,'' '',per_LastName,''</a>'') AS Name,per_DateDeactivated as ''GDPR'' FROM person_per LEFT JOIN person2group2role_p2g2r ON per_id = p2g2r_per_ID WHERE p2g2r_grp_ID=~group~ ORDER BY per_LastName' WHERE qry_ID = 21;
UPDATE `query_qry` SET qry_SQL = 'SELECT per_ID as AddToCart, CONCAT(''<a href=v2/people/person/view/'',per_ID,''>'',per_FirstName,'' '',per_LastName,''</a>'') AS Name, per_DateDeactivated as ''GDPR'' FROM person_per WHERE per_cls_id =1' WHERE qry_ID = 24;
UPDATE `query_qry` SET qry_SQL = 'SELECT per_ID as AddToCart, CONCAT(''<a href=v2/people/person/view/'',per_ID,''>'',per_FirstName,'' '',per_LastName,''</a>'') AS Name, per_DateDeactivated as ''GDPR'' FROM person_per LEFT JOIN person2volunteeropp_p2vo ON per_id = p2vo_per_ID WHERE p2vo_vol_ID = ~volopp~ ORDER BY per_LastName' WHERE qry_ID = 25;
UPDATE `query_qry` SET qry_SQL = 'SELECT a.per_ID as AddToCart, CONCAT(''<a href=v2/people/person/view/'',a.per_ID,''>'',a.per_FirstName,'' '',a.per_LastName,''</a>'') AS Name, a.per_DateDeactivated as ''GDPR''  FROM person_per AS a LEFT JOIN person2volunteeropp_p2vo p2v1 ON (a.per_id = p2v1.p2vo_per_ID AND p2v1.p2vo_vol_ID = ~volopp1~) LEFT JOIN person2volunteeropp_p2vo p2v2 ON (a.per_id = p2v2.p2vo_per_ID AND p2v2.p2vo_vol_ID = ~volopp2~) WHERE p2v1.p2vo_per_ID=p2v2.p2vo_per_ID ORDER BY per_LastName' WHERE qry_ID = 100;
UPDATE `query_qry` SET qry_SQL = 'SELECT a.per_ID as AddToCart, CONCAT(''<a href=v2/people/person/view/'',a.per_ID,''>'',a.per_FirstName,'' '',a.per_LastName,''</a>'') AS Name, a.per_DateDeactivated as ''GDPR''  FROM person_per AS a LEFT JOIN person_custom pc ON a.per_id = pc.per_ID WHERE pc.~custom~ LIKE ''%~value~%'' ORDER BY per_LastName' WHERE qry_ID = 200;


--
-- Update Pledges/eGive and add comments : 2023-07-05
-- 

ALTER TABLE `pledge_plg` ADD `plg_MoveDonations_Comment` text NOT NULL default 'None';
ALTER TABLE `egive_egv` ADD `egv_MoveDonations_Comment` text NOT NULL default 'None';

