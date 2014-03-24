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

	require_once('monitoractivity_form.php');
	// Global variables needed
	global $DB, $USER, $PAGE, $OUTPUT, $CFG;

	// Gather form data
	$courseid = required_param('cid',PARAM_INT);  			 // course id
	$inst_id = required_param('inst_id',PARAM_INT);  			 // course id
	$monitoredcmid = optional_param('monitoredcmid', '',PARAM_RAW);  			 // course id
	$instanceid = optional_param('modinstanceid', 0, PARAM_INT); // instance we're looking at.
	$option = optional_param('option', 4, PARAM_INT); // instance we're looking at.
	$monitorededitcmid = optional_param('monitorededitcmid', 0, PARAM_INT); // instance we're looking at.
	$confirm = optional_param('confirm', 0, PARAM_INT);
	
	// Determine course information
	if (!$course = $DB->get_record('course', array('id' => $courseid))) {
		print_error('nocourseid');
	}
	
	// checks if user is logged in
	require_login($course, false);
	
	// starts the main div container
	$monitoredactivityurl = new moodle_url('/blocks/usp_ews/editactivity.php', array(
		'cid'=> $course->id, 'inst_id'=>$inst_id));
	
	// getting configuration from the ews config table
	$monitored = $DB->get_record('usp_ews_config', array('courseid' => $course->id, 'ewsinstanceid'=>$inst_id));
	// decoding monitored activity configuration
	$monitoreddatarecord = json_decode($monitored->monitoreddata);
	// geting modules details
	$modinfo = get_fast_modinfo($course);

	// afetr submitting the monitored form
	if ($data = data_submitted()) 
	{
		// if cancelled then return to list of monitored activity page
		if (isset($data->cancel)) 
		{
			redirect(new moodle_url($monitoredactivityurl));
		// else update the DB as in the form submitted
		}else if(isset($data->submitbutton)){
			// default values
			$critical = 0; 
			$expected = 0;
			$locked = 0;
			$duedate = 0;
			$optionselected = '';
			
			// if the follwing items are set from the form received
			if (isset($data->moduletype))  // module type
				$module = $data->moduletype;
			if (isset($data->moduleid))  // cmid
				$moduleid = $data->moduleid;
			if (isset($data->config_critical))  // if critical selected
				$critical = $data->config_critical;
			if (isset($data->modinstanceid))  // instance id of module
				$instance = $data->modinstanceid;					
			if (isset($data->config_locked)){  // locked to deadline
				$locked = $data->config_locked;
			} // whats the due date
			if (isset($data->duedate)){
				$duedate = $data->duedate;
			} // changing to timestamp
			if (isset($data->config_date_time)){
				$expectedtime = $data->config_date_time;
				$expected = mktime($expectedtime['hour'], $expectedtime['minute'], 0, $expectedtime['month'], $expectedtime['day'], $expectedtime['year']);
			} // selected action
			if (isset($data->config_action))
				$action = $data->config_action; 
			if (isset($data->cmid))
				$cmid = $data->cmid; 			
			if (isset($data->optionselected))
				$optionselected = $data->optionselected; 				

			// updating the configuration
			// editing option selected to edit the activity configuration setting
			if($optionselected == 'update'){
				// for each update the values
			   foreach($monitoreddatarecord as $monitor){
					if($monitor->cmid == $cmid){
						$monitor->action = $action;
						$monitor->critical = $critical;
						$monitor->expected = $expected;
						$monitor->duedate = $duedate;
						$monitor->locked = $locked;
					}
			   }
			    // update the record in the database 
			   	$monitored->monitoreddata = json_encode($monitoreddatarecord);
				$DB->update_record('usp_ews_config', $monitored);
				redirect(new moodle_url($monitoredactivityurl));

			}else{
				// if not update then its adding of general page
				// adding the activity in the configuration
				if(!empty($monitoreddatarecord)){
					// values are from the submitted form
					$monitoreddata = new stdclass;
					$monitoreddata->cmid = $cmid;
					$monitoreddata->modid = $moduleid;
					$monitoreddata->instanceid = $instance;
					$monitoreddata->module = $module;
					$monitoreddata->action = $action;
					$monitoreddata->critical = $critical;
					$monitoreddata->expected = $expected;
					$monitoreddata->duedate = $duedate;
					$monitoreddata->locked = $locked;

					array_push($monitoreddatarecord, $monitoreddata);
					$monitored->monitoreddata = json_encode($monitoreddatarecord);
					// update the list
					$DB->update_record('usp_ews_config', $monitored);
					redirect(new moodle_url($monitoredactivityurl));
			
				}
				else{
					// if list empty then insert the list in the column
					$newmonitoreddataarray = array();
					$monitoreddata = new stdclass;
					$monitoreddata->cmid = $cmid;
					$monitoreddata->modid = $moduleid;
					$monitoreddata->instanceid = $instance;
					$monitoreddata->module = $module;
					$monitoreddata->action = $action;
					$monitoreddata->critical = $critical;
					$monitoreddata->expected = $expected;
					$monitoreddata->duedate = $duedate;
					$monitoreddata->locked = $locked;

					array_push($newmonitoreddataarray, $monitoreddata);
					$monitored->monitoreddata = json_encode($newmonitoreddataarray);
					// inserting the monitored activity
					$DB->update_record('usp_ews_config', $monitored);
					redirect(new moodle_url($monitoredactivityurl));
				}
			}
		}
	}
	
	// deleting the configured activity
	if($confirm == 1){
		// remove from the seleceted monitored activity from the array
		$monitoredupdatedrecord = usp_ews_delete_monitored_activity($monitoreddatarecord, $monitorededitcmid);

		$monitored->monitoreddata = json_encode($monitoredupdatedrecord);
		$DB->update_record('usp_ews_config', $monitored);
		redirect(new moodle_url($monitoredactivityurl));
	}
		
	// Determine context information from the function using course id
	$context = context_course::instance($course->id, MUST_EXIST);
	// build base url
	$baseurl = new moodle_url('/blocks/usp_ews/monitoractivity.php', array(
		'cid'=> $course->id, 'inst_id'=>$inst_id));
			
	// Set up page parameters and the navigation links
 	$PAGE->set_course($course);	
	$PAGE->set_url($baseurl);
	$PAGE->set_context($context);
	$title = get_string('config_block_title', 'block_usp_ews');
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
	
	// adding the activity
	// if add activity button is clicked
	if($option == 0){

		// modules which are used in the course
		$modules = usp_ews_modules_in_use($course->id);

		// to see if the activity already configured then dont show it in the list
		$monitor_cmids = explode(" ", $monitoredcmid);
		
		// label for listing activiy		
		$instanceoptions = array();
		$instanceoptions[] = get_string('chooseactivity', 'block_usp_ews');

		// if not activity selected then show the list of activty
		if ($instanceid == 0){
			$sections = $modinfo->get_section_info_all();

			$sectionoptions = '';
			// list is based on the sections
			// activities in each sections
			 foreach ($modinfo->sections as $section=>$cmids) {
				if($sections[$section]->visible == 1){
					$sectionoptions= "-- ". get_section_name($course, $sections[$section])." --";
					$instances = array();
					// getting each activity detail and listing in dropdown menue
					foreach ($cmids as $cmid) {
						$cm = $modinfo->cms[$cmid];
						// Skip modules such as label which do not actually have links;
						// this means there's nothing to participate in
						if (!$cm->has_view()) {
							continue;
						}

						if(!in_array($cm->id, $monitor_cmids) && isset($modules[$cm->modname])){
						
							$cmname = $cm->name;
							if (textlib::strlen($cmname) > 55) {
								$cmname = textlib::substr($cmname, 0, 50)."...";
							}
							$instances[$cm->id] = format_string($cmname);
						}
					}
					// if no activity in the course the continue
					if (count($instances) == 0) {
						continue;
					}
				  // dropdown list in array
				  $instanceoptions[] = array($sectionoptions=>$instances);
				}
			 }
			// url for adding the activity
			$html_url = "monitoractivity.php?inst_id=$inst_id&cid=$course->id&amp;option=0";
			echo $OUTPUT->container_start('usp_ewsoverviewmenus');
			echo '&nbsp;'.get_string('select_activity', 'block_usp_ews') . ':&nbsp;';
			echo $OUTPUT->single_select($html_url, 'modinstanceid', $instanceoptions, $instanceid, null, 'activityform');
			
			// back button
			$params = array('inst_id' => $inst_id,'cid' => $course->id);
			$backurl = new moodle_url('/blocks/usp_ews/editactivity.php', $params);
			echo $OUTPUT->single_button($backurl, get_string('back'));
	
			echo $OUTPUT->container_end();
			
		// if some activity is selected to be added
		}else {
			$cm = $modinfo->cms[$instanceid];
			// if the activity is hidden then show message that its hidden
			// will be shown to students once its active
			if($cm->visible != 1){
				$poptions = array('class' => 'usp_ews_alert_error');
				$message = HTML_WRITER::start_tag('p', $poptions);
				$message .= get_string('hiddeninfor', 'block_usp_ews');
				$message .= HTML_WRITER::end_tag('p');
				echo $message;
			}
			// passing data in the form 
			$form = new monitoractivity_form(null, array('modname'=>$cm->modname, 'instancename'=>$cm->name, 'module'=> $cm->module, 'cmid'=>$instanceid, 'modinstanceid' => $cm->instance, 'visible'=>$cm->visible, 'inst_id'=>$inst_id, 'option'=>0));
			// displaying the form
			$form->display();
		}
	}	
	// deleting the configured activity
	// delete buttoon selected
	else if($option == 1){

		$cm = $modinfo->cms[$monitorededitcmid];
		$fullmodulename = get_string('modulename', $cm->modname);
		
		$strdeletecheckfull = get_string('deletecheckfull', '', "$fullmodulename '$cm->name'");

		$optionsyes = array('confirm'=>1, 'inst_id'=>$inst_id, 'cid'=>$courseid, 'monitorededitcmid'=>$monitorededitcmid);
	
		// showin the confirmation box
		if($confirm == 0){
			echo $OUTPUT->box_start('noticebox');
			$formcontinue = new single_button(new moodle_url("monitoractivity.php", $optionsyes), get_string('yes', 'block_usp_ews'));
			$formcancel = new single_button(new moodle_url($monitoredactivityurl), get_string('cancel', 'block_usp_ews'), 'get');
			echo $OUTPUT->confirm($strdeletecheckfull, $formcontinue, $formcancel);
			echo $OUTPUT->box_end();
		}
	}
	// edit the configuration of the activity
	else if($option == 2){
		$action = required_param('action',PARAM_RAW);
		$critical = required_param('critical',PARAM_INT);
		$expected = required_param('expected',PARAM_RAW);
		$locked = required_param('locked',PARAM_INT);
		$changedactivity = optional_param('changedactivity','0',PARAM_INT);
	
		$cm = $modinfo->cms[$monitorededitcmid];

		// send the filled data to the form
		$form = new monitoractivity_form(null, array('modname'=>$cm->modname, 'instancename'=>$cm->name, 'module'=> $cm->module, 'cmid'=>$monitorededitcmid, 'modinstanceid' => $cm->instance, 'visible'=>$cm->visible, 'changedactivity'=>$changedactivity, 'inst_id'=>$inst_id, 'optionselected'=>'update', 'action'=>$action, 'critical'=>$critical, 'locked'=>$locked, 'expected'=>$expected));
		// display the form
		$form->display();
	}
	else{
		// anything else then above conditions
		// redirect to configuration page
		redirect(new moodle_url($monitoredactivityurl));
	}

	echo $OUTPUT->container_end();
	echo $OUTPUT->footer();	

	