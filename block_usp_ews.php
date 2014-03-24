<?php
/**
 * usp_ews block definition
 *
 * @package    contrib
 * This is the main block shown on the course page
 *
 *
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/usp_ews/lib.php');

class block_usp_ews extends block_base {

	/**
     *
     * @configureddata - cofiguration setting of the block
     * @monitoreddata - monitored activities data
     * @courseid - current course id
     */
	private $configureddata;
	private $monitoreddata;
	private $courseid;

    /**
     * Sets the block title
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('config_default_title', 'block_usp_ews');
    }

    /**
     * Constrols the block title based on instance configuration
     *
     * @return bool
     */
    public function specialization() {
		global $DB, $COURSE;

		$this->courseid = $COURSE->id;
		$this->configureddata = $DB->get_record('usp_ews_config', array('courseid' => $this->courseid, 'ewsinstanceid'=>$this->instance->id));
		if(empty($this->configureddata))
			$this->title = get_string('config_default_title', 'block_usp_ews');
		else
			$this->title = format_string($this->configureddata->title);
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
	 *
	 * by default, multiple usp_ews block is not allowed
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array('course-view' => true);
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        // Access to settings needed
        global $USER, $CFG, $OUTPUT, $PAGE, $SESSION, $DB;

        // If content has already been generated, don't waste time generating it again
        if ($this->content !== null) {
            return $this->content;
        }

		$this->content = new stdClass;
        $this->content->text = '';

		// RT 12022013 - Remove this message when functionality is stable
		$strNote = '<p style="background:#D1EBEF;font-size:8pt; padding:3px; border:1px solid #338c9a;">This module is currently in <strong>experimental</strong> state. For any queries or errors regarding this module, please email singh_vn@usp.ac.fj</p>';
        $this->content->footer = $strNote ;

		$userid = $USER->id;

		$context = context_course::instance($this->courseid);
		
		$hasconfigpermission = has_capability('moodle/course:manageactivities', $context);

		// Check if any activities/resources have been created
		$modules = usp_ews_modules_in_use($this->courseid);

		if($hasconfigpermission) 
		{
			if(empty($modules)) {			
				$this->content->text .= get_string('no_events_config_message', 'block_usp_ews');
				return $this->content;
			}
			
			if(usp_ews_completion_used($this->courseid))
			{
				// displaying alert message if completion tracking also used
				$option_param = array('class'=> 'noticewarn');
				$this->content->text .= HTML_WRITER::start_tag('p', $option_param) 
						. get_string('completion_used', 'block_usp_ews', $this->title) 
						. HTML_WRITER::end_tag('p');
				
				return $this->content;
			}
			
			// if no activity is monitored then the block will be empty
			if(empty($this->configureddata) || $this->configureddata->ewsinstanceid != $this->instance->id){			
				$this->content->text .= get_string('notconfigured', 'block_usp_ews', $this->title);
				if($USER->editing) // in editing mode
				{
					$params = array('inst_id'=>$this->instance->id, 'cid' => $this->courseid);
					$urladd = new moodle_url('/blocks/usp_ews/editactivity.php', $params);
					$label = get_string('config_block', 'block_usp_ews');
					
					$this->content->text .= $OUTPUT->single_button($urladd, $label);
				}			
				return $this->content;
			}		
		}
		else
		{
			if(usp_ews_completion_used($this->courseid))
			{
				return $this->content;
			}
		}
		

		// monitored activity settings
		$this->monitoreddata = json_decode($this->configureddata->monitoreddata);

		// Check if activities/resources have been selected in config
		$events = usp_ews_event_information($this->courseid, $this->monitoreddata, $userid, $modules);

		// Nothing set up yet
		// Monitored events are main item and all crtical events should also be selected as monitored
		// therefore checking monitored events only
		if(empty($events))
		{
			if($hasconfigpermission)
			{
				$this->content->text .= get_string('no_events_message', 'block_usp_ews');

				if($USER->editing) // in editing mode
				{
					$params = array('inst_id' => $this->instance->id,'cid' => $this->courseid);
					$urladd = new moodle_url('/blocks/usp_ews/editactivity.php', $params);
					$label = get_string('selectitemstobeadded', 'block_usp_ews');
					
					$this->content->text .= $OUTPUT->single_button($urladd, $label);
				}
			}

			return $this->content;
		}

		// Check if critical activities/resources have been selected in config
		$events_critical = usp_ews_event_information($this->courseid, $this->monitoreddata, $userid, $modules, 'critical');

		// Dashboard for students
		// check if user is student
		// RT - TODO: use a better (more correct Moodle way) to determine this
		$is_student = $DB->record_exists('role_assignments', array('roleid' =>5, 'userid' => $userid, 'contextid' => $context->id));
		if($is_student && $this->configureddata->studentview)
		{
				/**
				 * Constantly used html elements/tags
				 * Moodle allows new way to write html elements/tags
				 * the repeated elements/tags stored in variable for easy using it
				 */
				$option_param = array('style'=> 'padding: 1px 0px; font-weight: bold;');
				$start_p = HTML_WRITER::start_tag('p', $option_param);
				$end_div = HTML_WRITER::end_tag('div');
				$end_p = HTML_WRITER::end_tag('p');
				$end_canvas = HTML_WRITER::end_tag('canvas');
				$start_b = HTML_WRITER::start_tag('b');
				$end_b = HTML_WRITER::end_tag('b');
				$start_span = HTML_WRITER::start_tag('span');
				$tooltipoptions = array('class' => 'usp_ews_tooltip');
				$start_span_tooltip = HTML_WRITER::start_tag('span', $tooltipoptions) ;
				$end_span = HTML_WRITER::end_tag('span');
				$boptionsindex = array('style'=>'margin:-10%; margin-left: 60%;');
				$start_b_index = HTML_WRITER::start_tag('b', $boptionsindex);
				// setting satisfactory tick and unsatisfactory cross in variable
				// because it is used in many places
				$sat = '<img src="'. $OUTPUT->pix_url('i/tick_green_small') .'" alt="" />';
				$unsat = '<img src="'. $OUTPUT->pix_url('i/cross_red_small') .'" alt="" />';

				/* calculation done to determine the overall flag on main dashboard
				 * total calculated taking:
				 * 45% of the completion rate
				 * 30% of the user's average interaction index
				 * 25% of the user's average login index
				**/
				// Default values of ratio to determine overall flag
				// by default the values are filled in the block setting configuration
				$weight_completion = ($this->configureddata->completionweight)/100;
				$weight_interection = ($this->configureddata->interactionweight)/100;
				$weight_login = ($this->configureddata->loginweight)/100;
				$min_login_week = $this->configureddata->minlogin;

				// Checks if a user has attempted/viewed/etc. monitoed activity/resource
				$attempts = usp_ews_get_attempts($modules, $events, $userid, $this->courseid);

				// setting session to prevent multiply popup
				$viewedpopup_cid_uid = 'usp_ewsviewedpopup'. $this->courseid . $userid;

				if(!isset($SESSION->usp_ewsviewedpopup)){
					$SESSION->usp_ewsviewedpopup = new stdClass();
				}

				if (!isset($SESSION->usp_ewsviewedpopup->$viewedpopup_cid_uid)){
					$SESSION->usp_ewsviewedpopup->$viewedpopup_cid_uid = false;
				}
				// if popup not seen already
				if($SESSION->usp_ewsviewedpopup->$viewedpopup_cid_uid == false){
					$contentpopup = usp_ews_warning_popup($events,  $attempts, $userid, $this->courseid);
					// popup content
					if ($contentpopup != '' || $contentpopup != null){
						$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/blocks/usp_ews/js/popup.js'));
						$this->content->text .= $contentpopup;
					}
				}

				$studentsindices = $DB->get_record('usp_ews_interaction', array('userid'=>$userid, 'courseid' => $this->courseid));

				$myinteract = 0;
				$mylogin = 0;
				// if not in the table
				// in case the students enrolled after first cron to mdl to usp_ews
				if(!empty($studentsindices)){
					// from interaction table, calculated index is retrieved
					$myinteract = $studentsindices->interactindex;
					// compares the login index with the minimum login set by the course coordinator
					$mylogin = number_format($studentsindices->lastsevenlogin/$min_login_week, EWS_DEFAULT_DECIMAL_POINT, '.', '');
				}
				// completion percentage
				$mycompletion = usp_ews_get_progess_percentage($events, $attempts);

				/* calculation done to determine the overall flag on main dashboard
				 * total calculated taking:
				 * 45% of the completion rate
				 * 30% of the user's average interaction index
				 * 25% of the user's average login index
				**/
				// VS-v2 pulling values from config tabledb
				$mystatus = ($weight_completion * (($mycompletion<0 ? 0 : $mycompletion)/100))
						  + ($weight_interection * ($myinteract<1 ? $myinteract : 1))
						  + ($weight_login * ($mylogin < 1 ? $mylogin : 1));

				// start of main block content's div
				$divoptions = array('class' => 'block_maincontent');
				$this->content->text .= HTML_WRITER::start_tag('div', $divoptions);

				// displaying flag according to the mystatus calculation
				// if its less than 50% of the status then its red else green
				// later the lecturer can choose different minimum percentage

				//$mystatus = $mystatus * 100;
				// if($mystatus != 1)
				if($mystatus <= EWS_DEFAULT_UNSATISFACTORY_INDEX)
					$flagpix = 'red_flag';
				else if($mystatus < EWS_DEFAULT_SATISFACTORY_INDEX)
					$flagpix ='orange_flag';
				else
					$flagpix = 'green_flag';

				// display the flag
				$this->content->text .= $start_p
					//. get_string('status_flag', 'block_usp_ews')
					. '<img id="usp_ews_status_flag" src="'. $OUTPUT->pix_url($flagpix, 'block_usp_ews') . '" alt="" />'
					. $end_p;

				// tooltip shows index when favourable
				$option_param1 = array();
				$convas = '';

				$spanoptions = array('class' => 'usp_ews_tooltip_space');

				// getting color for interaction traffic light and tooltip message
				$interactresult = usp_ews_find_color_index($myinteract);
				$colorinteract =  $interactresult['color'];
				$colorinteractactual =  $interactresult['coloractual'];
				$interactmessage = get_string('interaction'.$colorinteractactual , 'block_usp_ews');

				// displays interaction index and login index with a
				// convas according to index value if favourable or not
				$convas .= usp_ews_draw_circle_status(get_string('myCanvas_interact', 'block_usp_ews'), $colorinteract);

				$convasoption_param1 = array('id'=>get_string('myCanvas_interact', 'block_usp_ews'), 'class'=>'usp_ews_canvasspacing_first', 'width'=>'20', 'height'=>'20');
				$this->content->text .= $start_span_tooltip . $start_p
					. get_string('interact', 'block_usp_ews')
					. HTML_WRITER::start_tag('canvas', $convasoption_param1) . $end_canvas . $end_p
					. $start_span . $start_b_index . $end_b . $interactmessage . $end_span . $end_span;

				$convasoption_param2 = array('id'=>get_string('myCanvas_login', 'block_usp_ews'), 'class'=>'usp_ews_canvasspacing', 'width'=>'20', 'height'=>'20');

				// getting color for login traffic light and tooltip message
				if($mylogin < 1){
					$colorlogin = get_string('notAttempted_colour', 'block_usp_ews');
					$loginmessage = get_string('loginred', 'block_usp_ews');
				}else {
					$colorlogin = get_string('attempted_colour', 'block_usp_ews');
					$loginmessage = get_string('logingreen', 'block_usp_ews');
				}
				// canvas for login light
				$convas .= usp_ews_draw_circle_status(get_string('myCanvas_login', 'block_usp_ews'), $colorlogin);

				$this->content->text .= $start_span_tooltip . $start_p
						. get_string('login_index', 'block_usp_ews')  // . ($mylogin<1?$mylogin:1) . ' ' . ($mylogin<1 ? $unsat : $sat)
						. HTML_WRITER::start_tag('canvas', $convasoption_param2) . $end_canvas . $end_p
						. $start_span . $start_b_index . $end_b . $loginmessage . $end_span . $end_span;


				// completion percentage light and tooltip
				$convasoption_param3 = array('id'=>get_string('myCanvas_completion', 'block_usp_ews'), 'class'=>'usp_ews_canvasspacing_last', 'width'=>'20', 'height'=>'18');

				$colorcompletion = usp_ews_find_color_percentage($mycompletion/100);
				$convas .= usp_ews_draw_circle_status(get_string('myCanvas_completion', 'block_usp_ews'), $colorcompletion);

				// completion percentage bar
				$completion_bar = usp_ews_completion_progress($mycompletion, $colorcompletion);

				// tooltip showing the completion bar
				$this->content->text .= $start_span_tooltip
					. $start_p
					. get_string('completion_rate', 'block_usp_ews')  // . ($mylogin<1?$mylogin:1) . ' ' . ($mylogin<1 ? $unsat : $sat)
					. HTML_WRITER::start_tag('canvas', $convasoption_param3) . $end_canvas . $end_p
					. HTML_WRITER::start_tag('span', $spanoptions) . get_string('completion_percentage', 'block_usp_ews'). $mycompletion
					. get_string('percentage', 'block_usp_ews')
					. $start_b . $end_b
					. $completion_bar
					. $end_span . $end_span;

				// javascript for the canvas
				$this->content->text .= '<script type="text/javascript">' . $convas . '</script>';

				// after checking and unsetting critical activities which was not in monitored
				// monitored activity seected as critical activities in the configuration
				// bars are drawn for that
				if(!empty($events_critical)){
					// Checks if a user has attempted/viewed/etc. critical activity/resource
					$attempts_critical = usp_ews_get_attempts($modules, $events_critical, $userid, $this->courseid);
					// displays the title
					$this->content->text .= $start_p .
									get_string('config_header_critical', 'block_usp_ews') . $end_p;
					// adds the critical activities in the progress bar
					$this->content->text .= usp_ews_progress_bar($this->configureddata, $events_critical, $this->instance->id, $userid, $attempts_critical, true);

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

					$displaydate = !isset($this->configureddata->now) || $this->configureddata->now==1;
					$arguments = array($CFG->wwwroot, array_keys($modules), $displaydate);
					$this->page->requires->js_init_call('M.block_usp_ews.init', $arguments, false, $jsmodule);
				}
				// end of the main div
				$this->content->text .= $end_div;

				if(has_capability('block/usp_ews:viewdetail', $context))
				{
					$params = array('inst_id' => $this->instance->id, 'cid' => $this->courseid, 'mode' => 'mycom');
					$url = new moodle_url('/blocks/usp_ews/mydashboard.php', $params);
					$this->content->text .= $OUTPUT->single_button($url, get_string('mydashboard', 'block_usp_ews'));
				}
		}
		

		// if user has access to view detail
		// then button is created to show detail
		if($hasconfigpermission || has_capability('block/usp_ews:overview', $context))
		{
			if(usp_ews_completion_used($this->courseid)){
				// display alert message of using completion and EWS together
				$option_param = array('style'=> 'background:#FF9999;font-size:8pt; padding:1px; border:1px solid #C0311E;');
				$this->content->text .= HTML_WRITER::start_tag('p', $option_param) . get_string('completion_used', 'block_usp_ews') . HTML_WRITER::end_tag('p');

			}else{

				if(usp_ews_check_activity_setting($this->monitoreddata, $this->courseid, $modules)){
					$option_param = array('style'=> 'background:#FF9999;font-size:8pt; padding:1px; border:1px solid #C0311E;');
					$this->content->text .= HTML_WRITER::start_tag('p', $option_param) . get_string('activity_setting_infor', 'block_usp_ews') . HTML_WRITER::end_tag('p');
				}

				$params = array('inst_id' => $this->instance->id,'cid' => $this->courseid);
				$url = new moodle_url('/blocks/usp_ews/overviewallstudents.php', $params);

				//adding push
				if($USER->editing){
					$urledit = new moodle_url('/blocks/usp_ews/editactivity.php', $params);
					$this->content->text .= $OUTPUT->single_button($urledit, get_string('config_block', 'block_usp_ews'));
				}

				$this->content->text .= get_string('viewoverviewdetails', 'block_usp_ews');
				$this->content->text .= $OUTPUT->single_button($url, get_string('overviewlink', 'block_usp_ews'));
			}
		}

		// returns content to the main course page
        return $this->content;
    }

     /**
     * cron - is used to push the data to the backendhe cache
     * duration set to 0 in order to force the retrieval of the item and
     * refresh the cache
     *
     * @return boolean true if all feeds were retrieved succesfully
     */
    function cron() {
        global $CFG, $DB;

        // We are going to measure execution times
        $starttime =  microtime();

        // And we have one initial $status
        $status = true;

		// cron script to clean config table and push the log
		require_once($CFG->dirroot.'/blocks/usp_ews/push/cron.php'); // usp_ews library file

        // Show times
        mtrace('time taken: ' . microtime_diff($starttime, microtime()) . ' seconds)');

        // And return $status
        return $status;
    }
}