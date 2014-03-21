<?php
/* This file is part of usp_ews USP
*
* The script is used to generate the class progress report for the lecturers
*/
	require_once(dirname(__FILE__) . '/../../../config.php');
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php');
	require_once($CFG->dirroot.'/lib/excellib.class.php');
	
	// Global variables needed
	global $DB; // $OUTPUT;

	// Gather form data
	$inst_id       = required_param('inst_id', PARAM_INT);
	$courseid	   = required_param('cid', PARAM_INT);
	$roleselected  = optional_param('role', 0, PARAM_INT);
	// file for filtered id
	$importcode    = optional_param('importcode', '', PARAM_FILE);
	$separator     = optional_param('separator', '', PARAM_ALPHA);
	$verbosescales = optional_param('verbosescales', 1, PARAM_BOOL);

	define('CSV_LINE_LENGTH', 2000);

	// Determine course and context
	$course   = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
	// update version 2.6
	$context = context_course::instance($course->id, MUST_EXIST);

	// Check user is logged in and capable of grading
	require_login($course, false);
	require_capability('block/usp_ews:overview', $context);

	// Get specific block config
	//$block = $DB->get_record('block_instances', array('id' => $inst_id));
	//$config = unserialize(base64_decode($block->configdata));

	$modules = usp_ews_modules_in_use($course->id);
	if (empty($modules)) {
		echo get_string('no_events_config_message', 'block_usp_ews');
		echo $OUTPUT->footer();
		die();
	}
		
	// getting configuration data from config table
	$configureddata = $DB->get_record('usp_ews_config', array('courseid' => $course->id, 'ewsinstanceid'=>$inst_id));

	// get monitored activities
	$monitoreddata = json_decode($configureddata->monitoreddata);
	// Check if activities/resources have been selected in config

	// if no events selected, display no activities to be monitored
	if ($monitoreddata==null) {
		echo get_string('no_events_message', 'block_usp_ews');
		echo $OUTPUT->footer();
		die();
	}
	if (empty($monitoreddata)) {
		echo get_string('no_visible_events_message', 'block_usp_ews');
		echo $OUTPUT->footer();
		die();
	}

	// getting the filtered students
	// if report for filtured students only
	$filterstudentid = array();
	if($importcode != ''){

		// sort out delimiter
		if (isset($CFG->CSV_DELIMITER)) {
			$csv_delimiter = $CFG->CSV_DELIMITER;

			if (isset($CFG->CSV_ENCODE)) {
				$csv_encode = '/\&\#' . $CFG->CSV_ENCODE . '/';
			}
		} else if ($separator == 'tab') {
			$csv_delimiter = "\t";
			$csv_encode = "";
		} else {
			$csv_delimiter = ",";
			$csv_encode = '/\&\#44/';
		}
		// have 1 folder for 1 user only
		// updated if next time its used
		$importcode = clean_param($importcode, PARAM_FILE);
		$filename = make_temp_directory('usp_ews_studentid_import/cvs/'.$USER->id);
		$filename = $filename.'/'.$importcode;	

		if (!$fp = fopen($filename, "r")) {
			print_error('cannotopenfile');
		}

		// --- get header (field names) ---
		$header = explode($csv_delimiter, fgets($fp, CSV_LINE_LENGTH));

		// gets the student ids
		while (!feof ($fp)) {
			$lines = explode($csv_delimiter, fgets($fp, CSV_LINE_LENGTH));
			foreach ($lines as $line) {
				// check if no empty id separted by comma
				if(!empty($line) || $line != ''){
					$filterstudentid[] = trim($line);
				}
			}
		}

		fclose($fp);			
	}

	// only generate the report for selected roles of users
	// this is either student, all participants, course coordinator
	$rolewhere = $roleselected!=0 ? "AND r.roleid = $roleselected" : '';

	// passing contextid and courseid in sql
	$params = array('contextid'=>$context->id, 'courseid'=>$course->id);
	// Get the list of users enrolled in the course
	$sql = "SELECT DISTINCT u.id, u.lastaccess, u.firstname, u.lastname, u.idnumber, u.email, u.id, gg.finalgrade
			 FROM {role_assignments} r, {user} u, {grade_grades} gg, {grade_items} gi 
			WHERE r.contextid = :contextid
			  AND r.userid = u.id AND gg.userid=u.id AND gg.itemid=gi.id AND gi.itemtype='course' AND gi.courseid= :courseid 
			  $rolewhere ORDER BY u.lastname, u.firstname";
		  
	// array holds records of all the users accounding to the selected role
	$users_data = array_values($DB->get_records_sql($sql, $params));

	// counting number of records/rows retrieved
	// this is used in to fill the number of rows in the excel sheet
	$numberofusers = count($users_data);

	// get the current time
	$now = time();
	// array holds the headings for the table in excel report
	$header = array(
                  get_string('idnumber'),
				  get_string('lastname', 'block_usp_ews'),
				  get_string('firstname', 'block_usp_ews'),
                  get_string('lastonline', 'block_usp_ews'),
				  get_string('numloginexcel', 'block_usp_ews'),
				  get_string('progress_percent', 'block_usp_ews'),
				  get_string('interaction_status', 'block_usp_ews'),
				  get_string('coursework', 'block_usp_ews'),
				  get_string('activity_not_done', 'block_usp_ews')
                );

	// name of the file for excel report
	$filename = get_string('excelreport', 'block_usp_ews');
	
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
	//$formatitle->set_bold(1);
	//$formatitle->set_size(12);
	// setting column width
	$myxls->set_column(0, 6, 15);
	// formating the title of the report in excel
	$myxls->write_string(0, 0, "$course->fullname Progress Report", $formatitle);
	
	// position of the starting of the table
	// that is cell B3 in excel
	$colhead = 0; // position at column 1
	$rownum = 1;  // position at row 2
	
	// writing table headers to there located cells
	foreach($header as $item){
		$myxls->write_string($rownum, $colhead, $item, $format);
		$colhead ++;
	}
	// increasing the row location by 1 to have the content below the header in right order
	$rownum ++;

	if(!empty($filterstudentid)){
	
			// Fill the content of the report
		// for each of the users, there appropiate data is filled 
		// with resect to each of the headers
		for ($i=0; $i<$numberofusers; $i++) {
			$colnum = 0; // position of the column, that is 2

			if(in_array($users_data[$i]->idnumber, $filterstudentid)){
				// getting last access by the user
				// if lastaccess = 0, means that user never accessed
				// else it gets the last access from the database
				if ($users_data[$i]->lastaccess == 0) {
					$lastonline = get_string('never');
				}
				else {
					// using userdate function of moodle
					// converts last access to an appropiate format
					// that is interms of days and hours
					$lastonline = userdate($users_data[$i]->lastaccess, get_string('strftimedate', 'langconfig'));
				}
				
				// getting information of the configured activities
				$events = usp_ews_event_information($course->id, $monitoreddata, $users_data[$i]->id, $modules);
				
				// Checked if a user has attempted/viewed/etc. an activity/resource
				
				$attempts = usp_ews_get_attempts($modules, $events, $users_data[$i]->id, $course->id);
				
				// used to count attempted activities
				$attemptcount = 0;
				$notattemptedactivity = '';
				
				// this loop counts the attempted activities from the monitored activities
				// used to catculate the progress percentage
				foreach ($events as $event) {
					if ($attempts[$event['type'].$event['id']]==1) {
						$attemptcount++;
					}else if($event['expected'] < $now) {
						$notattemptedactivity .= format_string(addSlashes($event['name'])) . ', ';
					}
				}
				// removing right comma
				$notattemptedactivity = rtrim($notattemptedactivity, " ,");
				//$comment = "($notattemptedactivity) Not Completed";
				// to calcuate progress percentage
				$progressvalue = usp_ews_get_progess_percentage($events, $attempts);	
				$progresspercentage = $progressvalue.'%';
				
				// to get the number of logins done by the user
				$userinteraction = $DB->get_record('usp_ews_interaction', array('userid' => $users_data[$i]->id, 'courseid' => $course->id));
				$lastsevenlogin=0;
				
				if(!empty($userinteraction))
					$lastsevenlogin = $userinteraction->lastsevenlogin;

				$interactionindex = 0;
				if(isset($userinteraction->interactindex))
					$interactionindex = $userinteraction->interactindex;
				// interaction message
				$traficlight = usp_ews_find_interaction_light($interactionindex);

				// final coursework mark exported
				$coursework = 0;
				if ($users_data[$i]->finalgrade=="")
					$coursework = "-";
				else
					$coursework = number_format($users_data[$i]->finalgrade, 1);				
					
					
				// putting all the data in array that are to be filled in the report	
				// formated the data
				$rows = array(
					'idnumber'=>$users_data[$i]->idnumber,
					'lastname'=>strtoupper($users_data[$i]->lastname),
					'firstname'=>$users_data[$i]->firstname,
					'lastonline'=>$lastonline,
					'numlogin'=> $lastsevenlogin,
					'progress'=>$progresspercentage,
					'interaction'=>$traficlight['msg'],
					'coursework'=> $coursework,
					'notattemptedactivity'=>$notattemptedactivity
					//'email'=>$users_data[$i]->email,
				);
				
				//writing the content to the excel for each students with formating
				$myxls->write_string($rownum, $colnum ++, $rows['idnumber'], $formatd);
				$myxls->write_string($rownum, $colnum ++, $rows['lastname'], $formatd);
				$myxls->write_string($rownum, $colnum ++, $rows['firstname'], $formatd);
				$myxls->write_string($rownum, $colnum ++, $rows['lastonline'], $formate);
				$myxls->write_number($rownum, $colnum ++, $rows['numlogin'], $formate);
				$myxls->write_string($rownum, $colnum ++, $rows['progress'], $formate);
				$myxls->write_string($rownum, $colnum ++, $rows['interaction'], $formate);
				$myxls->write_string($rownum, $colnum ++, $rows['coursework'], $formate);
				$myxls->write_string($rownum, $colnum ++, $rows['notattemptedactivity'], $format_incom);
				//$myxls->write_string($rownum, $colnum ++, $rows['email'], $formatd);

				// putting postion to next row where writing to excel will continue	
				$rownum ++;	
				
				// empty the rows array so next studets data uses same array
				$rows = null; 
			
			}
		}
	}else{ // if not filtured students
	
		// Fill the content of the report
		// for each of the users, there appropiate data is filled 
		// with resect to each of the headers
		for ($i=0; $i<$numberofusers; $i++) {
			$colnum = 0; // position of the column, that is 2


				// getting last access by the user
				// if lastaccess = 0, means that user never accessed
				// else it gets the last access from the database
				if ($users_data[$i]->lastaccess == 0) {
					$lastonline = get_string('never');
				}
				else {
					// using userdate function of moodle
					// converts last access to an appropiate format
					// that is interms of days and hours
					$lastonline = userdate($users_data[$i]->lastaccess, get_string('strftimedate', 'langconfig'));
				}
				
				// monitored activity's events
				$events = usp_ews_event_information($course->id, $monitoreddata, $users_data[$i]->id, $modules);
				
				// Checked if a user has attempted/viewed/etc. an activity/resource
				$attempts = usp_ews_get_attempts($modules, $events, $users_data[$i]->id, $course->id);
				
				// used to count attempted activities
				$attemptcount = 0;
				$notattemptedactivity = '';
				
				// this loop counts the attempted activities from the monitored activities
				// used to catculate the progress percentage
				foreach ($events as $event) {
					if ($attempts[$event['type'].$event['id']]==1) {
						$attemptcount++;
					}else if($event['expected'] < $now) {
						$notattemptedactivity .= format_string(addSlashes($event['name'])) . ', ';
					}
				}
				// removing right comma
				$notattemptedactivity = rtrim($notattemptedactivity, " ,");
				//$comment = "($notattemptedactivity) Not Completed";
				// to calcuate progress percentage
				$progressvalue = usp_ews_get_progess_percentage($events, $attempts);				
				$progresspercentage = $progressvalue.'%';
				
				// to get the number of logins done by the user
				$userinteraction = $DB->get_record('usp_ews_interaction', array('userid' => $users_data[$i]->id, 'courseid' => $course->id));
				$lastsevenlogin=0;
				
				if(!empty($userinteraction))
					$lastsevenlogin = $userinteraction->lastsevenlogin;

				// interaction index
				$interactionindex = 0;
				if(isset($userinteraction->interactindex))
					$interactionindex = $userinteraction->interactindex;
				// interaction message
				$traficlight = usp_ews_find_interaction_light($interactionindex);
				// coursework mark
				$coursework = 0;
				if ($users_data[$i]->finalgrade=="")
					$coursework = "-";
				else
					$coursework = number_format($users_data[$i]->finalgrade, 1);
				// putting all the data in array that are to be filled in the report	
				// formated the data
				$rows = array(
					'idnumber'=>$users_data[$i]->idnumber,
					'lastname'=>strtoupper($users_data[$i]->lastname),
					'firstname'=>$users_data[$i]->firstname,
					'lastonline'=>$lastonline,
					'numlogin'=> $lastsevenlogin,
					'progress'=>$progresspercentage,
					'interaction'=>$traficlight['msg'],
					'coursework'=> $coursework,
					'notattemptedactivity'=>$notattemptedactivity
					//'email'=>$users_data[$i]->email,
				);
				
				//writing the content to the excel for each students with formating
				$myxls->write_string($rownum, $colnum ++, $rows['idnumber'], $formatd);
				$myxls->write_string($rownum, $colnum ++, $rows['lastname'], $formatd);
				$myxls->write_string($rownum, $colnum ++, $rows['firstname'], $formatd);
				$myxls->write_string($rownum, $colnum ++, $rows['lastonline'], $formate);
				$myxls->write_number($rownum, $colnum ++, $rows['numlogin'], $formate);
				$myxls->write_string($rownum, $colnum ++, $rows['progress'], $formate);
				$myxls->write_string($rownum, $colnum ++, $rows['interaction'], $formate);
				$myxls->write_string($rownum, $colnum ++, $rows['coursework'], $formate);
				$myxls->write_string($rownum, $colnum ++, $rows['notattemptedactivity'], $format_incom);
				//$myxls->write_string($rownum, $colnum ++, $rows['email'], $formatd);

				// putting postion to next row where writing to excel will continue	
				$rownum ++;	
				
				// empty the rows array so next studets data uses same array
				$rows = null; 
			
			}
		}
	/// Close the workbook
	$workbook->close(); 
	 exit;

?>