<?php
/* This file is part of usp_ews USP
*
* The script is used to generate the class progress report for the lecturers
*/
	require_once(dirname(__FILE__) . '/../../../../config.php');
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php');
	require_once($CFG->dirroot.'/lib/excellib.class.php');
	require_once $CFG->dirroot.'/grade/export/lib.php';
	
	// Global variables needed
	global $DB, $CFG; // $OUTPUT;

	// Gather form data
	$courseid  = required_param('cid', PARAM_INT);   
	$name  = required_param('name', PARAM_RAW);       // overview tab
	$maxgrade  = required_param('maxgrade', PARAM_FLOAT);       // overview	
	$gradeid = required_param('gradeitemid', PARAM_INT); // instance we're looking at.
	$selectedgrade  = optional_param('selectedgrade', 0, PARAM_INT);       // overview tab
	$gradecriteria  = optional_param('gradecriteria', 0, PARAM_INT);       // overview tab

	// Determine course and context
	$course   = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
	// update version 2.6
	$context = context_course::instance($course->id, MUST_EXIST);

	// Check user is logged in and capable of grading
	require_login($course, false);
	require_capability('block/usp_ews:overview', $context);

	$headertitle = get_string('studentscored', 'block_usp_ews');
	// building the sql
	// having conditions in whereas part
	// grade condition
	switch ($gradecriteria)
		{
		case 0:
		  $gradesql = "AND finalgrade < $selectedgrade";
		  $headertitle .= get_string('lessthan', 'block_usp_ews') . ' ' . $selectedgrade;
		  break;
		case 1:
		  $gradesql = "AND finalgrade <= $selectedgrade";
		  $headertitle .= get_string('lessthanequal', 'block_usp_ews') . ' ' . $selectedgrade;
		  break;
		case 2:
		  $gradesql = "AND finalgrade = $selectedgrade";
		  $headertitle .= get_string('equalto', 'block_usp_ews') . ' ' . $selectedgrade;
		  break;
		case 3:
		  $gradesql = "AND finalgrade > $selectedgrade";
		  $headertitle .= get_string('greaterthan', 'block_usp_ews') . ' ' . $selectedgrade;
		  break;
		case 4:
		  $gradesql = "AND finalgrade >= $selectedgrade";
		  $headertitle .= get_string('greaterthanequal', 'block_usp_ews') . ' ' . $selectedgrade;
		  break;
		default:
		  $gradesql = "";
		} 
		// building the title
		$headertitle .= get_string('in', 'block_usp_ews') . $name;
		$extrauserfields = get_extra_user_fields_sql($context, 'u', '', array(
				'id', 'username', 'firstname', 'lastname', 'idnumber','email', 'city', 'country',
				'picture', 'lang', 'timezone', 'maildisplay', 'imagealt', 'lastaccess'));

		$mainuserfields = user_picture::fields('u', array('username', 'idnumber','email', 'city', 'country', 'lang', 'timezone', 'maildisplay'));


		//its a simple sql, will change
		$sql = "SELECT $mainuserfields, $extrauserfields g.finalgrade
				FROM (SELECT userid, finalgrade FROM mdl_grade_grades WHERE itemid = $gradeid $gradesql) g 
				 JOIN mdl_user u ON u.id = g.userid";
				
	   $countsql = "SELECT COUNT(*) 
					FROM (SELECT userid, finalgrade FROM mdl_grade_grades WHERE itemid = $gradeid $gradesql) AS count";
				

	$users = $DB->get_records_sql($sql);

	$totalcount = $DB->count_records_sql($countsql);
	
	// get the current time
	$now = time();
	// array holds the headings for the table in excel report
	$header = array();

	// user idnumber header column
    $header[] = get_string('idnumber'); 
	// column for username
	$header[] = get_string('lastname', 'block_usp_ews');
	  
	$header[] = get_string('firstname', 'block_usp_ews');
	// column for grade
	$header[] = get_string('grade');

	// name of the file for excel report
	$filename = get_string('gradeexcelreport', 'block_usp_ews');
	
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

	// title printed in excel
	$myxls->write_string(0, 0, "$course->fullname $name  Report " . get_string('select_grade_outof', 'block_usp_ews') . $maxgrade, $formatitle);
	
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
		
		// print fullname of the user

		$myxls->write_string($rownum, $colnum ++, strtoupper($rs->lastname), $formatd);
		$myxls->write_string($rownum, $colnum ++, $rs->firstname, $formatd);

		// user's mark in the selected activity
		$myxls->write_string($rownum, $colnum ++, usp_ews_format_number($rs->finalgrade), $formatd);

		// putting postion to next row where writing to excel will continue	
		$rownum ++;		
	}
		
	/// Close the workbook
	$workbook->close(); 
	 exit;

?>