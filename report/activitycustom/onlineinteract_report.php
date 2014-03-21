<?php
/*
*   Lists of all the users within a given course are shown in the table
*	according to the different roles of the users.
*	The report is shows:picture, full name, ID number, Last Access, 
*					    number of times user logged in, progress bar and progress percentage.
*   The report table can be sorted in multiple ways.
*	The cordinator can view each students progress report and which activities student didn't
*   complete and accordingly cordinator can take following action on the user instantly,
*   this actions are: send a message, add a new note or add a common note to group of students..
*   There are many search option in the report, cordinator can search specific idnumber of name,
*   or even using firname and/or surname initials
*
*   The script also has the excel report button was directs to the excel report folder
*   where the actual excel report is generated according to the role..  
*
*   @access This script can only be accessed by the lecturer or coordinator or of higher role        
*/


    // including the files and library
    require_once(dirname(__FILE__) . '/../../../../config.php');
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php');
	require_once($CFG->libdir.'/tablelib.php');
	
	//require_once('ews_activity_form.php');


	define('DEFAULT_PAGE_SIZE', 10);
	define('SHOW_ALL_PAGE_SIZE', 5000);
	
	// Gather form data
    $contextid    = optional_param('contextid', 0, PARAM_INT);                // context id
    $courseid     = required_param('cid', PARAM_INT);                         // required course id
    $inst_id      = required_param('inst_id', PARAM_INT);                          // required instance id of the block   
	$modetab      = optional_param('modetab', 'onlineinteract_report', PARAM_ALPHA);       // overview tab
	$roleid     = optional_param('roleid', 0, PARAM_INT); // which role to show
	$modactivity  = optional_param('modactivity', 0, PARAM_INT);       // overview tab
	$tmcriteria  = optional_param('tmcriteria', '', PARAM_ALPHA);       // overview tab
	$modaction  = optional_param('modaction', '', PARAM_ALPHA);       // overview tab
	$getreport    = optional_param('getreport', FALSE, PARAM_BOOL);  // if get report clicked
	
	$day  = optional_param('day', 0, PARAM_INT);       // overview tab
	$month  = optional_param('month', 0, PARAM_INT);       // overview tab
	$year  = optional_param('year', 0, PARAM_INT);       // overview tab
 
	$page       = optional_param('page', 0, PARAM_INT);                     // which page to show
	$perpage    = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page

	if (!$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST)) {
		print_error('invalidcourse');
	}
	
    // update of v2.6
	//$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
	
	// getting context
	if (class_exists('context_course')) {
		$context = context_course::instance($course->id);
	} else {
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		
	}
	// checking role
	if ($roleid != 0 and !$role = $DB->get_record('role', array('id'=>$roleid))) {
		print_error('invalidrole');
	}
	
	// Check user is logged in and 
	// capable of seeing this page(only lecturers/cordinators can access)
	require_login($course, false);
	require_capability('block/usp_ews:overview', $context);
	require_capability('moodle/course:viewparticipants', $context);

	// Set up page parameters and the navigation links
	$PAGE->set_course($course);
			
	// base url for the interaction activity
	$url = new moodle_url('/blocks/usp_ews/report/activitycustom/onlineinteract_report.php', array('inst_id'=>$inst_id, 'cid' => $courseid));
	if ($roleid !== 0) $url->param('roleid');
	if ($modactivity !== 0) $url->param('modactivity');
	if ($page !== 0) $url->param('page');
	if ($perpage !== DEFAULT_PAGE_SIZE) $url->param('perpage');
	// setting the url
	$PAGE->set_url($url);
	// layout page type
	$PAGE->set_pagelayout('admin');

	$PAGE->set_context($context);
	$strblockname = get_string("config_default_title", "block_usp_ews");
	$title = get_string('overview', 'block_usp_ews');
	$PAGE->set_title($title);
	
	// Start page output
	// prints the heading and subheading of the page
	$PAGE->set_heading($course->fullname);
	$PAGE->navbar->add($title);
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
	
	// has capability to select checkbox
    //$bulkoperations = has_capability('moodle/course:bulkmessaging', $context);

	// Prepare the list of action options.
    $actions = array(
        'all' => get_string('allchanges'),
        'view' => get_string('viewed', 'block_usp_ews'),
        'post' => get_string('post_attempt', 'block_usp_ews'),
        'no' => get_string('no_particpated', 'block_usp_ews')
    );
	// criteria for time of the action
	// either before, after or on the date
	$timecriteria = array(
        'before' => get_string('before', 'block_usp_ews'),
        'on' => get_string('on', 'block_usp_ews'),
        'after' => get_string('after', 'block_usp_ews')
    );
	
	// getting information of the modules
	$modinfo = get_fast_modinfo($course);
	// array to list the activities in the course according to the sections
	$instanceoptions = array();

	$section = 0;
	//$sections = get_all_sections($course->id);
	// updated from V2.6
	$sections = $modinfo->get_section_info_all();
	$sectionoptions = '';
	// filling the array of activities in the list
	 foreach ($modinfo->sections as $section=>$cmids) {
		if($sections[$section]->visible == 1){
			$sectionoptions= "-- ". get_section_name($course, $sections[$section])." --";
			$instances = array();
			foreach ($cmids as $cmid) {
			
				$cm = $modinfo->cms[$cmid];
				// Skip modules such as label which do not actually have links;
				// this means there's nothing to participate in
				if (!$cm->has_view()) {
					continue;
				}
				
				$cmname = $cm->name;
				if (textlib::strlen($cmname) > 55) {
					$cmname = textlib::substr($cmname, 0, 50)."...";
				}
				$instances[$cm->id] = format_string($cmname);				
			}
			if (count($instances) == 0) {
				continue;
			}

		  $instanceoptions[] = array($sectionoptions=>$instances);
		}
	 }

	 // getting the type of user in array
	 $roleoptions = array();
	// TODO: we need a new list of roles that are visible here
	if ($roles = get_roles_used_in_context($context)) {
		arsort($roles);
		foreach ($roles as $r) {
			$roleoptions[$r->id] = $r->name;
		}
	}

	$guestrole = get_guest_role();
	if (empty($roleoptions[$guestrole->id])) {
			$roleoptions[$guestrole->id] = $guestrole->name;
	}

	$roleoptions = role_fix_names($roleoptions, $context);

	echo "<form class=\"logselectform\" action=\"$CFG->wwwroot/blocks/usp_ews/report/activitycustom/onlineinteract_report.php\" method=\"get\">\n";
    echo "<div>\n";//invisible fieldset here breaks wrapping
    echo "<input type=\"hidden\" name=\"cid\" value=\"$course->id\" />\n";
    echo "<input type=\"hidden\" name=\"inst_id\" value=\"$inst_id\" />\n";
	echo "<input type=\"hidden\" name=\"getreport\" value=\"true\" />\n";
	
	// listing the type of activity
	echo html_writer::label(get_string('activity'), 'activity');
	echo html_writer::select($instanceoptions, 'modactivity', $modactivity, get_string('chooseactivity', 'block_usp_ews'));	
	// listing what actions can be taken
	echo html_writer::label(get_string('action'), 'action');
	echo html_writer::select($actions, 'modaction', $modaction, false) . '<br/>';	
	// listing the time criterias
	echo html_writer::label(get_string('monitored_criteria', 'block_usp_ews'), 'criteria');
	echo html_writer::select($timecriteria, 'tmcriteria', $tmcriteria) . '<br/>';
	
	$datefrom = time();
	$selectors = html_writer::select_time('days', 'day', $datefrom)
               . html_writer::select_time('months', 'month', $datefrom)
               . html_writer::select_time('years', 'year', $datefrom);
			   
	echo html_writer::label('Date: ', 'date');
    echo $selectors . '<br/>';
	
	echo '<label for="menuroleid">'.get_string('showonly').'</label>'."\n";
	echo html_writer::select($roleoptions,'roleid',$roleid, false); 
	// submit button to get result
	echo '<input type="submit" class="getreport_btn" value="' . get_string('get_report', 'block_usp_ews') . '" />';
	
    echo '</div>';
    echo '</form>';

	if($getreport){
		$baseurl =  $CFG->wwwroot.'/blocks/usp_ews/report/activitycustom/onlineinteract_report.php?cid='.$course->id.'&amp;roleid='
		.$roleid.'&amp;inst_id='.$inst_id.'&amp;contextid='.$context->id .'&amp;modactivity='.$modactivity .'&amp;tmcriteria='.$tmcriteria .'&amp;modaction='.$modaction . '&amp;day='.$day. '&amp;month='.$month. '&amp;year='.$year. '&amp;perpage='.$perpage;

			// Define a table showing a list of users in the current role selection
		$tablecolumns = array();
		$tableheaders = array();
		
		// if has capability to select checkbox
		// defines table column for select option
		// and puts its heading in tableheaders array
		// defines table column for select option
		// and puts its heading in tableheaders array
		$tablecolumns[] = 'select';
		$tableheaders[] = get_string('select');

		if(empty($modactivity) || $modactivity == 0){
			$tablecolumns[] = 'activityname';
			$tableheaders[] = get_string('activity', 'block_usp_ews');
		}
		
		if($modaction != 'no'){
			$tablecolumns[] = 'activityaction';
			$tableheaders[] = get_string('config_header_action', 'block_usp_ews');
		}
		
		$tablecolumns[] = 'idnumber';
		$tableheaders[] = get_string('idnumber');
		
		$tablecolumns[] = 'fullname';
		$tableheaders[] = get_string('user');
			
		if (!isset($hiddenfields['lastaccess'])) {
			// defines table column for lastaccess
			// and puts its heading in tableheaders array
			$tablecolumns[] = 'lastaccess';
			$tableheaders[] = get_string('lastaccess');
		}

		$tablecolumns[] = 'completedactivity';
		$tableheaders[] = get_string('completed', 'block_usp_ews');
		
		// calls for flexible table class
		// defines column and its heading using array filled above
		// defines base url for table
		$table = new flexible_table('block-usp_ews-dg-'.$course->id);
		$table->define_columns($tablecolumns);
		$table->define_headers($tableheaders);
		$table->define_baseurl($baseurl);

		// allows following sorting column options
		$table->sortable(true,'lastname','ASC');
		$table->no_sorting('select');
		$table->no_sorting('activityname');
		if($modaction != 'no')
			$table->no_sorting('activityaction');
		
		// adding table attributes
		$table->set_attribute('cellspacing', '0');
		$table->set_attribute('id', 'usp_ews_online_overall_report');
		$table->set_attribute('class', 'generaltable generalbox');

		// adding css properties to the table
		//$table->column_style_all('padding', '5px 10px');
		$table->column_style_all('text-align', 'left');
		$table->column_style_all('vertical-align', 'middle');
		$table->column_style('fullname', 'width', '0');
		$table->column_style('idnumber', 'width', '0');
		$table->column_style('completedactivity', 'text-align', 'center');
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

		// building the sql
		// haviving conditions in whereas part
		$whereas = '';
		// if no specific activity selected then show for all actiivities with cmid != 0
		$cmidselected = false;
		// if no selected activity
		if($modactivity != 0){
			$cmidselected = true;
			$cm = $modinfo->cms[$modactivity];
			$cmname = $cm->name;
			$whereas .= " AND cmid = $modactivity";
		}else{
			$whereas .= " AND cmid != 0";
		}
		// which time of the year
		$time =  make_timestamp($year, $month, $day);
		// what action selected
		if($modaction == 'view'){
			 $whereas .= " AND action LIKE 'view%'";
		}else if($modaction == 'post'){
			$whereas .= " AND (action LIKE 'post%' OR action LIKE 'submit%' OR action LIKE 'add%')";
		}else if($modaction == 'no'){
			$modaction = 'no';
		}else{
			$modaction = 'all';
		}
		// time criteria
		if($tmcriteria == 'before'){
			 $whereas .= " AND time < $time";
		}else if($tmcriteria == 'on'){
			$beginOfday = strtotime("midnight", $time);
			$endOfday   = strtotime("tomorrow", $beginOfday) - 1;
			$whereas .= " AND time > $beginOfday AND time < $endOfday";
		}else if($tmcriteria == 'after'){
			$whereas .= " AND time > $time";
		}else{
			$tmcriteria_rs = '';
		}
		// updated v2.6
		$relatedcxtarray = $context->get_parent_context_ids(true);
		
		$relatedcxtstring = implode(",", $relatedcxtarray);
		$relatedcontexts = 'IN (' . $relatedcxtstring . ') ';

		// details of the users
		$extrauserfields = get_extra_user_fields_sql($context, 'u', '', array(
				'id', 'username', 'firstname', 'lastname', 'idnumber','email', 'city', 'country',
				'picture', 'lang', 'timezone', 'maildisplay', 'imagealt', 'lastaccess'));

		$mainuserfields = user_picture::fields('u', array('username', 'idnumber','email', 'city', 'country', 'lang', 'timezone', 'maildisplay'));

		// if action selected, not interacted
		if($modaction != 'no'){
			$sql = "SELECT CONCAT(l.id,u.id), $mainuserfields, $extrauserfields l.cmid, ra.userid, l.action, l.lastview, l.actioncount AS numview
					FROM (SELECT *, MAX(TIME) AS lastview, COUNT(id) AS actioncount FROM {log} WHERE course = :courseid $whereas GROUP BY userid, cmid, ACTION ) l 
					JOIN (
						SELECT * FROM {role_assignments} WHERE contextid $relatedcontexts AND roleid = :roleid
					) ra ON l.userid = ra.userid
					LEFT JOIN {user} u ON u.id = ra.userid";
					
		   $countsql = "SELECT COUNT(*)
					FROM (SELECT *, MAX(TIME) AS lastview, COUNT(id) AS actioncount FROM {log} WHERE course = :courseid $whereas GROUP BY userid, cmid, ACTION ) l 
					LEFT JOIN (
						SELECT * FROM {role_assignments} WHERE contextid $relatedcontexts AND roleid = :roleid
					) ra ON l.userid = ra.userid
					JOIN {user} u ON u.id = ra.userid";
					
		}else{
		// else what are the criterias field, generate accordingly
			$sql = "SELECT ra.userid, $mainuserfields, $extrauserfields l.cmid, l.action, l.lastview, IFNULL(l.actioncount, 0)
						FROM (SELECT * FROM {role_assignments} WHERE contextid $relatedcontexts AND roleid = :roleid ) ra
						JOIN {user} u ON u.id = ra.userid
					LEFT JOIN (
						SELECT *, MAX(TIME) AS lastview, COUNT(id) AS actioncount FROM {log} WHERE course = :courseid $whereas GROUP BY userid, cmid, ACTION
					) l ON l.userid = ra.userid
					WHERE l.actioncount IS NULL";
			
			$countsql = "SELECT COUNT(*)
						FROM (SELECT * FROM {role_assignments} WHERE contextid $relatedcontexts AND roleid = :roleid ) ra
						JOIN {user} u ON u.id = ra.userid
					LEFT JOIN (
						SELECT *, MAX(TIME) AS lastview, COUNT(id) AS actioncount FROM {log} WHERE course = :courseid $whereas GROUP BY userid, cmid, ACTION
					) l ON l.userid = ra.userid
					WHERE l.actioncount IS NULL";
		}			
		// parameters for the sql
		$params['courseid'] = $course->id;
		$params['roleid'] = $roleid;

		list($twhere, $tparams) = $table->get_sql_where();
		if ($twhere) {
			$sql .= ' WHERE '.$twhere; //initial bar
			$params = array_merge($params, $tparams);
		}

		if ($table->get_sql_sort()) {
			$sql .= ' ORDER BY '.$table->get_sql_sort();
		}

		$totalcount = $DB->count_records_sql($countsql, $params);

		if ($twhere) {
			$matchcount = $DB->count_records_sql($countsql.' AND '.$twhere, $params);
		} else {
			$matchcount = $totalcount;
		}
		// to get the details of the selected activity
		if($cmidselected){
			$cmid = $modactivity;
		
			if(isset($modinfo->cms[$cmid])){
			
				$cm = $modinfo->cms[$cmid];
				$cmname = $cm->name;
			}
		}else{
			$cmname = get_string('all_activity', 'block_usp_ews');
		}
		
		// display what activity selected
		$divoptions = array('id'=>'usp_ews_activity_report', 'style'=>'margin:20px; font-size: 12pt;');
		echo HTML_WRITER::start_tag('div', $divoptions);

		$poptions = array('id'=>'usp_ews_activity', 'style'=>'margin:0; padding:0;');
		echo HTML_WRITER::start_tag('p', $poptions) . get_string('selected_activity', 'block_usp_ews') . $cmname ;
		echo HTML_WRITER::end_tag('p') . HTML_WRITER::end_tag('div');

		$table->initialbars($totalcount > $perpage);
		$table->pagesize($perpage, $matchcount);

		if (!$users = $DB->get_records_sql($sql, $params, $table->get_page_start(), $table->get_page_size())) {
			$users = array(); // tablelib will handle saying 'Nothing to display' for us.
		}

		// if some reult the allow to generate report
		if($totalcount >= 1){
			$parameters = array('roleid'=>$roleid, 'cid' => $course->id, 'modactivity'=> $modactivity, 'time'=> $time, 'tmcriteria'=> $tmcriteria, 'modaction'=> $modaction);
			$url = new moodle_url('/blocks/usp_ews/report/activitycustom/xls_report.php', $parameters);
			$label = get_string('excel', 'block_usp_ews');
			echo $OUTPUT->single_button($url, $label);
		}
		// table data to be added here
		$data = array();

		$timeformat = get_string('date_format_activity', 'block_usp_ews');

		echo HTML_WRITER::start_tag('h2') . $totalcount . get_string('total_records', 'block_usp_ews') . HTML_WRITER::end_tag('h2');

		
	   // echo '<form action="../../../notification/action_redir.php" method="post" id="studentsform">'."\n";
		echo '<form action="'. $CFG->wwwroot.'/blocks/usp_ews/notification/action_redir.php' . '" method="post" id="studentsform">'."\n";
		echo '<div>'."\n";
		echo '<input type="hidden" name="id" value="'. $course->id .'" />'."\n";
		echo '<input type="hidden" name="inst_id" value="'. $inst_id .'" />'."\n";
		echo '<input type="hidden" name="returnto" value="'. s($PAGE->url) .'" />'."\n";
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />'."\n";

		// displaying the users and the results
		foreach($users as $rs){
			$data = array();
		
				$data[] = '<input type="checkbox" class="usercheckbox" name="user'.$rs->userid .'"/>';

				if(!$cmidselected){
					$cmid = $rs->cmid;
					if(isset($modinfo->cms[$cmid])){
						$cm = $modinfo->cms[$cmid];
						$cmname = $cm->name;
					}
					$data[] = $cmname;
				}
				// what action selected
				if($modaction != 'no'){
					$data[] = $rs->action;	
				}
				// user idnumber
				$data[] = $rs->idnumber;	
				// user name				
				$data[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$rs->userid.'&amp;course='.$course->id.'">'. fullname($rs, has_capability('moodle/site:viewfullnames', $context)) .'</a>';	
				// last access of the activity
				if($modaction != 'no'){
					$data[] = userdate($rs->lastview, $timeformat);		
					$data[] = get_string('yes') . "(" . $rs->numview . ")";
				}else{
					$data[] = get_string('never', 'block_usp_ews');		
					$data[] = get_string('no');		
				}
				$table->add_data($data);
		}

		// printing the table
		$table->print_html();

		if ($perpage == SHOW_ALL_PAGE_SIZE) {
			echo '<div id="showall"><a href="'.$baseurl.'&amp;perpage='.DEFAULT_PAGE_SIZE.'">'.get_string('showperpage', '', DEFAULT_PAGE_SIZE).'</a></div>'."\n";
		}
		else if ($matchcount > 0 && $perpage < $matchcount) {
			echo '<div id="showall"><a href="'.$baseurl.'&amp;perpage='.SHOW_ALL_PAGE_SIZE.'">'.get_string('showall', '', $matchcount).'</a></div>'."\n";
		}
		
		// selecting users for emailing
		echo '<div class="selectbuttons" style="text-align: center; margin:10px;">';
		echo '<input type="button" id="checkall" value="'.get_string('selectall').'" /> '."\n";
		echo '<input type="button" id="checknone" value="'.get_string('deselectall').'" /> '."\n";
		echo '</div>';
		
		echo '<div style="text-align: center;">';
		echo '<label for="formactionselect">'.get_string('withselectedusers').'</label>';
		$displaylist['messageselect.php'] = get_string('messageselectadd');
		echo html_writer::select($displaylist, 'formaction', '', array(''=>'choosedots'), array('id'=>'formactionselect'));
		echo $OUTPUT->help_icon('withselectedusers');
		echo '<input type="submit" value="' . get_string('ok') . '" />'."\n";
		echo '</div>';
		
		echo '</div>'."\n";
		echo '</form>'."\n";
		
		$jsmodule = array(
			'name' => 'block_usp_ews',
			'fullpath' => '/report/participation/module.js',
			'requires' => array()
		);
		$arguments = array();
		$PAGE->requires->js_init_call('M.report_participation.init', null, false, $jsmodule);
	}
	echo $OUTPUT->container_end();
	echo $OUTPUT->footer();