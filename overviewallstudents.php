<?php

/**
*	Report of all the students
*	The report contains many items 
*   the report can also be exported in excel format
*
*/
	// including the files and library
    require_once(dirname(__FILE__) . '/../../config.php');
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->libdir.'/filelib.php');

	// calls for jpgragh library
	$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/blocks/usp_ews/js/highcharts.js'));
	
    define('USER_SMALL_CLASS', 20);   // Below this is considered small
    define('USER_LARGE_CLASS', 200);  // Above this is considered large
    define('DEFAULT_PAGE_SIZE', 20);
    define('SHOW_ALL_PAGE_SIZE', 2000);
    define('MODE_BRIEF', 0);

	// page information
    $page         = optional_param('page', 0, PARAM_INT);                     // which page to show
    $perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page
    $mode         = optional_param('mode', NULL, PARAM_INT);                  // use the MODE_ constants
    $accesssince  = optional_param('accesssince',0,PARAM_INT);                // filter by last access. -1 = never
    $search       = optional_param('search','',PARAM_RAW);                    // make sure it is processed with p() or s() when sending to output!
    $roleid       = optional_param('roleid', 0, PARAM_INT);                   // optional roleid, 0 means all enrolled users (or all on the frontpage)

	// Gather form data
    $contextid    = optional_param('contextid', 0, PARAM_INT);                // context id
    $courseid     = required_param('cid', PARAM_INT);                         // required course id
    $inst_id      = required_param('inst_id', PARAM_INT);                          // required instance id of the block   
	$userid       = optional_param('uid', $USER->id, PARAM_INT);			  // user id
	$modetab      = optional_param('modetab', 'overview', PARAM_ALPHA);       // overview tab

	// getting the context
    if ($contextid) {
        $context = context::instance_by_id($contextid, MUST_EXIST);
        if ($context->contextlevel != CONTEXT_COURSE) {
            print_error('invalidcontext');
        }
        $course = $DB->get_record('course', array('id'=>$context->instanceid), '*', MUST_EXIST);
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        $context = context_course::instance($course->id, MUST_EXIST);
    }
	// Check user is logged in and 
	// capable of seeing this page(only lecturers/cordinators can access)
	require_login($course, false);
	require_capability('block/usp_ews:overview', $context);
	require_capability('moodle/course:viewparticipants', $context);
	
	$systemcontext = context_system::instance();

		// Set up page parameters and the navigation links
	$PAGE->set_course($course);
	
	$PAGE->set_url('/blocks/usp_ews/overviewallstudents.php', array(
			'page' => $page,
			'perpage' => $perpage,
			'mode' => $mode,
			'accesssince' => $accesssince,
			'search' => $search,
			'roleid' => $roleid,
			'contextid' => $contextid,
			'inst_id' => $inst_id,
			'cid' => $courseid));
	$PAGE->set_context($context);
	$strblockname = get_string("config_default_title", "block_usp_ews");
	$title = get_string('overview', 'block_usp_ews');
	$PAGE->set_title($title);
	
	// Start page output
	// prints the heading and subheading of the page
	$PAGE->set_heading($course->fullname);
	//$PAGE->navbar->add($strblockname, $dashboardurl);
	$PAGE->navbar->add($title);
	$PAGE->set_pagelayout('standard');
	$PAGE->set_pagelayout('incourse');
	
	// not needed anymore
    unset($contextid);
    unset($courseid);
	
	// printing heading, sudheading
	// stating the main div container
    echo $OUTPUT->header();
	echo $OUTPUT->heading($title, 2);
	echo $OUTPUT->container_start('block_usp_ews');

	// including tab file and setting current tab to overview 
	$currenttab = $modetab;
	$showroles = 1;
	include($CFG->dirroot.'/blocks/usp_ews/tabs.php');
	
	// setting role name url, depending on the role of the users
    $rolenamesurl = new moodle_url("overviewallstudents.php?inst_id=$inst_id&cid=$course->id&contextid=$context->id&sifirst=&silast=");

	// getting all roles available in that course 
    $allroles = get_all_roles();
    $roles = get_profile_roles($context);
    $allrolenames = array();
    $rolenames = array(0=>get_string('allparticipants'));
	
	// Used in menus etc later on
	// filling the rest of rolenames array with other roles in the course
    foreach ($allroles as $role) {
        $allrolenames[$role->id] = strip_tags(role_get_name($role, $context));   
        if (isset($roles[$role->id])) {
            $rolenames[$role->id] = $allrolenames[$role->id];
        }
    }

    // make sure other roles may not be selected by any means
    if (empty($rolenames[$roleid])) {
        print_error('noparticipants');
    }
	
	// adding viewing of this page to log table
    add_to_log($course->id, 'blocks', 'view all', "usp_ews/overviewallstudents.php?cid=$course->id", $course->id, '', $userid);

    $bulkoperations = has_capability('moodle/course:bulkmessaging', $context);

    $strnever = get_string('never');

    $datestring = new stdClass();
    $datestring->year  = get_string('year');
    $datestring->years = get_string('years');
    $datestring->day   = get_string('day');
    $datestring->days  = get_string('days');
    $datestring->hour  = get_string('hour');
    $datestring->hours = get_string('hours');
    $datestring->min   = get_string('min');
    $datestring->mins  = get_string('mins');
    $datestring->sec   = get_string('sec');
    $datestring->secs  = get_string('secs');

    $mode = MODE_BRIEF;

	// Check to see if groups are being used in this course
	// and if so, set $currentgroup to reflect the current group

    $groupmode    = groups_get_course_groupmode($course);   // Groups are being used
    $currentgroup = groups_get_course_group($course, true);

    if (!$currentgroup) {      // To make some other functions work better later
        $currentgroup  = NULL;
    }

    $isseparategroups = ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context));

    echo '<div class="userlist">';

    if ($isseparategroups and (!$currentgroup) ) {
        // The user is not in the group so show message and exit
        echo $OUTPUT->heading(get_string("notingroup"));
        echo $OUTPUT->footer();
        exit;
    }

    // Should use this variable so that we don't break stuff every time a variable is added or changed.
	// setting base url
    $baseurl = new moodle_url('overviewallstudents.php', array(
            'contextid' => $context->id,
            'roleid' => $roleid,
            'cid' => $course->id,
			'inst_id' => $inst_id,
            'perpage' => $perpage,
            'accesssince' => $accesssince,
            'search' => s($search)));

	// setting up tags
    if ($course->id == SITEID) {
        $filtertype = 'site';
    } else if ($course->id && !$currentgroup) {
        $filtertype = 'course';
        $filterselect = $course->id;
    } else {
        $filtertype = 'group';
        $filterselect = $currentgroup;
    }



	// Get the hidden field list
    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $hiddenfields = array();  // teachers and admins are allowed to see everything
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    if (isset($hiddenfields['lastaccess'])) {
        // do not allow access since filtering
        $accesssince = 0;
    }

	// Print settings and things in a table across the top
    $controlstable = new html_table();
    $controlstable->attributes['class'] = 'controls';
    $controlstable->cellspacing = 0;
    $controlstable->data[] = new html_table_row();


    if (!isset($hiddenfields['lastaccess'])) {
        // get minimum lastaccess for this course and display a dropbox to filter by lastaccess going back this far.
        // we need to make it diferently for normal courses and site course

		$minlastaccess = $DB->get_field_sql('SELECT min(lastaccess)
											   FROM {user}
											  WHERE lastaccess != 0');
		$lastaccess0exists = $DB->record_exists('user', array('lastaccess'=>0));
        
        $now = usergetmidnight(time());
        $timeaccess = array();
        $baseurl->remove_params('accesssince');

        // makes sense for this to go first.
        $timeoptions[0] = get_string('selectperiod');

        // days
        for ($i = 1; $i < 7; $i++) {
            if (strtotime('-'.$i.' days',$now) >= $minlastaccess) {
                $timeoptions[strtotime('-'.$i.' days',$now)] = get_string('numdays','moodle',$i);
            }
        }
        // weeks
        for ($i = 1; $i < 10; $i++) {
            if (strtotime('-'.$i.' weeks',$now) >= $minlastaccess) {
                $timeoptions[strtotime('-'.$i.' weeks',$now)] = get_string('numweeks','moodle',$i);
            }
        }
        // months
        for ($i = 2; $i < 12; $i++) {
            if (strtotime('-'.$i.' months',$now) >= $minlastaccess) {
                $timeoptions[strtotime('-'.$i.' months',$now)] = get_string('nummonths','moodle',$i);
            }
        }
        // try a year
        if (strtotime('-1 year',$now) >= $minlastaccess) {
            $timeoptions[strtotime('-1 year',$now)] = get_string('lastyear');
        }

        if (!empty($lastaccess0exists)) {
            $timeoptions[-1] = get_string('never');
        }
    }
		
    echo html_writer::table($controlstable);
	
	
	$parameters = array('inst_id'=>$inst_id, 'cid' => $course->id, 'role'=> $roleid);
	$url = new moodle_url('/blocks/usp_ews/report/xls_report.php', $parameters);
	$label = get_string('excel', 'block_usp_ews');
	echo $OUTPUT->single_button($url, $label);
	
	echo $OUTPUT->container_end();
	
    // Define a table showing a list of users in the current role selection
    $tablecolumns = array();
    $tableheaders = array();
    if ($bulkoperations && $mode === MODE_BRIEF) {
        $tablecolumns[] = 'select';
        $tableheaders[] = get_string('select');
    }
    $tablecolumns[] = 'userpic';
	$tableheaders[] = get_string('picture', 'block_usp_ews');	
	
    $tablecolumns[] = 'fullname';
	$tableheaders[] = get_string('fullnameuser');

    $extrafields = get_extra_user_fields($context);

	// checks if mode is brief
	// this is always the case for current version
	// to enable future updates in modes, mode is kept
	if ($mode === MODE_BRIEF) {
		// defines table column for idnumber
		// and puts its heading in tableheaders array
		$tablecolumns[] = 'idnumber';
		$tableheaders[] = get_string('idnumber');

		if (!isset($hiddenfields['lastaccess'])) {
			// defines table column for lastaccess
			// and puts its heading in tableheaders array
			$tablecolumns[] = 'lastaccess';
			$tableheaders[] = get_string('lastaccess');
		}
	
		// defines table column for numlogin
		// and puts its heading in tableheaders array
        $tablecolumns[] = 'numlogin';
        $tableheaders[] = get_string('numlogin', 'block_usp_ews');
	
		// defines table column for progressbar
		// and puts its heading in tableheaders array
        $tablecolumns[] = 'progressbar';
        $tableheaders[] = get_string('progressbar', 'block_usp_ews');

		// defines table column for progress percentage
		// and puts its heading in tableheaders array
        $tablecolumns[] = 'progress';
        $tableheaders[] = get_string('progress', 'block_usp_ews');

        $tablecolumns[] = 'interact';
        $tableheaders[] = get_string('interact', 'block_usp_ews');
    }

	// calls for flexible table class
	// defines column and its heading using array filled above
	// defines base url for table
    $table = new flexible_table('block-usp_ews-overall-'.$course->id);
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl->out());

	// if not hidden last access then allows table sorting the last access
    if (!isset($hiddenfields['lastaccess'])) {
        $table->sortable(true, 'lastaccess', SORT_DESC);
    } else {
        $table->sortable(true, 'firstname', SORT_ASC);
    }

	// allows following sorting column options
    $table->no_sorting('roles');
    $table->no_sorting('groups');
    $table->no_sorting('groupings');
    $table->no_sorting('select');
	$table->no_sorting('numlogin');
    $table->no_sorting('progressbar');
	$table->no_sorting('progress');
	$table->no_sorting('interact');

	// adding table attributes
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'usp_ews_online_overall_report');
    $table->set_attribute('class', 'generaltable generalbox');

	// adding css properties to the table
	//$table->column_style_all('padding', '5px 10px');
	$table->column_style_all('text-align', 'left');
	$table->column_style_all('vertical-align', 'middle');
	$table->column_style('userpic', 'width', '2%');
	$table->column_style('fullname', 'width', '0');
	$table->column_style('idnumber', 'width', '0');
	$table->column_style('numlogin', 'text-align', 'center');
	$table->column_style('numlogin', 'width', '3%');
	$table->column_style('progressbar', 'width', '200px');
	$table->column_style('lastaccess', 'width', '20%');
	$table->column_style('progress', 'text-align', 'center');
	$table->column_style('progress', 'width', '5%');
	$table->column_style('interact', 'text-align', 'center');
	$table->column_style('select', 'text-align', 'center');
	$table->column_style('select', 'width', '2%');
	
    $table->set_control_variables(array(
                TABLE_VAR_SORT    => 'ssort',
                TABLE_VAR_HIDE    => 'shide',
                TABLE_VAR_SHOW    => 'sshow',
                TABLE_VAR_IFIRST  => 'sifirst',
                TABLE_VAR_ILAST   => 'silast',
                TABLE_VAR_PAGE    => 'spage'
                ));
	// setting the table
    $table->setup();

    list($esql, $params) = get_enrolled_sql($context, NULL, $currentgroup, true);
    $joins = array("FROM {user} u");
    $wheres = array();

    $extrasql = get_extra_user_fields_sql($context, 'u', '', array(
            'id', 'username', 'firstname', 'lastname', 'idnumber', 'email', 'city', 'country',
            'picture', 'lang', 'timezone', 'maildisplay', 'imagealt', 'lastaccess'));

    $mainuserfields = user_picture::fields('u', array('username', 'idnumber', 'email', 'city', 'country', 'lang', 'timezone', 'maildisplay'));

	$select = "SELECT $mainuserfields, COALESCE(ul.timeaccess, 0) AS lastaccess$extrasql";
	$joins[] = "JOIN ($esql) e ON e.id = u.id"; // course enrolled users only
	$joins[] = "LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = :courseid)"; // not everybody accessed course yet
	$params['courseid'] = $course->id;
	if ($accesssince) {
		$wheres[] = usp_ews_get_course_lastaccess_sql($accesssince);
	}
    

    // performance hacks - we preload user contexts together with accounts
    $ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
    $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_USER;
    $select .= $ccselect;
    $joins[] = $ccjoin;


    // limit list to users with some role only
    if ($roleid) {
        // We want to query both the current context and parent contexts.
        list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');

        $wheres[] = "u.id IN (SELECT userid FROM {role_assignments} WHERE roleid = :roleid AND contextid $relatedctxsql)";
        $params = array_merge($params, array('roleid' => $roleid), $relatedctxparams);
    }

    $from = implode("\n", $joins);
    if ($wheres) {
        $where = "WHERE " . implode(" AND ", $wheres);
    } else {
        $where = "";
    }

    $totalcount = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);

    if (!empty($search)) {
        $fullname = $DB->sql_fullname('u.firstname','u.lastname');
        $wheres[] = "(". $DB->sql_like($fullname, ':search1', false, false) .
                    " OR ". $DB->sql_like('email', ':search2', false, false) .
                    " OR ". $DB->sql_like('idnumber', ':search3', false, false) .") ";
        $params['search1'] = "%$search%";
        $params['search2'] = "%$search%";
        $params['search3'] = "%$search%";
    }

    list($twhere, $tparams) = $table->get_sql_where();
    if ($twhere) {
        $wheres[] = $twhere;
        $params = array_merge($params, $tparams);
    }

    $from = implode("\n", $joins);
    if ($wheres) {
        $where = "WHERE " . implode(" AND ", $wheres);
    } else {
        $where = "";
    }

    if ($table->get_sql_sort()) {
        $sort = ' ORDER BY '.$table->get_sql_sort();
    } else {
        $sort = '';
    }

    $matchcount = $DB->count_records_sql("SELECT COUNT(u.id) $from $where", $params);

    $table->initialbars(true);
    $table->pagesize($perpage, $matchcount);

    // list of users at the current visible page - paging makes it relatively short
    $userlist = $DB->get_recordset_sql("$select $from $where $sort", $params, $table->get_page_start(), $table->get_page_size());

    /// If there are multiple Roles in the course, then show a drop down menu for switching
    if (count($rolenames) > 1) {

		echo $OUTPUT->container_start('usp_ewsoverviewmenus');
		echo '&nbsp;'.get_string('currentrole', 'role') . ':&nbsp;';
		echo $OUTPUT->single_select($rolenamesurl, 'roleid', $rolenames, $roleid, null, 'rolesform');
		echo $OUTPUT->container_end();
    } else if (count($rolenames) == 1) {
        // when all users with the same role - print its name
        echo '<div class="rolesform">';
        echo get_string('role').get_string('labelsep', 'langconfig');
        $rolename = reset($rolenames);
        echo $rolename;
        echo '</div>';
    }

	// getting role details
    if ($roleid > 0) {
        $a = new stdClass();
        $a->number = $totalcount;
        $a->role = $rolenames[$roleid];
        $heading = format_string(get_string('xuserswiththerole', 'role', $a));

        if ($currentgroup and $group) {
            $a->group = $group->name;
            $heading .= ' ' . format_string(get_string('ingroup', 'role', $a));
        }

        if ($accesssince) {
            $a->timeperiod = $timeoptions[$accesssince];
            $heading .= ' ' . format_string(get_string('inactiveformorethan', 'role', $a));
        }

        $heading .= ": $a->number";

        if (user_can_assign($context, $roleid)) {
            $headingurl = new moodle_url($CFG->wwwroot . '/' . $CFG->admin . '/roles/assign.php',
                    array('roleid' => $roleid, 'contextid' => $context->id));
            $heading .= $OUTPUT->action_icon($headingurl, new pix_icon('t/edit', get_string('edit')));
        }
        echo $OUTPUT->heading($heading, 3);
    } else {
        if ($course->id == SITEID and $roleid < 0) {
            $strallparticipants = get_string('allsiteusers', 'role');
        } else {
            $strallparticipants = get_string('allparticipants');
        }
        if ($matchcount < $totalcount) {
            echo $OUTPUT->heading($strallparticipants.get_string('labelsep', 'langconfig').$matchcount.'/'.$totalcount , 3);
        } else {
            echo $OUTPUT->heading($strallparticipants.get_string('labelsep', 'langconfig').$matchcount, 3);
        }
    }


	// if any users selected for emailed
    if ($bulkoperations) {
        echo '<form action="'. $CFG->wwwroot.'/blocks/usp_ews/notification/action_redir.php' . '" method="post" id="participantsform">';
        echo '<div>';
        echo '<input type="hidden" name="sesskey" value="'. sesskey() .'" />';
        echo '<input type="hidden" name="inst_id" value="'. $inst_id .'" />';
		echo '<input type="hidden" name="reporttype" value="backtoactivityreport" />'."\n";
        echo '<input type="hidden" name="returnto" value="'. s($PAGE->url) .'" />';
    }

	$timeformat = get_string('strftimedate');

	// userlist of students
	if ($userlist)  {
		// Get the modules to check progress on
		$modules = usp_ews_modules_in_use($course->id);
		if (empty($modules)) {
			echo get_string('no_events_config_message', 'block_usp_ews');
			echo $OUTPUT->footer();
			die();
		}
		// getting configuration information from configuration table
		$configureddata = $DB->get_record('usp_ews_config', array('courseid' => $course->id, 'ewsinstanceid'=>$inst_id));
			
		// decoding the monitored activity
		$monitoreddata = json_decode($configureddata->monitoreddata);
		// Check if activities/resources have been selected in config

		// if events not selected then 
		// displays no event or no event visible message
		// and exit
		if ($monitoreddata==null) {
			echo get_string('no_events_message', 'block_usp_ews');
			echo $OUTPUT->footer();
			die();
		}
		if (empty($monitoreddata)) {
			echo get_string('no_visible_events_message', 'block_usp_ews');
			echo $OUTPUT->footer();
			die();
		}
		
		// get number of weeks from the start date
		//$number_weeks_elapsed = usp_ews_get_weeks_elapsed($course->id);
		// to prevent duplicate users
		$usersprinted = array();
		
		// for each of the user
		// find and fills information in an array
		foreach ($userlist as $user) {
			/// Prevent duplicates by r.hidden 
			if (in_array($user->id, $usersprinted)) { 
				continue;
			}
			
			// Add new user to the array of users printed
			$usersprinted[] = $user->id; 

			context_helper::preload_from_record($user);

			// when last user accessed the course
			if ($user->lastaccess) {
				$lastaccess = format_time(time() - $user->lastaccess, $datestring);
			} else {
				$lastaccess = $strnever;
			}

			// users interaction details
			$userinteraction = $DB->get_record('usp_ews_interaction', array('userid' => $user->id, 'courseid' => $course->id));
			
			//login detail of the student for the graph
			$logindetail = '';
			if(!empty($userinteraction))
				$logindetail = $userinteraction->logindetail;
		
		   // last 7days login 
			if(!empty($userinteraction))
				$lastsevenlogin = $userinteraction->lastsevenlogin;
			else
				$lastsevenlogin = usp_ews_get_userlogin_freq($user->id, $course->id, EWS_DEFAULT_LAST_SEVEN_LOGIN);

			// gets user's context
			$usercontext = context_user::instance($user->id);

			if ($piclink = ($USER->id == $user->id || has_capability('moodle/user:viewdetails', $context) || has_capability('moodle/user:viewdetails', $usercontext))) {
				$profilelink = '<strong><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.fullname($user).'</a></strong>';
			} else {
				$profilelink = '<strong>'.fullname($user).'</strong>';
			}

			$data = array();
			if ($bulkoperations) {
				$data[] = '<input type="checkbox" class="usercheckbox" name="user'.$user->id.'" />';
			}
			// user fullname and picture
			$data[] = $OUTPUT->user_picture($user, array('size' => 35, 'courseid'=>$course->id));
			$data[] = $profilelink;

			// adding user's id number in the array
			if ($mode === MODE_BRIEF) {
				$data[] = $user->idnumber;
	
				// adding last access for the user
				if (!isset($hiddenfields['lastaccess'])) {
					$data[] = $lastaccess;
				}


				// tie the var with studentid for different students login graph
				$var = "var$user->idnumber";	
				$namestudent = $user->firstname . ' ' . $user->lastname;
				$graph = usp_ews_generate_overall_login_graph($var, $user, $logindetail, $namestudent);
				$data[] = $lastsevenlogin . $graph;
				// monitored events
				$events = usp_ews_event_information($course->id, $monitoreddata, $user->id, $modules);
		
				// Checks if a user has attempted/viewed/etc. monitoed activity/resource
				$attempts = usp_ews_get_attempts($modules, $events, $user->id, $course->id);

				// puts monitored activities in the progress bar 
				$progressbar = usp_ews_progress_bar($configureddata, $events, $course->id, $user->id, $attempts, true, 'coursereport');
				// adds progress bar in the array for the user
				$data[] = $progressbar;				

				// calculates the completed progress value
				$progressvalue = usp_ews_get_progess_percentage($events, $attempts);
				
				$progress = $progressvalue.'%';
			   
			   // adds the percentage in the array
				$data[] = $progress;
				
				$indx = 0;
				if(isset($userinteraction->interactindex))
					$indx = $userinteraction->interactindex;
				// adding interaction traffic light
				$traficlight = usp_ews_find_interaction_light($indx);
				$data[] = $traficlight['img'];
						
			}	

			$table->add_data($data);
		}
				
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
		$arguments = array($CFG->wwwroot, array_keys($modules));
		$PAGE->requires->js_init_call('M.block_usp_ews.showdetails.init', $arguments, false, $jsmodule);
		
	}

	// display table
	$table->print_html();

	// if any user selected for alert then allow to message or note
    if ($bulkoperations) {
        echo '<br /><div class="buttons">';
        echo '<input type="button" id="checkall" value="'.get_string('selectall').'" /> ';
        echo '<input type="button" id="checknone" value="'.get_string('deselectall').'" /> ';
        $displaylist = array();
        $displaylist['messageselect.php'] = get_string('messageselectadd');
        if (!empty($CFG->enablenotes) && has_capability('moodle/notes:manage', $context)) {
            $displaylist['addnote.php'] = get_string('addnewnote', 'notes');
            $displaylist['groupaddnote.php'] = get_string('groupaddnewnote', 'notes');
        }

        echo $OUTPUT->help_icon('withselectedusers');
        echo html_writer::tag('label', get_string("withselectedusers"), array('for'=>'formactionid'));
        echo html_writer::select($displaylist, 'formaction', '', array(''=>'choosedots'), array('id'=>'formactionid'));

		// values passed to action_redir script, required in actioned page
        echo '<input type="hidden" name="id" value="'. $course->id . '" />';
        echo '<noscript style="display:inline">';
        echo '<div><input type="submit" value="'.get_string('ok').'" /></div>'; // save changes button
        echo '</noscript>';
        echo '</div></div>';
        echo '</form>';

        $module = array('name'=>'core_user', 'fullpath'=>'/user/module.js');
        $PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);
    }
	// textbox that allows to search for specific user
    if (has_capability('moodle/site:viewparticipants', $context)) {
        echo '<form action="overviewallstudents.php" class="searchform"><div>
		<input type="hidden" name="inst_id" value="'.$inst_id.'" />'.'<input type="hidden" name="cid" value="'.$course->id.'" />'.get_string('search').':&nbsp;'."\n";
        echo '<input type="text" name="search" value="'.s($search).'" />&nbsp;
		<input type="submit" value="'.get_string('search').'" /></div></form>'."\n";
    }

    $perpageurl = clone($baseurl);
    $perpageurl->remove_params('perpage');
    if ($perpage == SHOW_ALL_PAGE_SIZE) {
        $perpageurl->param('perpage', DEFAULT_PAGE_SIZE);
        echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showperpage', '', DEFAULT_PAGE_SIZE)), array(), 'showall');

    } else if ($matchcount > 0 && $perpage < $matchcount) {
        $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
        echo $OUTPUT->container(html_writer::link($perpageurl, get_string('showall', '', $matchcount)), array(), 'showall');
    }

    echo '</div>';  // userlist

    echo $OUTPUT->footer();

    if ($userlist) {
        $userlist->close();
    }
?>

<script>

	// javascript for login popup 
    $(document).ready(function () {

	 var myDataValues = new Array(); 
	 var myAxes;
	 var seriesCollection;
	 var myChart;
	 
      $(".openloginpopup").click(function () {
			var renderto;
			var studentidnum = $(this).attr("stutidnum");
			var studentid = $(this).attr("stutid");
			var logindetail = jQuery.parseJSON(($(this).attr("var"+studentidnum)));
			console.log(logindetail);

			var studentname = $(this).attr("stutname");

			var student = studentname + ' (' + studentidnum + ')';
			
			var loginstud = new Array();
			var loginclass = new Array();
			
			
            var i =0;
			$.each(logindetail, function(key, element) {
				loginstud[i] = parseFloat(element.my);
				loginclass[i] = parseFloat(element.cls);
				i++;
			});
		
			$("#loginpopup"+studentid).show();
			renderto = "#loginchart"+studentid;
			makelogingraph(student, loginstud, loginclass, renderto);
			
			
			$(".usp_ews_loginclosebtn").click(function () {
				$("#loginpopup"+studentid).hide(400, "swing"); 
		    });
      });    
	  
// making the graph
 function makelogingraph(stu, loginstud, loginclass,renderto) {
		
	YUI().use('charts-legend', function (Y) 
	{ 
	 
		for (var i=0;i<loginstud.length;i++)
		{
			var wk = i+1;
			myDataValues[i] = {week:wk, mylogin:loginstud[i], classlogin:loginclass[i]};
		}
		
        //Define our axes for the chart.
        myAxes = {
            login_graph:{
                keys:["mylogin", "classlogin"],
                position:"left",
                type:"numeric",
				//maximum: 5,
				minimum: 0,

				title: '<?php print_string('gp_loginyaxis', 'block_usp_ews'); ?>',
                styles:{
                    majorTicks:{
                        display: "Day"
                    }
                }
            },
            dateRange:{
                keys:["week"],
                position:"bottom",
                type:"category",
				title: '<?php print_string('gp_loginxaxis', 'block_usp_ews'); ?>',
                styles:{
                    majorTicks:{
                        display: "none"
                    },
                    label: {
                       // rotation:-45,
                        margin:{top:5}
                    }
                }
            }
        };
       
        //define the series 
        seriesCollection = [
         {
                type:"column",
                xAxis:"dateRange",
                yAxis:"login_graph",
                xKey:"week",
                xDisplayName: 'Week',
                yKey:"mylogin",
                yDisplayName: stu,
                styles: {
				    fill: {
                            color: "#4572A7" 
                    },
                    border: {
                        weight: 1,
                        color: "#cbc8ba"
                    },
                    over: {
                        fill: {
                            alpha: 0.7
                        }
                    }
                }
            },
            {
                type:"column",
                xAxis:"dateRange",
                yAxis:"login_graph",
                xKey:"week",
                xDisplayName:'Week',
                yKey:"classlogin",
                yDisplayName:'<?php print_string('leg_loginclass', 'block_usp_ews'); ?>',
                styles: {
                    marker:{
                        fill: {
                            color: "#C35F5C" 
                        },
                        border: {
                            weight: 1,
                            color: "#cbc8ba"
                        },
                        over: {
                            fill: {
                                alpha: 0.7
                            }
                        }
                    }
                }
            }
        ];
		
		var legend = {
			position: "bottom",
		};
        //instantiate the chart
        myChart = new Y.Chart({
                            dataProvider:myDataValues, 
							legend:legend,	
                            axes:myAxes, 
                            seriesCollection:seriesCollection, 
                            horizontalGridlines: true,
                            verticalGridlines: true,
                            render:renderto
        });	
    });
	

  }	

 });
	
  </script>