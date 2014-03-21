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
	require_once($CFG->libdir.'/tablelib.php');              // table library file
	include_once($CFG->libdir.'/ddllib.php');				 // database library file

	// Global variables needed
	//global $DB, $USER, $PAGE, $OUTPUT, $CFG;

	// Gather form data
	$inst_id       = required_param('inst_id', PARAM_INT);	     // instance id of the block
	$courseid = required_param('cid', PARAM_INT);  			 // course id
	$userid   = optional_param('uid', $USER->id, PARAM_INT); // user id
	$mode    = optional_param('modetab', "mycom", PARAM_ALPHA); // mode(my_completion, my_interaction, my_achievement)

	//html constantly used variable
	$start_div = HTML_WRITER::start_tag('div'); // <div>
	$option_param = array('style'=> 'padding: 0; margin: 0;'); // inline css
	$start_p = HTML_WRITER::start_tag('p', $option_param); // <p>
	$start_tr = HTML_WRITER::start_tag('tr'); // <tr>
	$end_div = HTML_WRITER::end_tag('div'); // </div>
	$end_p = HTML_WRITER::end_tag('p'); // </p>
	$end_td = HTML_WRITER::end_tag('td'); // </td>
	$end_tr = HTML_WRITER::end_tag('tr'); // </tr>
	$end_table = HTML_WRITER::end_tag('table'); // </table>
	$start_h3 = HTML_WRITER::start_tag('h3'); // <h3>
	$end_h3 = HTML_WRITER::end_tag('h3'); // </h3>
	$start_span = HTML_WRITER::start_tag('span'); // <span>
	$end_span = HTML_WRITER::end_tag('span'); // </span>
	
	// variable to hold progress avlue
	$progressval = 0;
	
	// Determine course information
	if (!$course = $DB->get_record('course', array('id' => $courseid))) {
		print_error('nocourseid');
	}
	
	// checks if user is logged in
	require_login($course, false);
	
    // update of v2.6
	//$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);	
	if (class_exists('context_course')) {
		$context = context_course::instance($course->id);
	} else {
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
	}
	
	// basic access checks
	require_capability('gradereport/user:view', $context);
	
	if (empty($userid)) {
		require_capability('moodle/grade:viewall', $context);

	} 
	else {
		if (!$DB->get_record('user', array('id'=>$userid, 'deleted'=>0)) or isguestuser($userid)) {
			print_error('invaliduser');
		}
	}

	$access = false;
	if (has_capability('moodle/grade:viewall', $context)) {
		//ok - can view all course grades
		$access = true;

	} 
	else if ($userid == $USER->id && has_capability('moodle/grade:view', $context) && $course->showgrades) {
		//ok - can view own grades
		$access = true;

	} 
	else if (has_capability('moodle/grade:viewall', get_context_instance(CONTEXT_USER, $userid)) && $course->showgrades) {
		// ok - can view grades of this user- parent most probably
		$access = true;
	}

	if (!$access) {
		// no access to grades!
		print_error('nopermissiontoviewgrades', 'error',  $CFG->wwwroot.'/course/view.php?id='.$courseid);
	}

	// build base url
	$baseurl = new moodle_url('/blocks/usp_ews/mydashboard.php', array(
		'inst_id' => $inst_id,
		'cid'=> $courseid,
		'modetab'=>$mode));
			
	// Set up page parameters and the navigation links
 	$PAGE->set_course($course);	
	$PAGE->set_url($baseurl);
	$PAGE->set_context($context);
	$title = get_string($mode , 'block_usp_ews');
	$strblockname = get_string("config_default_title", "block_usp_ews");
	$strmystats = get_string("mystats", "block_usp_ews");
	$PAGE->set_title($title);
	$PAGE->set_heading($course->fullname);
	$PAGE->navbar->add($strmystats);
	$PAGE->navbar->add($title);
	$PAGE->set_pagelayout('standard');  

	// Start page output
	// prints the heading and subheading of the page
	echo $OUTPUT->header();
	echo $OUTPUT->heading($title, 2);
	// starts the main div container
	echo $OUTPUT->container_start('block_usp_ews');

	// sets the current table to my_competion
	$currenttab = $mode;
	$showroles = 1;
	// includes the tab file to have tabs that can be easily be used
	include($CFG->dirroot.'/blocks/usp_ews/tabs.php');
	
	// using switch case
	switch ($mode) {
		// My completion
		case "mycom":			
			// Check if any activities/resources have been created	
			$modules = usp_ews_modules_in_use($courseid);
			
			// if no activities/resources have been created
			// displays message no events configured
			if (empty($modules)) {
				echo get_string('no_events_config_message', 'block_usp_ews');
			}
			// getting configuration from ews config table
			$configureddata = $DB->get_record('usp_ews_config', array('courseid' => $courseid, 'ewsinstanceid'=>$inst_id));
			// decoding the monitored activity
			$monitoreddata = json_decode($configureddata->monitoreddata);
			// Check if activities/resources have been selected in config
			//$events = usp_ews_event_information($config, $courseid, $modules);
			$events = usp_ews_event_information($courseid, $monitoreddata, $userid, $modules);
			
			// if events not selected then 
			// displays no event or no event visible message
			if ($events==null) {
				echo '<div class="usp_ews_notice">' . get_string('no_events_message', 'block_usp_ews') . '</div>';
			}
			else if (empty($events)) {
				echo '<div class="usp_ews_notice">' . get_string('no_visible_events_message', 'block_usp_ews') . '</div>';
			}
			// if events configured
			else 
			{		
				
				$min_satisfactory = EWS_DEFAULT_SATISFACTORY;
				// start of content div
				$divoptions = array('id'=>'usp_ews_mydashboardcontentt');
				$contenthtml = HTML_WRITER::start_tag('div', $divoptions);
				
				// tables inline css properties
				$tableoptions = array(						 
						 'id' => 'usp_ews_mydashboard_layout-table',						 
						 'summary' => 'Layout table',
						 'style' => 'width:auto;min-width:300px;');
				
				// start of table
				$contenthtml .= HTML_WRITER::start_tag('table', $tableoptions); // <table>
				$contenthtml .= $start_tr; // <tr>
				
				/* 1st Column */
				// inline css properties for td
				$celloptions = array('id' => 'usp_ews_mydashboard_left-column',
									'class' => 'no-padding',									
									'style' => 'width:70%;');
				$contenthtml .= HTML_WRITER::start_tag('td', $celloptions); // <td>
				
				// div inline css
				$divoptions = array('class' => 'usp_ews_border-bottom-radius',
									'id'=>'usp_ews_progresstracker');
				$contenthtml .= HTML_WRITER::start_tag('div', $divoptions); // <div>
				// printing header
				$divoptions2 = array('class' => 'usp_ews_header');
				$contenthtml .= HTML_WRITER::start_tag('div', $divoptions2);			
				$contenthtml .= get_string('progress_tracker', 'block_usp_ews') . $end_div;
				
				$divoptions3 = array('class' => 'usp_ews_progresstrackercontent');
				$contenthtml .= HTML_WRITER::start_tag('div', $divoptions3); // <div>
				
				// displays check the progress title
				$paraoptions = array('style' => 'text-align: left; padding-top: 4px; margin-bottom: 5px;');
				$contenthtml .= HTML_WRITER::start_tag('p', $paraoptions);
				$contenthtml .= get_string('check_progress', 'block_usp_ews') . $end_p;
				
				// Checks if a user has attempted/viewed/etc. an activity/resource
				//$attempts = usp_ews_get_attempts($modules, $config, $events, $USER->id, $course->id);
				$attempts = usp_ews_get_attempts($modules, $events, $userid, $courseid);
				// adds the monitored activities in the progress bar to content html 
				$contenthtml .= usp_ews_progress_bar($configureddata, $events, $inst_id, $userid,$attempts, false, 'mydashboard');
				// Organise access to JS
				// access to module.js
				// this is yui used to show more information in the progress bar
				$jsmodule = array(
					'name' => 'block_usp_ews',
					'fullpath' => '/blocks/usp_ews/js/module.js',
					'requires' => array(),
					'strings' => array(
						array('time_expected', 'block_usp_ews'),
					),
				);
				$displaydate = !isset($configureddata->now) || $configureddata->now==1;
				$arguments = array($CFG->wwwroot, array_keys($modules), $displaydate);
				$PAGE->requires->js_init_call('M.block_usp_ews.init', $arguments, false, $jsmodule);
				
				// alternative key values
				$showkey = get_string('show_key', 'block_usp_ews');		
				
				$divoptions4 = array('id' => 'usp_ews_colorkey');
				$contenthtml .= HTML_WRITER::start_tag('div', $divoptions4); // <div> with its properties
				
				// adds color key for progress bar to content html
				$option_param1 = array('class'=> 'keyboxcontent_p'); // inline css
				$start_keybox_p = HTML_WRITER::start_tag('p', $option_param1); // <p>
				
				$paraoptions1 = array('class' => 'colorkeyheading');
				$contenthtml .= HTML_WRITER::start_tag('p', $paraoptions1) . get_string('colorkeyheading', 'block_usp_ews') . $end_p; // <p> with its properties
				$notattemptedcolour = get_string('notAttempted_colour', 'block_usp_ews');
				$spanoptions_notattempted = array('class' => 'keybox',
									'style' => 'background-color: ' . get_string('notAttempted_colour', 'block_usp_ews'));
				$contenthtml .= $start_keybox_p . HTML_WRITER::start_tag('span', $spanoptions_notattempted) . $end_span 
								. $start_span . get_string('overdueactivity', 'block_usp_ews') . $end_span. $end_p;
				
				$spanoptions_attempted = array('class' => 'keybox',
									'style' => 'background-color: ' . get_string('attempted_colour', 'block_usp_ews'));
				$contenthtml .= $start_keybox_p . HTML_WRITER::start_tag('span', $spanoptions_attempted) . $end_span 
								. $start_span . get_string('completedactivity', 'block_usp_ews') . $end_span. $end_p;
				
				$spanoptions_notdue = array('class' => 'keybox',
											'style' => 'background-color: ' . get_string('futureNotAttempted_colour', 'block_usp_ews'));
				$contenthtml .= $start_keybox_p . HTML_WRITER::start_tag('span', $spanoptions_notdue) . $end_span 
								. $start_span . get_string('futureactivity', 'block_usp_ews') . $end_span. $end_p;
				
				$contenthtml .= $end_div . $end_div . $end_div . $end_td;
				
				/* 2nd Column */
				// spacing the table's td with 10px width
				// gives better format of display
				$celloptions1 = array('style' => 'width:10px;');
				$contenthtml .= HTML_WRITER::start_tag('td', $celloptions1) . $end_td;

				/*  3rd Column 	
				 *  this column in the table has completion percentage 
				 *  this is only based only on monitored activities 
				**/
				// inline css and adding class and id for <td>
				$celloptions3 = array('id' => 'right-column',
							'class' => 'no-padding',
							'valign' => 'top',
							'style' => 'width:auto;max-width:180px;');
				$contenthtml .= HTML_WRITER::start_tag('td', $celloptions3); // <td>
				
				// adding div's class and id
				$divoptions4 = array('class' => 'usp_ews_border-bottom-radius',
										'id' => 'usp_ews_completion_gauge');
				$contenthtml .= HTML_WRITER::start_tag('div', $divoptions4); // <div>
				
				// adding heading
				$divoptions5 = array('class' => 'usp_ews_header');
				$contenthtml .= HTML_WRITER::start_tag('div', $divoptions5);
				$contenthtml .= get_string('completion_percent', 'block_usp_ews') . $end_div;
				
				// adding content
				$divoptions7 = array('class' => 'content');
				$contenthtml .= HTML_WRITER::start_tag('div', $divoptions7);
				
				// calculation of progress completion percentage
				$progressval = usp_ews_get_progess_percentage($events, $attempts);
				$divoptions8 = array('class' => 'usp_ews_completion_percent', 'id'=>'usp_ews_completion_percentID');
				
				// div to display progress completion value
				$contenthtml .= HTML_WRITER::start_tag('div', $divoptions8);
				// inline css to format display value
				$paraoptions2 = array('class' => 'usp_ews_percent_value', );				
				$contenthtml .= HTML_WRITER::start_tag('p', $paraoptions2); // <p>
				// adding progress completion percentage to content html
				$contenthtml .= "$progressval%" . $end_p . $end_div; // </p></div>
				$color = usp_ews_find_color_percentage($progressval/100);
				$contenthtml .= usp_ews_completion_progress($progressval, $color);
				$contenthtml .= $start_p . '<em>' . get_string('min_requirement', 'block_usp_ews', $min_satisfactory) . '</em>' . $end_p;
				// display completion till current time
				$contenthtml .= $start_p . get_string('completion_tillnow', 'block_usp_ews') . $end_p;
				// disply the current time
				$paraoptions4 = array('class'=>'usp_ews_date_displayNow');
				$contenthtml .= HTML_WRITER::start_tag('p', $paraoptions4); // <p>
				// $contenthtml .= userdate(time()) . $end_p; // </p>   getting current time				
				$contenthtml .= userdate(time()) . $end_p; // </p>
				$contenthtml .= $end_div . $end_div . $end_td . $end_tr . $end_table . $end_div; // </div></div></td><tr></table></div>
			    // displaying the whole content
			    echo $contenthtml;   
	
			}			
		break;

		// my interaction tab
		// if participation tab clicked
		// includes interaction file that has all content on interaction
        case "myint" :
			
			$contenthtml = '';
			include('interaction.php');
		break;
		
		// my achievement tab
		// if coursework tab clicked
		// from the grade folder of moodle
		case "aboutus" :
			$contenthtml = '';
			include('about_us.php');
		break;
	
	// if none of the mode matches 3 specific mode
	// then invalid mode
	default:
		echo get_string("invalidmode", "block_usp_ews");
 }
 
	// RT 12022013 - Remove this message when functionality is stable
	$strNote = '<p style="margin: 20px 50px; background:#D1EBEF;font-size:8pt; padding:5px; border:1px solid #338c9a;">This is an <b>experimental</b> module, developed by FSTE. For any queries or errors regarding this module, please email singh_vn@usp.ac.fj</p>';
	echo $strNote;
		
	echo $OUTPUT->container_end();
	echo $OUTPUT->footer();	
