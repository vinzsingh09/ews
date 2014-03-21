<?php

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/blocks/usp_ews/lib.php');

class coursedate_form extends moodleform {

	public function definition() {
        global $CFG, $COURSE, $DB, $OUTPUT;
		
		$course = $DB->get_record('course', array('id' => $COURSE->id));
		
		$mform =& $this->_form;
       
		$mform->addElement('hidden', 'cid', $COURSE->id);
		$mform->setType('cid', PARAM_INT);
		
		$mform->addElement('hidden', 'inst_id', $this->_customdata['inst_id']);
		$mform->setType('inst_id', PARAM_INT);
		
		$mform->addElement('date_selector', 'startdate', get_string('startdate'));
        $mform->addHelpButton('startdate', 'startdate');
        $mform->setDefault('startdate', $course->startdate);
		
		$this->add_action_buttons(false, get_string('updatedate', 'block_usp_ews'));
    }
}