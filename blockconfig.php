<?php
/*
*   this scripts contains 3 tabs code, my_completion, my_interaction, my_achievement
*	my_completion: script shows the monitored activities in the progress bar
*   			   and checks if it is attempted or not according to the guideline 
*                  of the configuration of the monitored activities..
*				   Also it has the completion percentage value shown in box
*   my_interaction: includes the interaction.php script..
*   my_achievement: this part is captured from grade folder to dispaly detailed grade
*					this will be changed in future..
*/

	// Include required files
	require_once(dirname(__FILE__) . '/../../config.php');   // moodle config file
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php'); // usp_ews library file
	//include_once($CFG->libdir.'/ddllib.php');				 // database library file

	require_once('blockconfig_form.php');
	// Global variables needed
	global $DB, $USER, $PAGE, $OUTPUT, $CFG;

	// Gather form data
	$courseid = required_param('cid',PARAM_INT);  			 // course id
	$inst_id  = required_param('inst_id', PARAM_INT);	     // instance id of the block
	$error_msg  = optional_param('error_msg', 0, PARAM_INT);	     // if any error in total sum
	// Determine course information
	if (!$course = $DB->get_record('course', array('id' => $courseid))) {
		print_error('nocourseid');
	}
	
	// checks if user is logged in
	require_login($course, false);
	
	// html tags
	$poptions = array('class' => 'usp_ews_alert_error');
	$start_p = HTML_WRITER::start_tag('p', $poptions);
	$end_p = HTML_WRITER::end_tag('p');
	
	
	$monitored = $DB->get_record('usp_ews_config', array('courseid' => $course->id, 'ewsinstanceid'=>$inst_id));
	
	// starts the main div container
	$monitoredactivityurl = new moodle_url('/blocks/usp_ews/editactivity.php', array(
		'cid'=> $course->id, 'inst_id'=> $inst_id));

		
	// Determine context information from the function using course id
	$context = context_course::instance($course->id, MUST_EXIST);
	// build base url
	$baseurl = new moodle_url('/blocks/usp_ews/blockconfig.php', array(
		'cid'=> $course->id, 'inst_id'=>$inst_id));
		
	if ($data = data_submitted()) 
	{
	    // cancel clicked
		if (isset($data->cancel)) 
		{
			redirect(new moodle_url($monitoredactivityurl));
		// save clicked
		}else if(isset($data->submitbutton)){
		
			$total_weight = $data->config_comp_weight + $data->config_interact_weight + $data->config_login_weight;
			
			// if the weighting is greater than 100 then display error and do not proceed
			if ($total_weight > 100) {				
				$returnurl = new moodle_url('/blocks/usp_ews/blockconfig.php', array(
					'cid'=> $course->id, 'inst_id'=> $inst_id, 'error_msg'=>1));
				redirect(new moodle_url($returnurl));
			// if the weighting is less than 100 then display error and do not proceed
			}else if($total_weight < 100){
				$returnurl = new moodle_url('/blocks/usp_ews/blockconfig.php', array(
					'cid'=> $course->id, 'inst_id'=> $inst_id, 'error_msg'=>2));
				redirect(new moodle_url($returnurl));
			}else{
				// updating the default or previous set setting
				$monitored->title = $data->config_usp_ewsTitle;
				$monitored->icon = $data->config_usp_ewsBarIcons;
				$monitored->now = $data->config_displayNow;
				$monitored->loginweight = $data->config_login_weight;
				$monitored->completionweight = $data->config_comp_weight;
				$monitored->interactionweight = $data->config_interact_weight;
				$monitored->minlogin = $data->config_min_login;
				$monitored->studentview = $data->config_student_view;
				
				$DB->update_record('usp_ews_config', $monitored);
				
				redirect(new moodle_url($monitoredactivityurl), get_string('changes_saved', 'block_usp_ews'), 1);
			}
		}
	}
			
	// Set up page parameters and the navigation links
 	$PAGE->set_course($course);	
	$PAGE->set_url($baseurl);
	$PAGE->set_context($context);
	$title = get_string('config_block', 'block_usp_ews');
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
	
	$currentconfigtab = get_string('block_config_tab', 'block_usp_ews');
	include($CFG->dirroot.'/blocks/usp_ews/tabsconfig.php');
	
	echo $OUTPUT->container_start('block_usp_ews');

	// if the weighting not set properly then which message to show on the page
	if($error_msg == 1){
	    // if greater than 100
		echo $start_p . get_string('config_error_excessed', 'block_usp_ews') . $end_p;
	}else if($error_msg == 2){
		// if less than 100
		echo $start_p . get_string('config_error_less', 'block_usp_ews') . $end_p;
	}
	
	// including the block config form
	// passing required parameter to the form
	$form = new blockconfig_form(null, array('inst_id'=>$inst_id, 'title'=>$monitored->title, 'studentview'=>$monitored->studentview, 'icon'=>$monitored->icon, 'now'=>$monitored->now, 'com_weight'=>$monitored->completionweight, 'interact_weight'=>$monitored->interactionweight, 'login_weight'=>$monitored->loginweight, 'minlogin'=>$monitored->minlogin));
	
	// displaying of the form
	$form->display();

	// end of div	
	echo $OUTPUT->container_end();
	echo $OUTPUT->footer();	

?>
