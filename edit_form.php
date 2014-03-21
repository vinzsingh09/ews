<?php

/**
 * usp_ews block configuration form definition
 *
 * @package    contrib
 * @subpackage block_usp_ews
 */

require_once(dirname(__FILE__) . '/../../config.php');

/**
 * Simple clock block config form class
 */
class block_usp_ews_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $OUTPUT;
       
	   	$inst_id       = required_param('bui_editid', PARAM_INT); 
	    $id       = required_param('id', PARAM_INT); 
		
		if (!$course = $DB->get_record('course', array('id'=>$id))) {
			print_error('invalidcourse');
		}

        // Start block specific section in config form
        $mform->addElement('header', 'configheader', get_string('config_monitored', 'block_usp_ews') . ' & ' .get_string('blocksettings', 'block'));
		
		$monitored = $DB->get_record('usp_ews_config', array('courseid' => $course->id, 'ewsinstanceid'=>$inst_id));
		// if empty means it's first time so let them agree pre-conditions
		if(empty($monitored)){
			$params = array('inst_id' => $inst_id,'cid' => $course->id);
			$urlactivity = new moodle_url('/blocks/usp_ews/confirm_precondition.php', $params);
		}else{
			$params = array('inst_id' => $inst_id,'cid' => $course->id);
			$urlactivity = new moodle_url('/blocks/usp_ews/editactivity.php', $params);
		}
		$courseurl = new moodle_url('/course/view.php', array('id'=> $course->id));
		
		// links to the block and activity configuration
		$tabs = '<ul class="usp_ews_tabrow">
			<li>
				<a href="'. $urlactivity .'"> ' . get_string('activity_config', 'block_usp_ews') .
		   '</li>
			<li><a href="' . $courseurl. '">' . get_string('course_page', 'block_usp_ews'). '</a></li>
		</ul>';

		$mform->addElement('html', $tabs);

	}
				
}
