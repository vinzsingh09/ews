<?php

/**
 * Student Dashboard block common configuration and helper functions
 *
 * Instructions for adding new modules so they can be monitored
 * ================================================================================================
 * Activies that can be monitored (all resources are treated together) are defined in the $MODULES
 * array.
 *
 * Modules can be added with:
 *  - defaultTime (deadline from module if applicable),
 *  - actions (array if action-query pairs) and
 *  - defaultAction (selected by default in config page and needed for backwards compatability)
 *
 * The module name needs to be the same as the table name for module in the database.
 *
 * Queries need to produce at least one result for completeness to go green, ie there is a record
 * in the DB that indicates the user's completion.
 *
 * Queries may include the following placeholders that are substituted when the query is run. Note
 * that each placeholder can only be used once in each query.
 *  :eventid (the id of the activity in the DB table that relates to it, eg., an assignment id)
 *  :cmid (the course module id that identifies the instance of the module within the course),
 *  :userid (the current user's id) and
 *  :courseid (the current course id)
 *
 * When you add a new module, you need to add a translation for it in the lang files.
 * If you add new action names, you need to add a translation for these in the lang files.
 *
 * Note: Activity completion is automatically available when enabled (sitewide setting) and set for
 * an activity.
 *
 * If you have added a new module to this array and think other's may benefit from the query you
 * have created, please share it by sending it to michaeld@moodle.com
 * ================================================================================================
 *
 * @package    contrib
 * @subpackage block_ews
 */

 /*
define('EWS_COMPLETION_RATIO_NO_CW', 0.45);
define('EWS_INTERACT_RATIO_NO_CW', 0.3);
define('EWS_LOGIN_RATIO_NO_CW', 0.25);
define('EWS_COURSEWORK_RATIO', 1);
define('EWS_COMPLETION_RATIO', 0);
define('EWS_INTERACT_RATIO', 0);
define('EWS_LOGIN_RATIO', 0);
*/

define('EWS_DEFAULT_CW_WEIGHT', 0);
define('EWS_DEFAULT_COMPLETION_WEIGHT', 40);
define('EWS_DEFAULT_INTERACT_WEIGHT', 30);
define('EWS_DEFAULT_LOGIN_WEIGHT', 30);
define('EWS_DEFAULT_LOGIN_PER_WEEK', 1);
define('EWS_DEFAULT_NOW', 0);
define('EWS_DEFAULT_ICON', 0);
define('EWS_DEFAULT_LAST_SEVEN_LOGIN', 7);

define('EWS_DEFAULT_UNSATISFACTORY', 40);
define('EWS_DEFAULT_AMBER', 59);
define('EWS_DEFAULT_SATISFACTORY', 60);

define('EWS_DEFAULT_UNSATISFACTORY_INDEX', 0.40);
define('EWS_DEFAULT_SATISFACTORY_INDEX', 0.60);

define('EWS_DEFAULT_DECIMAL_POINT', 2);

// constant for cron
define('EWS_DEFAULT_MAX_CRON_TIME', 600);
define('EWS_DEFAULT_MAX_RECORD', 1000); // estimated for 50 courses data
define('EWS_DEFAULT_MAX_RECORD_PER_SEC', 3000);
define('EWS_DEFAULT_MAX_INSERT_RECORD', 500); // inserting in batch
define('EWS_DEFAULT_LASTLOGID', 0);
define('EWS_DEFAULT_PROCESSNEW', 1);
 
/**
 * ** Provides information about monitorable modules
 *
 * @return array
 */
function usp_ews_get_monitorable_modules() {
    global $DB;

    return array(
        'assign' => array(
            'defaultTime' => 'duedate',
            'actions' => array(
                'submitted'    => "SELECT id
                                     FROM {assign_submission}
                                    WHERE assignment = :eventid
                                      AND userid = :userid
                                      AND status = 'submitted'",
                'marked'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assign'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND g.finalgrade IS NOT NULL",
                'passed'       => "SELECT g.finalgrade, i.gradepass
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assign'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid"
            ),
            'defaultAction' => 'submitted'
        ),
        'assignment' => array(
            'defaultTime' => 'timedue',
            'actions' => array(
                'submitted'    => "SELECT id
                                     FROM {assignment_submissions}
                                    WHERE assignment = :eventid
                                      AND userid = :userid
                                      AND (
                                          numfiles >= 1
                                          OR {$DB->sql_compare_text('data2')} <> ''
                                      )",
                'marked'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assignment'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND g.finalgrade IS NOT NULL",
                'passed'       => "SELECT g.finalgrade, i.gradepass
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'assignment'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid"
            ),
            'defaultAction' => 'submitted'
        ),
        'bigbluebuttonbn' => array(
            'defaultTime' => 'timedue',
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'bigbluebuttonbn'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'recordingsbn' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'recordingsbn'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'book' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'book'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'certificate' => array(
            'actions' => array(
                'awarded'      => "SELECT id
                                     FROM {certificate_issues}
                                    WHERE certificateid = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'awarded'
        ),
        'chat' => array(
            'actions' => array(
                'posted_to'    => "SELECT id
                                     FROM {chat_messages}
                                    WHERE chatid = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'posted_to'
        ),
        'choice' => array(
            'defaultTime' => 'timeclose',
            'actions' => array(
                'answered'     => "SELECT id
                                     FROM {choice_answers}
                                    WHERE choiceid = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'answered'
        ),
        'data' => array(
            'defaultTime' => 'timeviewto',
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'data'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'feedback' => array(
            'defaultTime' => 'timeclose',
            'actions' => array(
                'responded_to' => "SELECT id
                                     FROM {feedback_completed}
                                    WHERE feedback = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'responded_to'
        ),
        'resource' => array(  // AKA file.
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'resource'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'flashcardtrainer' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'flashcardtrainer'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'folder' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'folder'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'forum' => array(
            'defaultTime' => 'assesstimefinish',
            'actions' => array(
				'viewed'      => "SELECT id
									 FROM {log}
									WHERE course = :courseid
									  AND module = 'forum'
									  AND action = 'view forum' 
									  AND cmid = :cmid
									  AND userid = :userid",
                'posted_to'    => "SELECT id
                                     FROM {forum_posts}
                                    WHERE userid = :userid AND discussion IN (
                                          SELECT id
                                            FROM {forum_discussions}
                                           WHERE forum = :eventid
                                    )"
            ),
            'defaultAction' => 'posted_to'
        ),
        'glossary' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'glossary'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'hotpot' => array(
            'defaultTime' => 'timeclose',
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {hotpot_attempts}
                                    WHERE hotpotid = :eventid
                                      AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {hotpot_attempts}
                                    WHERE hotpotid = :eventid
                                      AND userid = :userid
                                      AND timefinish <> 0",
            ),
            'defaultAction' => 'finished'
        ),
        'imscp' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'imscp'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'journal' => array(
            'actions' => array(
                'posted_to'    => "SELECT id
                                     FROM {journal_entries}
                                    WHERE journal = :eventid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'posted_to'
        ),
        'lesson' => array(
            'defaultTime' => 'deadline',
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {lesson_attempts}
                                    WHERE lessonid = :eventid
                                      AND userid = :userid
                                UNION ALL
                                   SELECT id
                                     FROM {lesson_branch}
                                    WHERE lessonid = :eventid1
                                      AND userid = :userid1",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'lesson'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND g.finalgrade IS NOT NULL"
            ),
            'defaultAction' => 'attempted'
        ),
        'page' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'page'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'questionnaire' => array(
            'defaultTime' => 'closedate',
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {questionnaire_attempts}
                                    WHERE qid = :eventid
                                      AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {questionnaire_response}
                                    WHERE complete = 'y'
                                      AND username = :userid
                                      AND survey_id = :eventid",
            ),
            'defaultAction' => 'finished'
        ),
        'quiz' => array(
            'defaultTime' => 'timeclose',
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {quiz_attempts}
                                    WHERE quiz = :eventid
                                      AND userid = :userid",
                'finished'     => "SELECT id
                                     FROM {quiz_attempts}
                                    WHERE quiz = :eventid
                                      AND userid = :userid
                                      AND timefinish <> 0",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'quiz'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND g.finalgrade IS NOT NULL",
                'passed'       => "SELECT g.finalgrade, i.gradepass
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'quiz'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid"
            ),
            'defaultAction' => 'finished'
        ),
        'scorm' => array(
            'actions' => array(
                'attempted'    => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid",
                'completed'    => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid
                                      AND element = 'cmi.core.lesson_status'
                                      AND {$DB->sql_compare_text('value')} = 'completed'",
                'passedscorm'  => "SELECT id
                                     FROM {scorm_scoes_track}
                                    WHERE scormid = :eventid
                                      AND userid = :userid
                                      AND element = 'cmi.core.lesson_status'
                                      AND {$DB->sql_compare_text('value')} = 'passed'"
            ),
            'defaultAction' => 'attempted'
        ),
        'turnitintool' => array(
            'defaultTime' => 'defaultdtdue',
            'actions' => array(
                'submitted'    => "SELECT id
                                     FROM {turnitintool_submissions}
                                    WHERE turnitintoolid = :eventid
                                      AND userid = :userid
                                      AND submission_score IS NOT NULL"
            ),
            'defaultAction' => 'submitted'
        ),
        'url' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'url'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'wiki' => array(
            'actions' => array(
                'viewed'       => "SELECT id
                                     FROM {log}
                                    WHERE course = :courseid
                                      AND module = 'wiki'
                                      AND action = 'view'
                                      AND cmid = :cmid
                                      AND userid = :userid"
            ),
            'defaultAction' => 'viewed'
        ),
        'workshop' => array(
            'defaultTime' => 'assessmentend',
            'actions' => array(
                'submitted'    => "SELECT id
                                     FROM {workshop_submissions}
                                    WHERE workshopid = :eventid
                                      AND authorid = :userid",
                'assessed'     => "SELECT s.id
                                     FROM {workshop_assessments} a, {workshop_submissions} s
                                    WHERE s.workshopid = :eventid
                                      AND s.id = a.submissionid
                                      AND a.reviewerid = :userid
                                      AND a.grade IS NOT NULL",
                'graded'       => "SELECT g.rawgrade
                                     FROM {grade_grades} g, {grade_items} i
                                    WHERE i.itemmodule = 'workshop'
                                      AND i.iteminstance = :eventid
                                      AND i.id = g.itemid
                                      AND g.userid = :userid
                                      AND g.finalgrade IS NOT NULL"
            ),
            'defaultAction' => 'submitted'
        ),
    );
}

/**
 * ** Provides information about monitorable and active modules at site level
 *
 * @return array
 * used in edit_form.php
 */
function usp_ews_get_active_monitorable_modules() {
    global $DB;

	$fields = 'name';
	$active_site_modules = array();
	
	// get records of modules thoses are activve in site level
	$active_site_modules_records = $DB->get_records('modules', array('visible'=>'1'), '', $fields);
	
	foreach ($active_site_modules_records as $usp_ews_module=>$details) {
		$active_site_modules[] = $details->name;
	}
	
	$monitored_modules = usp_ews_get_monitorable_modules();

	foreach ($monitored_modules as $modules=>$detail) {
		if(!in_array($modules, $active_site_modules))	{
			unset($monitored_modules[$modules]);
		}
	}

	return $monitored_modules;
}


/**
 * ** Checks if a variable has a value and returns a default value if it doesn't
 *
 * @param mixed $var The variable to check
 * @param mixed $def Default value if $var is not set
 * @return string
 */
function usp_ews_default_value(&$var, $def = null) {
    return isset($var)?$var:$def;
}

/**
 * ** Filters the modules list to those installed in Moodle instance and used in current course
 *
 * @return array
 */
function usp_ews_modules_in_use($courseid) {
    global $DB;
	
    $dbmanager = $DB->get_manager(); // used to check if tables exist
    $modules = usp_ews_get_active_monitorable_modules();
    $modulesinuse = array();

    foreach ($modules as $module => $details) {
        if (
            $dbmanager->table_exists($module) &&
            $DB->record_exists($module, array('course'=>$courseid))
        ) {
            $modulesinuse[$module] = $details;
        }
    }
    return $modulesinuse;
}

/**
 * ** Gets event information about modules monitored by an instance of a Progress Bar block
 *
 * @param stdClass $courseid  The course id
 * @param json    $configmonitoreddata The configured activity
 * @param string   $typeactivityconfig to check if its for monitored or critical activities
 * @return mixed   returns array of visible events monitored,
 *                 empty array if none of the events are visible,
 *                 null if all events are configured to "no" monitoring and
 *                 0 if events are available but no cofig is set
 */
function usp_ews_event_information($courseid, $configmonitoreddata, $userid, $modules, $typeactivityconfig="monitor") {
    global $DB, $USER;
    //$dbmanager = $DB->get_manager(); // used to check if tables exist
    $events = array();
    $numevents = 0;
	
	if (empty($configmonitoreddata)) {
        return null;
    }

	$modinfo = get_fast_modinfo($courseid, $userid);

	if($typeactivityconfig == "monitor"){
		foreach($configmonitoreddata as $monitor){
				$mod = $modinfo->cms[$monitor->cmid];
				if($mod->visible == 1 && $mod->uservisible == 1){

					$expected = $monitor->expected;
					if($monitor->locked == 1){
				
						if (array_key_exists('defaultTime', $modules[$monitor->module])) {
							$fields = $modules[$monitor->module]['defaultTime'].' as due';
							$record = $DB->get_record($monitor->module, array('id'=>$monitor->instanceid, 'course'=>$courseid), $fields);

							if($record->due != 0){
								$expected = $record->due;
							}
						}
					}
					$numevents ++;
					$events[] = array(
						'expected'=>$expected,
						'type'=>$monitor->module,
						'id'=>$monitor->instanceid,
						'name'=>$mod->name,
						'cmid'=>$monitor->cmid,
						'action'=>$monitor->action
					);
				 }				
		}	
	}else{
		foreach($configmonitoreddata as $critical){
			$mod = $modinfo->cms[$critical->cmid];
			if($critical->critical == 1 && $mod->visible == 1 && $mod->uservisible == 1){
				
					$expected = $critical->expected;
					if($critical->locked == 1){
				
						if (array_key_exists('defaultTime', $modules[$critical->module])) {
							$fields = $modules[$critical->module]['defaultTime'].' as due';
							$record = $DB->get_record($critical->module, array('id'=>$critical->instanceid, 'course'=>$courseid), $fields);

							if($record->due != 0){
								$expected = $record->due;
							}
						}
					}
				
				$numevents ++;
				$events[] = array(
					'expected'=>$expected,
					'type'=>$critical->module,
					'id'=>$critical->instanceid,
					'name'=>$mod->name,
					'cmid'=>$critical->cmid,
					'action'=>$critical->action
				);
			 }				
		}	
	}

	if ($numevents==0) {
        return null;
    }

    // Sort by first value in each element, which is time due
    sort($events);

    return $events;
}

/**
 * ** Checked if a user has attempted/viewed/etc. an activity/resource
 *
 * @param array    $modules The modules used in the course
 * @param stdClass $config  The blocks configuration settings
 * @param array    $events  The possible events that can occur for modules
 * @param int      $userid  The user's id
 * @return array   an describing the user's attempts based on module+instance identifiers
 */
function usp_ews_get_attempts($modules, $events, $userid, $courseid) {
    global $DB;
    $attempts = array();

    foreach ($events as $event) {
        $module = $modules[$event['type']];
        $uniqueid = $event['type'].$event['id'];
        $parameters = array('courseid' => $courseid, 'courseid1' => $courseid,
                            'userid' => $userid, 'userid1' => $userid,
                            'eventid' => $event['id'], 'eventid1' => $event['id'],
                            'cmid' => $event['cmid'], 'cmid1' => $event['cmid'],
                      );
        // Check for passing grades as unattempted, passed or failed
        if ($event['action'] && $event['action']== 'passed')
        {
            $query =  $module['actions'][$event['action']];
            $graderesult = $DB->get_record_sql($query, $parameters);
            if (!$graderesult) {
                $attempts[$uniqueid] = false;
            } else {
                $attempts[$uniqueid] = $graderesult->finalgrade >= $graderesult->gradepass ? true : 'failed';
            }
		}else{	
			// If activity completion is used, check completions table
			if(isset($event['action']) && $event['action']=='activity_completion') {
				$query = 'SELECT id
							FROM {course_modules_completion}
						   WHERE userid = :userid
							 AND coursemoduleid = :cmid
							 AND completionstate = 1';
			}        
			else // Determine the set action and develop a query
			{
				$action = isset($event['action']) ? $event['action'] : $module['defaultAction'];
				$query =  $module['actions'][$action];
			}
			

			 // Check if the user has attempted the module
			$attempts[$uniqueid] = $DB->record_exists_sql($query, $parameters) ? true : false;
		}
	
	}
    return $attempts;
}

/**
 * ** Draws a progress bar
 *
 * @param array    $modules  The modules used in the course
 * @param stdClass $config   The blocks configuration settings
 * @param array    $events   The possible events that can occur for modules
 * @param int      $userid   The user's id
 * @param int      courseid  The course id, only 1 usp_ews block per course is allowed
 * @param array    $attempts The user's attempts on course activities
 * @param bool     $simple   Controls whether instructions are shown below a progress bar
 * @param string   $locationprogressbar  where progress bar is placed to control css for different locations
 * @return string  $content  html content for the progress bar, form of table is return
 */
function usp_ews_progress_bar($config, $events, $instance, $userid, $attempts, $simple = false, $locateprogressbar = 'mainblock') {
    global $OUTPUT, $CFG;

    $now = time();
    $numevents = count($events);
    $dateformat = get_string('date_format', 'block_usp_ews');
    $tableoptions = array('class' => 'usp_ewsBarDashboardTable');
    $content = HTML_WRITER::start_tag('table', $tableoptions);

	    // Place now arrow
    if ($config->now==1 && !$simple) {

        // Find where to put now arrow
        $nowpos = 0;
        while ($nowpos<$numevents && $now>$events[$nowpos]['expected']) {
            $nowpos++;
        }

        $content .= HTML_WRITER::start_tag('tr');
        $nowstring = get_string('now_indicator', 'block_usp_ews');
        if ($nowpos<$numevents/2) {
            for ($i=0; $i<$nowpos; $i++) {
                $content .= HTML_WRITER::tag('td', '&nbsp;', array('class' => 'usp_ewsBarHeader'));
            }
            $celloptions = array('colspan' => $numevents-$nowpos,
                                 'class' => 'usp_ewsBarHeader',
                                 'style' => 'text-align:left;');
            $content .= HTML_WRITER::start_tag('td', $celloptions);
            $content .= $OUTPUT->pix_icon('left', $nowstring, 'block_usp_ews');
            $content .= $nowstring;
            $content .= HTML_WRITER::end_tag('td');
        }
        else {
            $celloptions = array('colspan' => $nowpos,
                                 'class' => 'usp_ewsBarHeader',
                                 'style' => 'text-align:right;');
            $content .= HTML_WRITER::start_tag('td', $celloptions);
            $content .= $nowstring;
            $content .= $OUTPUT->pix_icon('right', $nowstring, 'block_usp_ews');
            $content .= HTML_WRITER::end_tag('td');
            for ($i=$nowpos; $i<$numevents; $i++) {
                $content .= HTML_WRITER::tag('td', '&nbsp;', array('class' => 'usp_ewsBarHeader'));
            }
        }
        $content .= HTML_WRITER::end_tag('tr');
    }
    // Start progress bar
    $width = 100/$numevents;
    $content .= HTML_WRITER::start_tag('tr');
    foreach ($events as $event) {
        $attempted = $attempts[$event['type'].$event['id']];

        // A cell in the progress bar
		// uses module.js to show the cell information on hover
        $celloptions = array(
            'class' => 'usp_ewsBarCell',
           // 'width' => $width.'%',
			'title' => $event['name'],
            'onclick' => 'document.location=\''.$CFG->wwwroot.'/mod/'.$event['type'].
                '/view.php?id='.$event['cmid'].'\';',
            'onmouseover' => 'M.block_usp_ews.showdetails.showInfo(\''.$event['type'].'\', \''.
                get_string($event['type'], 'block_usp_ews').'\', \''.$event['cmid'].'\', \''.
                addslashes($event['name']).'\', \''.
                get_string($event['action'], 'block_usp_ews').
                '\', \''. userdate($event['expected'], $dateformat, $CFG->timezone) .'\', \''.
                $instance.'\', \''.$userid.'\', \''.($attempted?'tick':'cross').'\');',
			//'onmouseout' => 'M.block_usp_ews.showdetails.cancelInfo(\'' .$instance.'\', \''.$userid .'\');',
            'style' => 'width:' . $width . '%;background-color:');
		// green color - attempted activity
        if ($attempted) {
            $celloptions['style'] .= get_string('attempted_colour', 'block_usp_ews').';';
            $cellcontent = $OUTPUT->pix_icon(isset($config->icon) && $config->icon==1 ?'tickconfig' : 'blank', '', 'block_usp_ews');
        }
		// red color - not attempted activity
        else if ($event['expected'] < $now) {
            $celloptions['style'] .= get_string('notAttempted_colour', 'block_usp_ews').';';
            $cellcontent = $OUTPUT->pix_icon(
                               isset($config->icon) && $config->icon==1 ?
                               'crossconfig':'blank', '', 'block_usp_ews');
        }
		// blue color - future activity
        else {
            $celloptions['style'] .= get_string('futureNotAttempted_colour', 'block_usp_ews').';';
            $cellcontent = $OUTPUT->pix_icon('blank', '', 'block_usp_ews');
        }
        $content .= HTML_WRITER::tag('td', $cellcontent, $celloptions);
    }
    $content .= HTML_WRITER::end_tag('tr');
    $content .= HTML_WRITER::end_tag('table');

    // Add the info box below the table
	$divoptions = array('class' => "usp_ewsEventInfo  usp_ewsEventInfo_$locateprogressbar",
                        'id'=>'usp_ewsBarInfo'.$instance.'user'.$userid); //'style'=> 'height:40px;'

	$content .= HTML_WRITER::start_tag('div', $divoptions);

	$content .= get_string('mouse_over_prompt', 'block_usp_ews');
    $content .= HTML_WRITER::end_tag('div');

    return $content;
}


// Draw traffic light
function usp_ews_draw_circle_status($id, $color){

	$circle = "var c=document.getElementById('$id');
			var ctx=c.getContext('2d');
			ctx.fillStyle='$color';
			ctx.beginPath();
			ctx.arc(9,8,7,0,2*Math.PI);
			ctx.strokeStyle = '#000';
			ctx.lineWidth = 2;
			ctx.stroke();
			ctx.fill();	
			";
	return $circle;
}



/**
 * ** Calculates an completion percentage
 *
 * @param array $events            The possible events that can occur for modules
 * @param array $attempts          The user's attempts on course activities
 * @return int  $progressvalue*100 progress percentage 
 */
function usp_ews_get_progess_percentage($events, $attempts) {
    $attemptcount = 0;

	$count_events = count($events);
    foreach($events as $event) {
        if($attempts[$event['type'].$event['id']] == 1) {
            $attemptcount++;
        }
    }
	$progressvalue = ($attemptcount==0  || $count_events==0) ? 0 : $attemptcount / $count_events;

    return (int)($progressvalue * 100);
}


/**
 * ** Draws a progress bar for coursework and completion percentage
 *
 * @param int      $iCompletion percentage of the work completed
 *				    this are either completion or coursework
 * @return string  $contenthtml  html content for the progress bar, form of table is return
 */
function usp_ews_completion_progress($iCompletion, $colour='') {
	// total width block - completed percentage
	$width = 100 - $iCompletion;
	
	if($colour == ''){
		$colour = usp_ews_find_color_percentage($iCompletion);
	}
	// html content
	// creating the bar in table 
	$contenthtml = '';
	
	// inline css for the table properties
	$tableoption = array('class' => 'usp_ewsBarDashboardTable');
	$contenthtml .= HTML_WRITER::start_tag('table', $tableoption); // <table>

	$contenthtml .= HTML_WRITER::start_tag('tr'); // <tr>
	// <td> properties
	$tdoption = array('class' => 'usp_ewsotherCell',
						'title' => "$iCompletion%",						
						'style' => 'background:' . $colour .'; width:' . $iCompletion . '%;');
	$contenthtml .= HTML_WRITER::start_tag('td', $tdoption); // <td>
	$contenthtml .= HTML_WRITER::end_tag('td'); // </td>
	
	$tdoption1 = array( 'class' => 'usp_ewsotherCell',						
						'style' => 'background:#D3D3D3; width:' . $width . '%;');
	$contenthtml .= HTML_WRITER::start_tag('td', $tdoption1); // <td>	 				
	$contenthtml .= HTML_WRITER::end_tag('td'); // </td>

	$contenthtml .= HTML_WRITER::end_tag('tr'); // </tr>
	$contenthtml .= HTML_WRITER::end_tag('table'); // <table>

	// returns the content
	return $contenthtml;
}

/**
 * Removing or deleting the monitored activity which is removed from the list
 * done by the cron 
 */
function usp_ews_delete_monitored_activity($monitoreddatarecord, $monitorededitcmid){
	
	$count = count($monitoreddatarecord);
	for($i=0; $i < $count; $i++){
		if($monitoreddatarecord[$i]->cmid == $monitorededitcmid){
		
			if($i == 0){
				break;
			}else{
				$temp = array();
				$temp = $monitoreddatarecord[0];
				$monitoreddatarecord[0] = $monitoreddatarecord[$i];
				$monitoreddatarecord[$i] = $temp;
			
			}
		}
	}

	$monitorednewrecord = array_shift($monitoreddatarecord);
	
	// note we returning monitoreddatarecord since thats the updated array
	return $monitoreddatarecord;

}

/**
 *  Check if completion tracking is also used in the course
 *  if its used return true
 *
 * @param int 	   $course  courseid
 * @return bool  returns if completion tracking used
 */
 
function usp_ews_completion_used($course){

	global $DB;
	$fields = 'enablecompletion';
	$record = $DB->get_record('course', array('id'=>$course), $fields);
	
	if($record->enablecompletion == 1)
		return true;
	else
		return false;
	
}

/**
 *  Check if there are any changes in the activity setting
 *  if its used return true
 *
 * @param int 	   $course  courseid
 * @return bool  returns if any setting needs attention
 */
 
function usp_ews_check_activity_setting($configmonitoreddata, $courseid, $modules){

	global $DB;
	
	$dbmanager = $DB->get_manager(); // loads ddl manager and xmldb classes


	if (empty($configmonitoreddata)) {
        return false;
    }

	$modinfo = get_array_of_activities($courseid);

	foreach($configmonitoreddata as $monitor){
		$mod = $modinfo[$monitor->cmid];
		// if hidden then no sense giving unnecessary attention
		if($mod->visible == 1){
			// only if the activity is notlocked, otherwise that's unnecessary as in the manual meaning of lock is specified
			if($monitor->locked == 0){
		
				if (array_key_exists('defaultTime', $modules[$monitor->module])) {
					$fields = $modules[$monitor->module]['defaultTime'].' as due';
					$record = $DB->get_record($monitor->module, array('id'=>$monitor->instanceid, 'course'=>$courseid), $fields);

					if($record->due != $monitor->duedate && $record->due > time()){
						return true;
					}
				}
			}
		 }				
	}

	return false;
}


/**
 *  get ids of activities where there are any changes in the activity setting
 *  if there are any return array of ids
 *
 * @param int 	   $course  courseid
 * @return array   returns  array of ids
 */
 
function usp_ews_changed_setting_ids($configmonitoreddata, $modinfo, $courseid, $modules){

	global $DB;
	
	$dbmanager = $DB->get_manager(); // loads ddl manager and xmldb classes


	if (empty($configmonitoreddata)) {
        return null;
    }

	$activity_ids = array();

	foreach($configmonitoreddata as $monitor){
		$mod = $modinfo[$monitor->cmid];
		// if hidden then no sense giving unnecessary attention
		if($mod->visible == 1){
			// only if the activity is notlocked, otherwise that's unnecessary as in the manual meaning of lock is specified
			if($monitor->locked == 0){
		
				if (array_key_exists('defaultTime', $modules[$monitor->module])) {
					$fields = $modules[$monitor->module]['defaultTime'].' as due';
					$record = $DB->get_record($monitor->module, array('id'=>$monitor->instanceid, 'course'=>$courseid), $fields);

					if($record->due != $monitor->duedate && $record->due > time()){
						$activity_ids[] = $monitor->cmid;
					}
				}
			}
		}				
	}

	return $activity_ids;
}

/**
 * ** Formats the grade to 1 decimal place
 *
 * @param double   $grade  finalgrade that is from the databse
 * @param int 	   $decimalpoints  set to 1dp
 * @return double  returns formated grade
 */
function usp_ews_format_number($grade, $decimalpoints=1) {
    if (is_null($grade) || $grade == "") {
        return '-';
    }
	else {
        return number_format($grade, $decimalpoints, '.', '');
    }
}

/**
 * ** To display the popup alert on the main page
 *	  display activities that are configured by the cordinator
 *    and activities that are due for past 7days and activities due in
 *    within next 7days..
 *
 * @param stdClass $config   The blocks configuration settings 
 * @param array    $events   The possible events that can occur for modules
 * @param array    $attempts The user's attempts on course activities
 *
 * @return string  $content  The content of the popup about upcoming and due activities
 *								 
 */
function usp_ews_warning_popup($events, $attempts, $userid, $courseid){

	global $CFG, $OUTPUT, $SESSION;
	// popup content
	$content = '';
	
	$viewedpopup_cid_uid = 'usp_ewsviewedpopup'. $courseid .$userid;

	// popup now seen
	$SESSION->usp_ewsviewedpopup->$viewedpopup_cid_uid = true;
	
	//VS-v2 removed the pending activity
	$attemptcount = 0;  // completed activities count
	$expectedcount = 0; // expected activities count 
	$futurecount= 0;    // upcoming activities count 
	
	// hold information of the activities
	//$popupArray = array();
	$popupFuture = array();

	// formate the date
	$dateformat = get_string('date_format', 'block_usp_ews');
	$now = time();

	// each events checks if attmpted or not according to the configuration
	foreach ($events as $event) {			
		$attempted = $attempts[$event['type'].$event['id']];
		// count attempted events
		if ($attempted) {
			$attemptcount++;
		}
		// check for upcoming activities
		else if($event['expected'] > $now){   

			//to find difference of current time and next week
			$aweek = mktime(0, 0, 0, date("m"), date("d")+7, date("Y"));

			// if the event is due within 7 days from current time
			// then alert the user these events
			if($aweek > $event['expected']){
				$popupFuture[$futurecount] = array(format_string(addSlashes($event['name'])),
							userdate($event['expected'], $dateformat, $CFG->timezone),
							get_string($event['action'], 'block_usp_ews'),
							$event['type'],
							$event['cmid']);
				$futurecount++;
			}				
		}
		// check for expected events and puts in a array
		else {
				$expectedcount++; //count number of expected activities
			}  
	}

	// display alert popup
	// if some pending activities or upcoming activities 
	if($futurecount > 0)
	{		
		$strdate = get_string('duedate', 'block_usp_ews');
		// popup content
		$content .= '<div class="usp_ews_popupContainer" style="display:none;"> 
					<a class="usp_ews_popupclose" title="' .  get_string('close', 'block_usp_ews') . '"><img src="' . $OUTPUT->pix_url('close', 'block_usp_ews') . '" alt="" /></a>';
					
		// if upcoming events
		$content .= '<p class="usp_ews_popupheader">'
				. '<img src="' . $OUTPUT->pix_url('alertfuture', 'block_usp_ews') . '" alt="" />'
				. '<span class="blink_header">&nbsp;&nbsp;&nbsp;' . get_string('upcomingactivity', 'block_usp_ews') 
				. '</span></p>'
				. '<div class="usp_ews_popupcontactArea">';

		for($i=0; $i < $futurecount; $i++){
			// event's link
			$content .= '<p><a href="' . $CFG->wwwroot . '/mod/' . $popupFuture[$i][3] . '/view.php?id='
			. $popupFuture[$i][4] . '">' . $popupFuture[$i][0] . '</a>' 
			.$strdate . $popupFuture[$i][1] . '</p>';
		}
		
		$content .= '</div>';
		
		// placing cross to close popup box
		$content .= '<p class="usp_ews_click">'. get_string('closepopup', 'block_usp_ews') 
			.'</p></div>'
			. '<div class="usp_ews_overlayEffect">'
			. '</div>'
			. '<!--end popup content-->';			
	}	
	return $content;
}


/**
 * ** get 5 popular activities in past 7 days
 *
 *  @return object  list of 5 popular activities in past 7 days
 */
function usp_ews_get_popularActivities($courseid, $contextid)
{ 
 global $DB;
	$timeval = time() - (7 * 24 * 60 * 60); //last week
	$sql = "SELECT @rn:=@rn+1 AS rank, mid, section, numviews , lasttime 
			FROM ( SELECT cm.id AS mid, section, COUNT('x') AS numviews, MAX(time) AS lasttime 
			FROM  {course_modules} cm 
				JOIN {modules} m ON m.id = cm.module 
				JOIN {log} l ON l.cmid = cm.id  
			WHERE 
				cm.course = ". $courseid  
				. " AND  time > " .  $timeval  
				. " AND (SELECT min(roleid) 
						FROM {role_assignments} 
						WHERE contextid = " . $contextid. " AND userid = l.userid) = 5
				AND l.action LIKE 'view%' AND m.visible = 1 
			GROUP BY cm.id 
			ORDER BY numviews DESC LIMIT 0, 4) t1, (SELECT @rn:=0) t2;";
			
	$result = $DB->get_records_sql($sql);

	return $result;	
}

/**
 * Get course last access sql for front page used for sorting
 *
 * @param accesssince   The last access time 
 *
 */
function usp_ews_get_course_lastaccess_sql($accesssince='') {
    if (empty($accesssince)) {
        return '';
    }
    if ($accesssince == -1) { // never
        return 'ul.timeaccess = 0';
    } else {
        return 'ul.timeaccess != 0 AND ul.timeaccess < '.$accesssince;
    }
}

/**
 * Get user last access sql used for sorting
 *
 * @param accesssince   The last access time 
 *
 */
function usp_ews_get_user_lastaccess_sql($accesssince='') {
    if (empty($accesssince)) {
        return '';
    }
    if ($accesssince == -1) { // never
        return 'u.lastaccess = 0';
    } else {
        return 'u.lastaccess != 0 AND u.lastaccess < '.$accesssince;
    }
}

// finding number of weeks from start of course
function usp_ews_get_weeks_elapsed($courseid){	

	global $DB;

	$sql = "SELECT UNIX_TIMESTAMP() - startdate AS timediff
			FROM {course} 
			WHERE id = :courseid ;";
	
	$params = array('courseid'=>$courseid);
	
	$timediff = $DB->count_records_sql($sql, $params);

	$num_weeks = ceil($timediff/(7 * 24 * 60 * 60));

	return $num_weeks;
	
}
// finding color for the completion bar
function usp_ews_find_color_percentage($mycompletion){
		
		if($mycompletion <= EWS_DEFAULT_UNSATISFACTORY_INDEX){
			$color =  get_string('notAttempted_colour', 'block_usp_ews');
		}else if($mycompletion > EWS_DEFAULT_SATISFACTORY_INDEX){
			$color =  get_string('attempted_colour', 'block_usp_ews');
		}else{
			$color = get_string('amber_colour', 'block_usp_ews');
		}
		return $color;

}

// finding color for trafic light main dashboard
function usp_ews_find_color_index($index){
		
	$result = array();
	$index = $index/2;
	if($index <= EWS_DEFAULT_UNSATISFACTORY_INDEX){
		$color = get_string('notAttempted_colour', 'block_usp_ews');
		$coloractual = get_string('red', 'block_usp_ews');
	}else if($index >= EWS_DEFAULT_SATISFACTORY_INDEX){
		$color = get_string('attempted_colour', 'block_usp_ews');
		$coloractual = get_string('green', 'block_usp_ews');
	}else{
		$color = get_string('amber_colour', 'block_usp_ews');
		$coloractual = get_string('orange', 'block_usp_ews');
	}
	
	$result['color'] = $color;
	$result['coloractual'] = $coloractual;
	
	return $result;

}

function usp_ews_find_interaction_light($index){
	// by default
	global $OUTPUT;
	$img = $OUTPUT->pix_url('redtraffic', 'block_usp_ews');
	$msg = get_string('interactunsatisfactory', 'block_usp_ews');
		
	$index = $index/2;
	if($index <= EWS_DEFAULT_UNSATISFACTORY_INDEX){
		$img = $OUTPUT->pix_url('redtraffic', 'block_usp_ews');
		$msg = get_string('interactunsatisfactory', 'block_usp_ews');
	}else if($index >= EWS_DEFAULT_SATISFACTORY_INDEX){
		$img = $OUTPUT->pix_url('greentraffic', 'block_usp_ews');
		$msg = get_string('interactsatisfactory', 'block_usp_ews');
	}else{
		$img = $OUTPUT->pix_url('orangetraffic', 'block_usp_ews');
		$msg = get_string('interactaverage', 'block_usp_ews');
	}
	
	$interact = array();
	$interact['img'] = '<img width="30px" height="30px" src="'. $img .'" alt="" /><p class="interactionresult">' . $msg . '</p>';
	$interact['msg'] = $msg;
	
	return $interact;

}

    /**
* Cleanup temporary data
*
* @global object
* @global object
* @param boolean $full true means do a full cleanup - all sessions for current user, false only the active iid
*/
function usp_ews_cleanup_csv($full=false) {
	global $USER, $CFG;

	if ($full) {
		@remove_dir($CFG->tempdir.'/usp_ews_studentid_import/cvs/'.$USER->id);
	} else {
		@unlink($CFG->tempdir.'/usp_ews_studentid_import/cvs/'.$USER->id);
	}
}

	
/**
 * ** check if the number not zero
 *    used to prevent division by zero
 *    only application for role < 3 - 
 *    for lecturer's and students, no such case
 *
 *	@param int   $num   number to be checked
 *  @return int  $num   if num <= 0 then return 1
 *						that is that user itself
 */
function is_not_zero($num){
	if($num <= 0){
		return 1;
	}
	
	return $num;	
}

/**
 * ** get user's number of logins (in days)
 *
 * @param int   $user_id  user id 
 *
 * @return int  $logindays  number of times that user logged in so far
 */
function usp_ews_get_userlogin_freq($userid, $courseid, $numdays=-1)
{
 global $DB;
 
	$wheres = '';
 	if($numdays != -1){
		$timeval = time() - ($numdays * 24 * 60 * 60); // if 7 then last week
		$wheres .= "time > " .  $timeval . " AND ";
	}
	
	$sql = "SELECT COUNT(DISTINCT FROM_UNIXTIME(time,'%Y %D %M')) AS login 
			FROM {log} 
			WHERE $wheres userid = :userid AND course = :courseid ;";
	
	$params = array('userid'=>$userid, 'courseid'=>$courseid);
	
	$logindays = $DB->count_records_sql($sql, $params);

	return $logindays;
}

/**
 * ** Count User in the course
 *
 * @param int    $context_id  The context id of the course
 * @param int 	 $userid
 * @return int   $resultset   counted number of the users in the course
 * 
 * This count is based on the role
 * Number returned for course coordinator is different from the number of student
 */
function usp_ews_get_usercount($context_id, $userid) 
{
 global $DB;
 
	$result = 0;
	$sql = "SELECT COUNT(userid) 
			FROM {role_assignments} 
			WHERE contextid = :contextid 
			 AND roleid=(SELECT min(roleid) FROM {role_assignments} 
						  WHERE contextid = :context_id    
						  AND userid = :userid);";

	$params= array('contextid'=>$context_id, 'context_id'=>$context_id, 'userid'=>$userid);

	$result = $DB->count_records_sql($sql, $params);
	
	// returns number of users
	return $result;	
}

/*New Function to get logs*/
function usp_ews_get_log($courseid, $userid=-1)
{
 global $DB;

	if($userid=-1){
		$where = "WHERE course = :courseid";
	}else{
		$where = "WHERE userid = :userid AND course = :courseid"; 		
	}
	$sql = "SELECT id, cmid, module, action, userid, COUNT(*) AS num 
			FROM {log} 
			$where 
			GROUP BY module, action, cmid, userid 
			ORDER BY num DESC";
	
	$params = array('userid'=>$userid, 'courseid'=>$courseid);
	
	$log = $DB->get_records_sql($sql, $params);

	return $log;
}

/**
 * ** get class's log
 *
 *  @return object  class's log containing the events/activities and action taken
 */
function usp_ews_get_classlog($context_id, $userid, $courseid)
{
 global $DB;
 
	$sql = "SELECT l.id, module, action 
			FROM {log} l, {role_assignments} r 
			WHERE r.userid = l.userid 
				AND contextid = :contextid 
				AND roleid IN (SELECT min(roleid) 
					FROM {role_assignments} 
					WHERE contextid = :context_id
					AND userid = :userid) 
			AND course = :courseid 
			GROUP BY l.id;";
	
	// should be in order as used in the sql	
	$params= array('contextid'=>$context_id, 'context_id'=>$context_id, 'userid'=>$userid, 'courseid'=>$courseid);
	
	$result = $DB->get_records_sql($sql, $params);
	
	return $result;	
}

function usp_ews_generate_overall_login_graph($var, $user, $logindetail, $namestudent){
 global $OUTPUT;
	$graph = '&nbsp;&nbsp;<a id="login">' . 
				"<span class='openloginpopup' stutname=" . $namestudent . " stutid=$user->id stutidnum=$user->idnumber $var=$logindetail>" . '<img src="'. $OUTPUT->pix_url('t/scales') .'" alt="" /></a>
				</span>
				<div class="logincontainer" id="loginpopup' . $user->id .'" style="display:none;">
					<div class="usp_ews_loginclosebtn" title="Close" id="usp_ews_loginclosebtn">
						<img src="' . $OUTPUT->pix_url('close', 'block_usp_ews') . '" alt="" />
					</div>
					'. '<div id="loginchart' . $user->id . '" class="loginchart"></div>
				</div>
				';
	return $graph;
}