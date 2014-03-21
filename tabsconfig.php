<?php  // $Id: tabs.php,v 1.43.2.8 2008/12/19 01:44:48 moodler Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set

/*
 * This script is used to add tabs for the usp_ews
**/

	$tabs = $row = $inactive = $activited = array();

	$courseurl = new moodle_url('/course/view.php', array('id'=> $course->id));
	
	$params = array('inst_id' => $inst_id,'cid' => $course->id);
	$urlblock = new moodle_url('/blocks/usp_ews/blockconfig.php', $params);

	$params_activity = array('inst_id' => $inst_id,'cid' => $course->id);
	$urlactivity = new moodle_url('/blocks/usp_ews/editactivity.php', $params_activity);
	// add activity
	$row[] = new tabobject('activity_config', $urlactivity, get_string('activity_config','block_usp_ews'));
	// configure block
	$row[] = new tabobject('block_config', $urlblock, get_string('block_config','block_usp_ews'));
	$row[] = new tabobject('course_page', $courseurl, get_string('course_page','block_usp_ews'));


	$tabs = array($row);


    /// Print out the tabs and continue!
    print_tabs($tabs, $currentconfigtab);
