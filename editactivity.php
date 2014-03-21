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
	
	require_once(dirname(__FILE__) . '/../../config.php');
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php');
	require_once($CFG->libdir.'/tablelib.php');
	
	// Gather form data
	$courseid = required_param('cid',PARAM_INT);  			 // course id
	$inst_id  = required_param('inst_id', PARAM_INT);	     // instance id of the block
		
	if (!$course = $DB->get_record('course', array('id' => $courseid))) {
		print_error('nocourseid');
	}
	
	require_login($course, false);	
	
	$context = context_course::instance($course->id, MUST_EXIST);
	
	$baseurl = new moodle_url('/blocks/usp_ews/editactivity.php', 
		array('cid'=> $course->id, 'inst_id'=> $inst_id));
	
	// RT - TODO: Check if this is really required
	$dbmanager = $DB->get_manager(); // loads ddl manager and xmldb classes
		
	$modules = usp_ews_modules_in_use($COURSE->id);
	
	// Set up page parameters and the navigation links
 	$PAGE->set_course($course);	
	$PAGE->set_url($baseurl);
	$PAGE->set_context($context);
	$title = get_string('config_block_title', 'block_usp_ews');
	$PAGE->set_title($course->shortname . ': ' . $title);
	$PAGE->set_heading($course->fullname . ': ' . $title);
	$PAGE->navbar->add(get_string('pluginname', 'block_usp_ews'));
	$PAGE->navbar->add($title);
	$PAGE->set_pagelayout('standard');  
	// for side blocks view
    //$PAGE->set_pagetype('course-view-' . $course->format);
	
	// Start page output	
	echo $OUTPUT->header();
	echo $OUTPUT->heading($title, 2);
	// starts the main div container
	
	echo $OUTPUT->container_start('block_usp_ews');
	
	$currentconfigtab = get_string('activity_config_tab', 'block_usp_ews');
	// include the tab for configuration
	include($CFG->dirroot.'/blocks/usp_ews/tabsconfig.php');
	
	$monitored = $DB->get_record('usp_ews_config', array('courseid' => $course->id, 'ewsinstanceid'=>$inst_id));
	
	// if the course is set for the first time then the block configuration is all set to default values
	if(empty($monitored))
	{
		$default_setting = new stdclass;
		$default_setting->courseid = $course->id;
		$default_setting->contextid = $context->id;
		$default_setting->ewsinstanceid = $inst_id;
		$default_setting->title = get_string('config_default_title', 'block_usp_ews');
		$default_setting->icon = EWS_DEFAULT_ICON;
		$default_setting->now = EWS_DEFAULT_NOW;
		$default_setting->loginweight = EWS_DEFAULT_LOGIN_WEIGHT;
		$default_setting->completionweight = EWS_DEFAULT_COMPLETION_WEIGHT;
		$default_setting->interactionweight = EWS_DEFAULT_INTERACT_WEIGHT;
		$default_setting->minlogin = EWS_DEFAULT_LOGIN_PER_WEEK;
		$default_setting->monitoreddata = '';
		$default_setting->coursestartdate = 0;
		$default_setting->lastupdatetimestamp = 0;
		$default_setting->processnew = 1;
		$default_setting->lastlogid = 0;
		
		// inserting all the default values
		if($DB->insert_record('usp_ews_config', $default_setting))
		{
			$monitored = $default_setting;
		}		
	}
	
	echo $OUTPUT->box_start('usp_ews_coursedate');
	echo HTML_WRITER::start_tag('p') 
		. get_string('currentdate', 'block_usp_ews') . userdate($course->startdate, get_string('strftimedate'))
		.  HTML_WRITER::end_tag('p');
	if($monitored->coursestartdate != $course->startdate)
	{
		require_once('coursedate_form.php');
		
		if ($data = data_submitted()) 
		{		
			if(isset($data->submitbutton)){
				if($record = $DB->get_record('usp_ews_config', array('courseid' => $data->cid, 'ewsinstanceid'=>$data->inst_id)))
				{
					$date = $data->startdate;
					$record->coursestartdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
					$record->lastupdatetimestamp = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
					
					$DB->update_record('usp_ews_config', $record);
					
					$course->startdate = $record->coursestartdate;
					$DB->update_record('course', $course);
					
					$params = array('inst_id'=>$data->inst_id, 'cid' => $data->cid);
					$urladd = new moodle_url('/blocks/usp_ews/editactivity.php', $params);
				
					redirect(new moodle_url($urladd), get_string('changes_saved', 'block_usp_ews'));
				}				
			}
		}
	
		if($monitored->coursestartdate == 0)
		{
			echo get_string('confirmdate', 'block_usp_ews');
		}
		else
		{
			echo get_string('datemismatch', 'block_usp_ews') . ' ' . get_string('confirmdate', 'block_usp_ews');
		}
		
		$form = new coursedate_form(null, array('inst_id'=>$inst_id));		
		$form->display();
	}
	echo $OUTPUT->box_end();
	
	
	
	// list of activities in the course
	// to check if monitoted activity still exists, to get name, visiblility
	$modinfo_incourse = get_array_of_activities($course->id);	
	
	// if activity configuration clicked
	// showing the list of monitored activity
	// displays none if none	
		
	// Define a table showing a list of users in the current role selection
	$tablecolumns = array();
	$tableheaders = array();
	
	// to check the activities already monitored so that they wont appear in the dropdown list
	$monitoredcmid = '';
	// if has capability to select checkbox
   
	// defines table column for select option
	// and puts its heading in tableheaders array
	$tablecolumns[] = 'itemname';
	$tableheaders[] = get_string('monitored_name', 'block_usp_ews');

	$tablecolumns[] = 'itemtype';
	$tableheaders[] = get_string('monitored_type', 'block_usp_ews');	
	
	$tablecolumns[] = 'criteria';
	$tableheaders[] = get_string('monitored_criteria', 'block_usp_ews');

	$tablecolumns[] = 'critical';
	$tableheaders[] = get_string('monitored_critical', 'block_usp_ews');

	$tablecolumns[] = 'locked';
	$tableheaders[] = get_string('config_title_locked', 'block_usp_ews');
	
	$tablecolumns[] = 'expectedby';
	$tableheaders[] = get_string('config_header_expected', 'block_usp_ews');
	
	$tablecolumns[] = 'option';
	$tableheaders[] = get_string('monitored_option', 'block_usp_ews');
	
	// calls for flexible table class
	// defines column and its heading using array filled above
	// defines base url for table
	$table = new flexible_table('block-usp_ews-monitoredactivity-'.$course->id);
	$table->define_columns($tablecolumns);
	$table->define_headers($tableheaders);
	$table->define_baseurl($baseurl->out());
	
	$table->set_attribute('cellspacing', '0');
	$table->set_attribute('style', 'margin-bottom: 10px;');
	$table->set_attribute('id', 'usp_ews_monitored_activity');
	$table->set_attribute('class', 'usp_ews_monitored generaltable generalbox');

	// adding css properties to the table
	//$table->column_style_all('padding', '5px 10px');
	$table->column_style_all('text-align', 'left');
	$table->column_style('critical', 'text-align', 'center');
	$table->column_style('locked', 'text-align', 'center');
	$table->column_style('itemname', 'width', '0');
	$table->column_style('itemtype', 'width', '10%');
	$table->column_style('criteria', 'width', '10%');
	$table->column_style('critical', 'width', '2%');
	$table->column_style('locked', 'width', '2%');
	$table->column_style('expectedby', 'width', '30%');
	$table->column_style('option', 'width', '60px');

	// setting the table
	$table->setup();
	// from langconfig.php in lang folder
	//$timeformat = get_string('strftimedatetime');
	$timeformat = get_string('strftimedaydatetime');
			  
	// if none of the activity configured
	// display none		
	if(empty($monitored->monitoreddata)){
		echo '';
	}else{ 
	
		// display each of monitored ativity with option to edit setting or delete completely
		
		$yes = '<img src="'. $OUTPUT->pix_url('tickconfig', 'block_usp_ews') .'" alt="Yes" />';
		$no = '<img src="'. $OUTPUT->pix_url('crossconfig', 'block_usp_ews') .'" alt="No" />';
			
		$monitoreddata = json_decode($monitored->monitoreddata);

		// get changed activity ids
		$activity_ids = usp_ews_changed_setting_ids($monitoreddata, $modinfo_incourse, $courseid, $modules);
	
		if(!empty($activity_ids)){
			$option_param = array('style'=> 'background:#FF9999;font-size:10pt; margin:0 20px 10px;padding:5px; border:1px solid #C0311E;');
			echo HTML_WRITER::start_tag('p', $option_param) . get_string('activity_setting_msg', 'block_usp_ews') . HTML_WRITER::end_tag('p');
		}
	
	
	
		foreach ($monitoreddata as $monitoritem){
			// in cases if the activity was deleted in course ut it will still be in the configuration
			if(isset($modinfo_incourse[$monitoritem->cmid])){

				$monitoredcmid .= $monitoritem->cmid . ' ';

				$data = array();
				$check_changes = false;
				if(in_array($monitoritem->cmid, $activity_ids)){
					$option_param = array('style'=> 'color:#C0311E;');
					
					$data[] = HTML_WRITER::start_tag('span', $option_param) . get_string('aestrict', 'block_usp_ews') . $modinfo_incourse[$monitoritem->cmid]->name . HTML_WRITER::end_tag('span');
					
					$check_changes = true;
				}
				else{
					$data[] = $modinfo_incourse[$monitoritem->cmid]->name;
				}
				$data[] = get_string($monitoritem->module, 'block_usp_ews');
				$data[] = get_string($monitoritem->action, 'block_usp_ews');
				$data[] = $monitoritem->critical == 1?$yes : $no ;
				$data[] = $monitoritem->locked == 1?$yes : $no ;
				
				if($monitoritem->locked == 1){
					$selected_module = $modules[$monitoritem->module];
					if ($dbmanager->table_exists($monitoritem->module)) {
						$sql = 'SELECT id, name';
						if ($monitoritem->module == 'assignment') {
							$sql .= ', assignmenttype';
						}
						if (array_key_exists('defaultTime', $selected_module)) {
							$sql .= ', '.$selected_module['defaultTime'].' as due';
						}
						$sql .= ' FROM {'. $monitoritem->module .'} WHERE id=\''. $monitoritem->instanceid .'\' AND course=\''. $course->id . '\'';
						$instance = $DB->get_record_sql($sql);
						
						$data[] = userdate($instance->due, $timeformat);
					}
				}else{
					$data[] = userdate($monitoritem->expected, $timeformat);
				}
				$edit = '<div>
					<a title="'. get_string('monitored_edit', 'block_usp_ews') .'" 
					   href="monitoractivity.php?inst_id='. $inst_id . '&amp;cid='. $course->id . '&amp;monitorededitcmid=' . $monitoritem->cmid . '&amp;action=' . $monitoritem->action . '&amp;critical=' . $monitoritem->critical . '&amp;expected=' . $monitoritem->expected; 
						if($check_changes)
							$edit .= '&amp;changedactivity=1';
					   $edit .= '&amp;locked=' . $monitoritem->locked .'&amp;option=2">' .
					   '<img'. ' src="'. $OUTPUT->pix_url('t/edit') . '" class="iconsmall" alt="'. get_string('monitored_edit','block_usp_ews') .'" />
					</a>
					<a title="'. get_string('monitored_remove', 'block_usp_ews') .'" href="monitoractivity.php?inst_id='. $inst_id . '&amp;cid='. $course->id . '&amp;monitorededitcmid=' . $monitoritem->cmid .'&amp;option=1">
					   <img'. ' src="'.$OUTPUT->pix_url('t/delete') . '" class="iconsmall" alt="'. get_string('monitored_remove', 'block_usp_ews') .'" />
					</a>
				</div>';
				$data[] = $edit;
				// adds data to table
				$table->add_data($data);	
				unset($data);
			}else{
				//remove from the array
				// for instant the activity removed from the course after being monitored
				$monitoredupdatedrecord = usp_ews_delete_monitored_activity($monitoreddata, $monitoritem->cmid);
				$monitored->monitoreddata = json_encode($monitoredupdatedrecord);
				$DB->update_record('usp_ews_config', $monitored);
			}
		}
		
	}
		echo '</form>';

		// prints the table
		$table->print_html();		


	$addnew = '<form action="monitoractivity.php" method="post" id="addactivityform">' .
			  '<input type="hidden" name="cid" value="'. $course->id .'" />' .
			  '<input type="hidden" name="monitoredcmid" value="'. $monitoredcmid .'" />' .
			  '<input type="hidden" name="inst_id" value="'. $inst_id .'" />' .
			  '<input type="hidden" name="option" value="0" />' .
			  '<div style="text-align: center;"><input class="button" type="submit" value="' .  get_string('monitored_add_activity', 'block_usp_ews') . '" /></div>' . 
			  '</form>';
	echo $addnew;			
 
		
	echo $OUTPUT->container_end();
	echo $OUTPUT->footer();	
