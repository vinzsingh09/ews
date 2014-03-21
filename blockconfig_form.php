<?php

require_once($CFG->dirroot.'/blocks/usp_ews/lib.php'); // usp_ews library file
	
require_once($CFG->libdir . '/formslib.php');

// database library file

class blockconfig_form extends moodleform {

	public function definition() {
        global $CFG, $COURSE, $DB, $OUTPUT;
		
		// Reload user info to get most current values
		$course = $DB->get_record('course', array('id' => $COURSE->id));
		
		$dbmanager = $DB->get_manager(); // loads ddl manager and xmldb classes
		
		$mform =& $this->_form;

        //$mform->addElement('hidden', 'sendto', '');
		$mform->addElement('hidden', 'cid', $COURSE->id);
		$mform->setType('cid', PARAM_INT);
		
		 $dbmanager = $DB->get_manager(); // loads ddl manager and xmldb classes
        $count = 0;
        $usingweeklyformat = $COURSE->format=='weeks' || $COURSE->format=='weekscss' ||
                             $COURSE->format=='weekcoll';

		if (!$course = $DB->get_record('course', array('id'=>$COURSE->id))) {
			print_error('invalidcourse');
		}

        // Start block specific section in config form
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Progress block instance title
        $mform->addElement('text', 'config_usp_ewsTitle',
                           get_string('config_title', 'block_usp_ews'));
        $mform->setDefault('config_usp_ewsTitle', $this->_customdata['title']);
        $mform->setType('config_usp_ewsTitle', PARAM_MULTILANG);
        $mform->addHelpButton('config_usp_ewsTitle', 'why_set_the_title', 'block_usp_ews');

		
		// Allow student view to be turned on/off on the block
        $mform->addElement('selectyesno', 'config_student_view', get_string('config_studentview', 'block_usp_ews'));
        $mform->setDefault('config_student_view', $this->_customdata['studentview']);
        $mform->addHelpButton('config_student_view', 'why_use_studentview', 'block_usp_ews');
		
        // Allow icons to be turned on/off on the block
        $mform->addElement('selectyesno', 'config_usp_ewsBarIcons',
                           get_string('config_icons', 'block_usp_ews').'&nbsp;'.
                           $OUTPUT->pix_icon('tick', '', 'block_usp_ews').'&nbsp;'.
                           $OUTPUT->pix_icon('cross', '', 'block_usp_ews'));
        $mform->setDefault('config_usp_ewsBarIcons', $this->_customdata['icon']);
        $mform->addHelpButton('config_usp_ewsBarIcons', 'why_use_icons', 'block_usp_ews');

        // Allow NOW to be turned on or off
        $mform->addElement('selectyesno', 'config_displayNow',
                           get_string('config_now', 'block_usp_ews').'&nbsp;'.
                           $OUTPUT->pix_icon('left', '', 'block_usp_ews').
                           get_string('now_indicator', 'block_usp_ews'));
        $mform->setDefault('config_displayNow', $this->_customdata['now']);
        $mform->addHelpButton('config_displayNow', 'why_display_now', 'block_usp_ews');
		
		// VS 15032013 Removed Performance Threshold (Min)
		// setting minimum satisfactory value
		//$action_satisfactory = array(10=>'10',20=>'20', 30=>'30',40=>'40', 50=>'50', 60=>'60' ,70=>'70', 80=>'80', 90=>'90', 100=>'100');
		
		$poptions = array('class' => 'usp_ews_info_message');
		$weightnote = HTML_WRITER::start_tag('p', $poptions) . get_string('config_weight_infor', 'block_usp_ews'). HTML_WRITER::end_tag('p');
		$mform->addElement('html', $weightnote);
		
		// setting minimum weighting value
		$scale_weight = array(0=>'0',5=>'5', 10=>'10', 15=>'15', 20=>'20', 25=>'25', 30=>'30', 35=>'35', 40=>'40', 45=>'45', 50=>'50', 55=>'55', 60=>'60', 65=>'65',70=>'70', 75=>'75', 80=>'80', 85=>'85', 90=>'90', 95=>'95', 100=>'100');

		// for minimum number of logins per week
		$scale_login = array(0=>'0', 1=>'1',  2=>'2', 3=>'3', 4=>'4', 5=>'5', 6=>'6', 7=>'7');

		// VS 15032013 Added completion weighting
		// by default it is set to 0
		// disabled if coursework 100- all weighting to coursework
		$mform->addElement('select', 'config_comp_weight',
						   get_string('config_header_comp_weight', 'block_usp_ews'),
						   $scale_weight);
		$mform->setDefault('config_comp_weight', $this->_customdata['com_weight']);
		$mform->addHelpButton('config_comp_weight', 'why_completion_weight', 'block_usp_ews');
		
		// VS 15032013 Added interaction weighting
		// by default it is set to 0
		// disabled if coursework 100- all weighting to coursework
		$mform->addElement('select', 'config_interact_weight',
						   get_string('config_header_interact_weight', 'block_usp_ews'),
						   $scale_weight);
		$mform->setDefault('config_interact_weight', $this->_customdata['interact_weight']);
		$mform->addHelpButton('config_interact_weight', 'why_interaction_weight', 'block_usp_ews');	
		
		// VS 15032013 Added login weighting
		// by default it is set to 0
		// disabled if coursework 100- all weighting to coursework
		$mform->addElement('select', 'config_login_weight',
						   get_string('config_header_login_weight', 'block_usp_ews'),
						   $scale_weight);
		$mform->setDefault('config_login_weight', $this->_customdata['login_weight']);
		$mform->addHelpButton('config_login_weight', 'why_login_weight', 'block_usp_ews');
		
		// VS 15032013 Added number of logins required by the students per week
		// by default it is set to 1	
		// used the determine login index
		$mform->addElement('select', 'config_min_login',
						   get_string('config_header_min_login', 'block_usp_ews'),
						   $scale_login); // 0 to 7
		$mform->setDefault('config_min_login', $this->_customdata['minlogin']);
		$mform->addHelpButton('config_min_login', 'why_login_per_week', 'block_usp_ews');

		
		// information about the colors in the traffic light
		$poptions = array('class' => 'usp_ews_info_message');
		$start_p = HTML_WRITER::start_tag('p');
		$end_p = HTML_WRITER::end_tag('p');
		$traficlight = HTML_WRITER::start_tag('div', $poptions)
			. $start_p . '<img class="usp_ews_traffic_light_pix" src="'. $OUTPUT->pix_url('redtraffic', 'block_usp_ews') . '" alt="" />'
			. get_string('config_traffic_red', 'block_usp_ews'). $end_p
			. $start_p
			. '<img class="usp_ews_traffic_light_pix" src="'. $OUTPUT->pix_url('orangetraffic', 'block_usp_ews') . '" alt="" />'
			. get_string('config_traffic_orange', 'block_usp_ews'). $end_p
			. $start_p
			. '<img class="usp_ews_traffic_light_pix" src="'. $OUTPUT->pix_url('greentraffic', 'block_usp_ews') . '" alt="" />'
			. get_string('config_traffic_green', 'block_usp_ews'). $end_p . HTML_WRITER::end_tag('div');
		
		$mform->addElement('html', $traficlight);
		
		$mform->addElement('hidden', 'inst_id', $this->_customdata['inst_id']);
		$mform->setType('inst_id', PARAM_INT);
		
		$this->add_action_buttons(true, 'Save'); // Cancel button works as expected
    }
}
