<?php
/*
*  to let Lecturer's know the pre-conditions of the system and let them agree to it
*
*/
	// Include required files
	require_once(dirname(__FILE__) . '/../../config.php');   // moodle config file
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php'); // usp_ews library file

	global $DB;

	// Gather form data
	$courseid = required_param('cid',PARAM_INT);  			 // course id
	$inst_id = required_param('inst_id', PARAM_INT);
	$confirm = optional_param('confirm', 0, PARAM_INT);
						
	// Determine course information
	if (!$course = $DB->get_record('course', array('id' => $courseid))) {
		print_error('nocourseid');
	}
	
	// checks if user is logged in
	require_login($course, false);

	// if confirmed that settings are ok then let them configure
	if($confirm == 1){

		$params = array('inst_id'=>$inst_id, 'cid' => $courseid);
		redirect(new moodle_url('/blocks/usp_ews/editactivity.php', $params));
	}
	
	// Determine context information from the function using course id
	$context = context_course::instance($course->id, MUST_EXIST);
	// build base url
	$baseurl = new moodle_url('/blocks/usp_ews/confirm_precondition.php', array(
		'id'=> $course->id));
			
	// Set up page parameters and the navigation links
 	$PAGE->set_course($course);	
	$PAGE->set_url($baseurl);
	$PAGE->set_context($context);
	$title = get_string('ensure_pretitle', 'block_usp_ews');
	$PAGE->set_title($title);
	$PAGE->set_heading($course->fullname);
	$PAGE->navbar->add($title);
	$PAGE->set_pagelayout('standard');  
	// for side blocks view
    //$PAGE->set_pagetype('course-view-' . $course->format);
	
	// Start page output
	// prints the heading and subheading of the page
	echo $OUTPUT->header();
	echo $OUTPUT->heading($title, 2);
	
	echo $OUTPUT->container_start('block_usp_ews');
	// parameter for confirmation
	$optionsyes = array('confirm'=>1, 'inst_id'=>$inst_id, 'cid'=>$courseid);

	// if not confirmed yet
	if($confirm == 0){
		// show message, agreement
		$option_param = array('style'=> 'background:#D1EBEF; padding:3px; border:1px solid #338c9a;');
		echo HTML_WRITER::start_tag('p', $option_param) . get_string('ensure_coursestartdate', 'block_usp_ews') . get_string('ensure_currentdate', 'block_usp_ews') . userdate($course->startdate, get_string('strftimedate')) . get_string('ensure_coursestartdatconfirm','block_usp_ews') . get_string('ensure_contact','block_usp_ews') .  HTML_WRITER::end_tag('p');	
		
		// button for them to accept
		// only if the pre-conditions are agreed by the course cordinators 
		// then the system will be installed in their course		
		echo $OUTPUT->box_start('noticebox');
		$formcontinue = new single_button(new moodle_url("confirm_precondition.php", $optionsyes), get_string('ensure_confirm', 'block_usp_ews'));
		$formcancel = new single_button(new moodle_url('/course/view.php', array('id'=>$courseid)), get_string('cancel', 'block_usp_ews'), 'get');
		echo $OUTPUT->confirm(get_string('ensure_agree', 'block_usp_ews'), $formcontinue, $formcancel);
		echo $OUTPUT->box_end();
	}
		
	echo $OUTPUT->container_end();
	echo $OUTPUT->footer();	
?>
