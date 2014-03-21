<?php
/* This file is part of usp_ews USP
*
* The script is used to generate the class progress report for the lecturers
*/
	require_once(dirname(__FILE__) . '/../../../../config.php');
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php');
	require_once($CFG->dirroot.'/lib/excellib.class.php');
	
	// Global variables needed
	global $DB; // $OUTPUT;

	// Gather form data
	$courseid  = required_param('cid', PARAM_INT);     
	$roleid     = optional_param('roleid', 0, PARAM_INT); // which role to show
	$modactivity  = optional_param('modactivity', 0, PARAM_INT);       // overview tab
	$time  = optional_param('time', 0, PARAM_INT);       // overview tab
	$tmcriteria  = optional_param('tmcriteria', '', PARAM_ALPHA);       // overview tab
	$modaction  = optional_param('modaction', '', PARAM_ALPHA);     

	// Determine course and context
	$course   = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
	// update version 2.6
	$context = context_course::instance($course->id, MUST_EXIST);

	// Check user is logged in and capable of grading
	require_login($course, false);
	require_capability('block/usp_ews:overview', $context);

	// getting modules in the course
	$modinfo = get_fast_modinfo($course);
	// printing the header on the excel sheet
	$headertitle = '';
	
	// different roles in the course
	$roles = get_roles_used_in_context($context);
	if($roleid != 0)
		$headertitle .= $roles[$roleid]->name;
	else
		$headertitle .= get_string('allparticipants');
		
	$headertitle .= get_string('whohave', 'block_usp_ews');
	// building the sql
	// haviving conditions in whereas part
	$whereas = '';
	// if no specific activity selected then show for all actiivities with cmid != 0
	$cmidselected = false;
	$cmname = '';
	// if no selected activity
	if($modactivity != 0){
		$cmidselected = true;
		$cm = $modinfo->cms[$modactivity];
		$cmname = $cm->name;
		$whereas .= " AND cmid = $modactivity";
	}else{
		$whereas .= " AND cmid != 0";
	}

	// what action selected
	if($modaction == 'view'){
		 $whereas .= " AND action LIKE 'view%'";
		 $headertitle .= get_string('viewed', 'block_usp_ews');
	}else if($modaction == 'post'){
		$whereas .= " AND (action LIKE 'post%' OR action LIKE 'submit%' OR action LIKE 'add%')";
		$headertitle .= get_string('post_attempt', 'block_usp_ews');
	}else if($modaction == 'no'){
		$modaction = 'no';
		$headertitle .= get_string('no_particpated', 'block_usp_ews');
	}else{
		$modaction = 'all';
		$headertitle .= get_string('particpated', 'block_usp_ews');
	}
	// header title
	$headertitle .= get_string('in', 'block_usp_ews');
	
	// if activity selected
	if($cmidselected)
		$headertitle .= $cmname;
	else
		$headertitle .= get_string('allactivity', 'block_usp_ews');
	// if not time crieria selected
	if($tmcriteria != ''){
		$headertitle .= get_string($tmcriteria, 'block_usp_ews') . ' ' ;
		$headertitle .= userdate($time, get_string('strftimedate'));
	}
	// time criteria
	if($tmcriteria == 'before'){
		 $whereas .= " AND time < $time";
	}else if($tmcriteria == 'on'){
		$beginOfday = strtotime("midnight", $time);
		$endOfday   = strtotime("tomorrow", $beginOfday) - 1;
		$whereas .= " AND time > $beginOfday AND time < $endOfday";
	}else if($tmcriteria == 'after'){
		$whereas .= " AND time > $time";
	}else{
		$tmcriteria_rs = '';
	}
	// updated v2.6
	$relatedcxtarray = $context->get_parent_context_ids(true);
	
	$relatedcxtstring = implode(",", $relatedcxtarray);
	$relatedcontexts = 'IN (' . $relatedcxtstring . ') ';

	// details of the users
	$extrauserfields = get_extra_user_fields_sql($context, 'u', '', array(
			'id', 'username', 'firstname', 'lastname', 'idnumber','email', 'city', 'country',
			'picture', 'lang', 'timezone', 'maildisplay', 'imagealt', 'lastaccess'));

	$mainuserfields = user_picture::fields('u', array('username', 'idnumber','email', 'city', 'country', 'lang', 'timezone', 'maildisplay'));

	// if action selected, not interacted
	if($modaction != 'no'){
		$sql = "SELECT CONCAT(l.id,u.id), $mainuserfields, $extrauserfields l.cmid, ra.userid, l.action, l.lastview, l.actioncount AS numview
				FROM (SELECT *, MAX(TIME) AS lastview, COUNT(id) AS actioncount FROM {log} WHERE course = :courseid $whereas GROUP BY userid, cmid, ACTION ) l 
				JOIN (
					SELECT * FROM {role_assignments} WHERE contextid $relatedcontexts AND roleid = :roleid
				) ra ON l.userid = ra.userid
				LEFT JOIN {user} u ON u.id = ra.userid";
				
	   $countsql = "SELECT COUNT(*)
				FROM (SELECT *, MAX(TIME) AS lastview, COUNT(id) AS actioncount FROM {log} WHERE course = :courseid $whereas GROUP BY userid, cmid, ACTION ) l 
				LEFT JOIN (
					SELECT * FROM {role_assignments} WHERE contextid $relatedcontexts AND roleid = :roleid
				) ra ON l.userid = ra.userid
				JOIN {user} u ON u.id = ra.userid";
				
	}else{
	// else what are the criterias field, generate accordingly
		$sql = "SELECT ra.userid, $mainuserfields, $extrauserfields l.cmid, l.action, l.lastview, IFNULL(l.actioncount, 0)
					FROM (SELECT * FROM {role_assignments} WHERE contextid $relatedcontexts AND roleid = :roleid ) ra
					JOIN {user} u ON u.id = ra.userid
				LEFT JOIN (
					SELECT *, MAX(TIME) AS lastview, COUNT(id) AS actioncount FROM {log} WHERE course = :courseid $whereas GROUP BY userid, cmid, ACTION
				) l ON l.userid = ra.userid
				WHERE l.actioncount IS NULL";
		
		$countsql = "SELECT COUNT(*)
					FROM (SELECT * FROM {role_assignments} WHERE contextid $relatedcontexts AND roleid = :roleid ) ra
					JOIN {user} u ON u.id = ra.userid
				LEFT JOIN (
					SELECT *, MAX(TIME) AS lastview, COUNT(id) AS actioncount FROM {log} WHERE course = :courseid $whereas GROUP BY userid, cmid, ACTION
				) l ON l.userid = ra.userid
				WHERE l.actioncount IS NULL";
	}			
	// parameters for the sql
	$params['courseid'] = $course->id;
	$params['roleid'] = $roleid;
	
	$users = $DB->get_records_sql($sql, $params);
	
	$totalcount = $DB->count_records_sql($countsql, $params);
	
	// get the current time
	$now = time();
	// array holds the headings for the table in excel report
	$header = array();

	// user idnumber header column
    $header[] = get_string('idnumber'); 
	// column for lastname
	$header[] = get_string('lastname', 'block_usp_ews');
	 // column for firstname
	$header[] = get_string('firstname', 'block_usp_ews');

	// header for activity if any selected
	if(!$cmidselected){
		$header[] = get_string('activity', 'block_usp_ews');
	}
				
	// header for action if selected
	if($modaction != 'no'){
		$header[] = get_string('config_header_action', 'block_usp_ews');
	}
	
	// column for last access of the activity
	if (!isset($hiddenfields['lastaccess'])) {
		$header[] = get_string('lastaccess');
	}
	// column if the user done with the task
	$header[] = get_string('completed', 'block_usp_ews');
	
	// name of the file for excel report
	$filename = get_string('activityexcelreport', 'block_usp_ews');
	
	// getting current date to append to the filename of excel report
	$currentdate = date("Y.m.d");

	// Calculate file name
	$downloadfilename = clean_filename("{$course->shortname} $filename-$currentdate");
	
	// Creating a workbook
	$workbook = new MoodleExcelWorkbook($downloadfilename, 'Excel5');
	
	// Sending HTTP headers
	$myxls = $workbook->add_worksheet($filename);
	
	//updates in v2.6
	// formating the table and heading of the table
	//$format =& $workbook->add_format();
	$format = $workbook->add_format(array('color'=>'red', 'align'=>'left', 'bold'=>1));
	
	// formating each rows of the table
	//$formatd = & $workbook->add_format();
	$formatd = $workbook->add_format(array('align'=>'left'));

	//$formate = & $workbook->add_format();
	$formate = $workbook->add_format(array('align'=>'center'));
	
	//$format_incom = & $workbook->add_format();
	$format_incom = $workbook->add_format(array('color'=>'red'));
	//$format_incom->set_color('red');

	//$formatd->set_border(1);
	//$formatitle = & $workbook->add_format();
	$formatitle = $workbook->add_format(array('size'=>20, 'bold'=>1));
	$formatsubtitle = $workbook->add_format(array('size'=>14, 'bold'=>1));
	//$formatitle->set_bold(1);
	//$formatitle->set_size(12);
	// setting column width
	$myxls->set_column(0, 6, 15);
	// formating the title of the report in excel
	
	// if any activity selected
	// title in excel page
	if($cmidselected){
		if(isset($modinfo->cms[$modactivity])){
		
			$cm = $modinfo->cms[$modactivity];
			$cmname = $cm->name;
		}
	}else{  // else result is for all activity
		$cmname = get_string('all_activity', 'block_usp_ews');
	}
		
	// title printed in excel
	$myxls->write_string(0, 0, "$course->fullname $cmname Report", $formatitle);
	
	$myxls->write_string(2, 0, $headertitle, $formatsubtitle);
	
	// position of the starting of the table
	// that is cell B3 in excel
	$colhead = 0; // position at column 1
	$rownum = 4;  // position at row 2
	

	// writing table headers to there located cells
	foreach($header as $item){
		$myxls->write_string($rownum, $colhead, $item, $format);
		$colhead ++;
	}
	// increasing the row location by 1 to have the content below the header in right order
	$rownum ++;

	$timeformat = get_string('date_format_activity', 'block_usp_ews');	
	// Fill the content of the report
	// for each of the users, there appropiate data is filled 
	// with resect to each of the headers
	foreach($users as $rs){
		$colnum = 0; // position of the column, that is 2

		// print idnumber of the student
		$myxls->write_string($rownum, $colnum ++, $rs->idnumber, $formatd);
		
		// print lastname of the user
		$myxls->write_string($rownum, $colnum ++, strtoupper($rs->lastname), $formatd);
		// print firstname of the user
		$myxls->write_string($rownum, $colnum ++, $rs->firstname, $formatd);

		// if no activity selected then print activity name in each row
		if(!$cmidselected){
			if(isset($modinfo->cms[$rs->cmid])){
				$cm = $modinfo->cms[$rs->cmid];
				$cmname = $cm->name;
			}
			$myxls->write_string($rownum, $colnum ++, $cmname, $formatd);
		}
		// if no action selected then print each actions
		if($modaction != 'no'){
			$myxls->write_string($rownum, $colnum ++, $rs->action, $formatd);
		}
		
		// if some action seelected then when was last participated and number times participated
		if($modaction != 'no'){
			$myxls->write_string($rownum, $colnum ++, userdate($rs->lastview, $timeformat), $formatd);
					
			$completed = get_string('yes') . "(" . $rs->numview . ")";
			
			$myxls->write_string($rownum, $colnum ++, $completed, $formatd);
		}else{
			// student who never participated
			$myxls->write_string($rownum, $colnum ++, get_string('never', 'block_usp_ews'), $formatd);
			$myxls->write_string($rownum, $colnum ++, get_string('no'), $formatd);
		}


		// putting postion to next row where writing to excel will continue	
		$rownum ++;		
	}
		
	/// Close the workbook
	$workbook->close(); 
	 exit;

?>