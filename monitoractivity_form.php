<?php

/* form for adding the activity in the configuration
* it's automatic monitored
* clritical can be selected
* lock to deadline
* what action to be taken
* what is the expected time that action should be completed
*/
require_once($CFG->dirroot.'/blocks/usp_ews/lib.php'); // usp_ews library file
require_once($CFG->libdir . '/formslib.php');

class monitoractivity_form extends moodleform {

	public function definition() {
        global $CFG, $COURSE, $DB, $OUTPUT;

		// Reload user info to get most current values
		$course = $DB->get_record('course', array('id' => $COURSE->id));
		
		$dbmanager = $DB->get_manager(); // loads ddl manager and xmldb classes
		
		$mform =& $this->_form;
		$mform->addElement('hidden', 'cid', $COURSE->id);

		// get modules used in the course
		$modules = usp_ews_modules_in_use($COURSE->id);

		// geting the modname from selected activity detail
		$module = $this->_customdata['modname'];
		$selected_module = $modules[$module];
		
		// check if the table exists for the module
		// see if duedate is associated with it
		if ($dbmanager->table_exists($module)) {
                $sql = 'SELECT id, name';
                if ($module == 'assignment') {
                    $sql .= ', assignmenttype';
                }
                if (array_key_exists('defaultTime', $selected_module)) {
                    $sql .= ', '.$selected_module['defaultTime'].' as due';
                }
                $sql .= ' FROM {'.$module.'} WHERE id=\''. $this->_customdata['modinstanceid'] .'\' AND course=\''.$COURSE->id.'\'';
				// getting details of the activity
                $instance = $DB->get_record_sql($sql);
		}
		// adding options to be performed on the activity
		$mform->addElement('html', '<div class="usp_ewsConfigBox">');

		// Icon, module type and name
		$htmlstr = '<div class="act_name">'
			. $OUTPUT->pix_icon('icon', get_string('pluginname', $module), 'mod_'.$module, array('class' => 'icon')) 
			. '&nbsp;<em>' . get_string($module, 'block_usp_ews') . '</em>: '
			. format_string($this->_customdata['instancename'])
			.  '</div>';
		
		$mform->addElement('header', 'general', $htmlstr);
		
		// checkbox for critical activity configuration
		$mform->addElement('checkbox', 'config_critical', get_string('config_header_critical', 'block_usp_ews'));
		// if the configured activity is to be edited then fill current value of critical
		// else keep it not critical by default
		if(isset($this->_customdata['optionselected']))
			$mform->setDefault('config_critical', $this->_customdata['critical']);
		else
			$mform->setDefault('config_critical', false);
		$mform->addHelpButton('config_critical',
							  'what_does_critical_mean', 'block_usp_ews');
		
		// Allow locking turned on or off
		// locking to deadline
		if (isset($selected_module['defaultTime']) && $instance->due != 0) {
			$mform->addElement('checkbox', 'config_locked', get_string('config_header_locked', 'block_usp_ews'));
			if(isset($this->_customdata['optionselected']))
				$mform->setDefault('config_locked', $this->_customdata['locked']);
			else
				$mform->setDefault('config_locked', 0);
			
			$mform->addHelpButton('config_locked',
								  'what_locked_means', 'block_usp_ews');
								  
			$mform->addElement('hidden', 'duedate', $instance->due);
			$mform->setType('duedate', PARAM_INT);
		}
		// if there was changes in the setting of the activity 
		// this changes the duedate to current duedate after confirmed
		if(isset($this->_customdata['changedactivity']) && $this->_customdata['changedactivity'] == 1){
			$mform->addElement('hidden', 'duedate', $instance->due);
			$mform->setType('duedate', PARAM_INT);
		}
		// Print the date selector
		$mform->addElement('date_time_selector',
						   'config_date_time',
						   get_string('config_header_expected', 'block_usp_ews'));
		
		$mform->disabledif ('config_date_time',
							'config_locked', 'eq', 1);

		// Assume a time/date for a activity/resource
		$expected = null;
		$datetimepropery = 'date_time_';
		if (
			isset($this->block->config) &&
			property_exists($this->block->config, $datetimepropery)
		) {
			$expected = $this->block->config->$datetimepropery;
		}
		if(isset($this->_customdata['optionselected']))
			$expected = $this->_customdata['expected'];
		
		// if not due date then by default set time to date, 7days after the current time
		// cordinators can change it accordingly
		if (empty($expected)) {

			// If there is a date associated with the activity/resource, use that
			if (isset($selected_module['defaultTime']) && $instance->due != 0) {
				$expected = usp_ews_default_value($instance->due);
			}

			// Assume 5min before the end of the current week
			else {
				$currenttime = time();
				$timearray = localtime($currenttime, true);
				$endofweektimearray =
					localtime($currenttime + (7-$timearray['tm_wday'])*86400, true);
				$expected = mktime(23,
								   55,
								   0,
								   $endofweektimearray['tm_mon']+1,
								   $endofweektimearray['tm_mday'],
								   $endofweektimearray['tm_year']+1900);
			}
		}
		
		$mform->setDefault('config_date_time', $expected);
		$mform->addHelpButton('config_date_time',
							  'what_expected_by_means', 'block_usp_ews');	

		 // Print the action selector for the event
		$actions = array();
		foreach ($selected_module['actions'] as $action => $sql) {
			// Before allowing pass marks, see that Grade to pass value is set
			if ($action == 'passed') {
				$params = array('itemmodule'=>$module, 'iteminstance'=>$instance->id);
				$gradetopass = $DB->get_record('grade_items', $params, 'gradepass');
				if ($gradetopass && $gradetopass->gradepass > 0) {
					$actions[$action] = get_string($action, 'block_usp_ews');
				}
			}
			else {
				$actions[$action] = get_string($action, 'block_usp_ews');
			}
		}
		// if completion tracking configuration is set
		if (isset($CFG->enablecompletion) && $CFG->enablecompletion==1) {
			$cm = get_coursemodule_from_instance($module, $instance->id, $COURSE->id);
			if ($cm->completion!=0) {
				$actions['activity_completion'] = get_string('activity_completion',
															 'block_usp_ews');
			}
		}
		$mform->addElement('select', 'config_action',
						   get_string('config_header_action', 'block_usp_ews'),
						   $actions );
		if(isset($this->_customdata['optionselected']))
			$mform->setDefault('config_action', $this->_customdata['action']);
		else
			$mform->setDefault('config_action',
						   $selected_module['defaultAction']);

		$mform->addHelpButton('config_action',
							  'what_actions_can_be_monitored', 'block_usp_ews');
		$mform->addElement('html', '</div>');
					
		// sending needed variables about the activity
		$mform->addElement('hidden', 'moduletype', $module);
		$mform->addElement('hidden', 'moduleid', $this->_customdata['module']);
		$mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
		$mform->addElement('hidden', 'modinstanceid', $instance->id);
		$mform->addElement('hidden', 'inst_id', $this->_customdata['inst_id']);
		
		$mform->setType('inst_id', PARAM_INT);
		$mform->setType('cid', PARAM_INT);
		$mform->setType('modinstanceid', PARAM_INT);
		$mform->setType('cmid', PARAM_INT);
		$mform->setType('moduletype', PARAM_RAW);
		$mform->setType('moduleid', PARAM_INT);

		if(isset($this->_customdata['optionselected'])){
			$mform->addElement('hidden', 'optionselected', $this->_customdata['optionselected']);
			$mform->setType('optionselected', PARAM_INT);
		}
		$this->add_action_buttons(true, 'Save'); // Cancel button works as expected
    }
}

