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
	require_once $CFG->dirroot.'/grade/export/lib.php';

	//require_once('grade_custom_form.php');
	
	define('DEFAULT_PAGE_SIZE', 10);
	define('SHOW_ALL_PAGE_SIZE', 5000);

	
	// Gather form data
    $contextid    = optional_param('contextid', 0, PARAM_INT);                // context id
    $courseid     = required_param('cid', PARAM_INT);                         // required course id
    $inst_id      = required_param('inst_id', PARAM_INT);                          // required instance id of the block   
	$modetab      = optional_param('modetab', 'coursework_report', PARAM_ALPHA);       // overview tab

	$gradeid = optional_param('gradeitemid', 0, PARAM_INT); // instance we're looking at.
	$selectedgrade  = optional_param('selectedgrade', 0, PARAM_INT);       // overview tab
	$gradecriteria  = optional_param('gradecriteria', 0, PARAM_INT);       // overview tab
	$page       = optional_param('page', 0, PARAM_INT);                     // which page to show
	$perpage    = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page
	
	$getreport    = optional_param('getreport', FALSE, PARAM_BOOL);  // how many per page
	
	$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
    // update of v2.6
	//$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
	
	if (class_exists('context_course')) {
		$context = context_course::instance($course->id);
	} else {
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		
	}
	
	// Check user is logged in and 
	// capable of seeing this page(only lecturers/cordinators can access)
	require_login($course, false);
	require_capability('block/usp_ews:overview', $context);
	require_capability('moodle/course:viewparticipants', $context);

	// Set up page parameters and the navigation links
	$PAGE->set_course($course);

	$url = new moodle_url('/blocks/usp_ews/report/gradecustom/coursework_report.php', array('inst_id'=>$inst_id, 'cid' => $courseid));

	if ($gradeid !== 0) $url->param('gradeid');
	if ($page !== 0) $url->param('page');
	if ($perpage !== DEFAULT_PAGE_SIZE) $url->param('perpage');
	
	$PAGE->set_url($url);

	$PAGE->set_context($context);
	$strblockname = get_string("config_default_title", "block_usp_ews");
	$title = get_string('overview', 'block_usp_ews');
	$PAGE->set_title($title);
	
	// Start page output
	// prints the heading and subheading of the page
	$PAGE->set_heading($course->fullname);
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
	
	$gradesoptions = array();
	$gradesoptions[] = get_string('choosegradeitem', 'block_usp_ews');
	
	$switch = grade_get_setting($course->id, 'aggregationposition', $CFG->grade_aggregationposition);
	// Grab the grade_seq for this course
    $gseq = new grade_seq($course->id, $switch);
	
	// getting only the activities which are graded
	// putting the list in array for display in dropdown menu
	if ($grade_items = $gseq->items) {
            $canviewhidden = has_capability('moodle/grade:viewhidden', context_course::instance($course->id));
			
			$gradeitem = array();
            foreach ($grade_items as $grade_item) {

                // Is the grade_item hidden? If so, can the user see hidden grade_items?
                if ($grade_item->is_hidden() && !$canviewhidden) {
                    continue;
                }
				
				$gradeitem[$grade_item->id] = $grade_item->get_name();
            }
			$gradesoptions[] = array($course->fullname=>$gradeitem);
     }
	
	$html_url = "coursework_report.php?inst_id=$inst_id&cid=$course->id";
	echo $OUTPUT->container_start('usp_ewsoverviewmenus');
	echo '&nbsp;'.get_string('select_gradeitem', 'block_usp_ews') . ':&nbsp;';
	echo $OUTPUT->single_select($html_url, 'gradeitemid', $gradesoptions, $gradeid, null, 'gradeitemform');
	echo $OUTPUT->container_end();

	// if a activity selected
	if($gradeid != 0){
		// action to be taken on the activity
		$actionscriteria = array(
			'0' => get_string('lessthan', 'block_usp_ews'),
			'1' => get_string('lessthanequal', 'block_usp_ews'),
			'2' => get_string('equalto', 'block_usp_ews'),
			'3' => get_string('greaterthan', 'block_usp_ews'),
			'4' => get_string('greaterthanequal', 'block_usp_ews'),
		);
		// what is the max grade of the activity
		$maxgrade = intval($gseq->items[$gradeid]->grademax);
		$name = $gseq->items[$gradeid]->get_name();
		// putting the grade range in the dropdown menu
		$graderange = array();
		for($i=0; $i<=$maxgrade; $i++){
			$graderange[$i] = $i;
		}
		// displaying the grade range
		$option_param = array('style'=> 'padding: 1px 0px; font-weight: bold;');
		echo HTML_WRITER::start_tag('p', $option_param);
		echo get_string('select_gradeitem_is', 'block_usp_ews') . $name . get_string('select_grade_outof', 'block_usp_ews') . $maxgrade;
		echo HTML_WRITER::end_tag('p');
		
		echo "<form class=\"gradeselectform\" action=\"$CFG->wwwroot/blocks/usp_ews/report/gradecustom/coursework_report.php\" method=\"get\">\n";
		echo "<div>\n";//invisible fieldset here breaks wrapping
		echo "<input type=\"hidden\" name=\"cid\" value=\"$course->id\" />\n";
		echo "<input type=\"hidden\" name=\"inst_id\" value=\"$inst_id\" />\n";
		echo "<input type=\"hidden\" name=\"gradeitemid\" value=\"$gradeid\" />\n";
		echo "<input type=\"hidden\" name=\"getreport\" value=\"true\" />\n";
		
		echo html_writer::label(get_string('monitored_criteria', 'block_usp_ews'), 'criteria');
		echo html_writer::select($actionscriteria, 'gradecriteria', $gradecriteria, false) ;	

		echo html_writer::label(get_string('grade'), 'grade');
		echo html_writer::select($graderange, 'selectedgrade', $selectedgrade, false);
	
	    // get report button
		echo '<input type="submit" class="getreport_btn" value="' . get_string('get_report', 'block_usp_ews') . '" />';
	
		echo '</div>';
		echo '</form>';
	
		// if the get report button selected
		if($getreport){

			$baseurl =  $CFG->wwwroot.'/blocks/usp_ews/report/gradecustom/coursework_report.php?cid='.$course->id.'&amp;inst_id='.$inst_id.'&amp;selectedgrade='.$selectedgrade. '&amp;gradecriteria='.$gradecriteria . '&amp;perpage='.$perpage . '&amp;gradeid='.$gradeid;	

			$tablecolumns = array();
			$tableheaders = array();

			// defines table column for select option
			// and puts its heading in tableheaders array
			$tablecolumns[] = 'select';
			$tableheaders[] = get_string('select');
			
			// column for idnumber
			$tablecolumns[] = 'idnumber';
			$tableheaders[] = get_string('idnumber');
			
			// column for name
			$tablecolumns[] = 'fullname';
			$tableheaders[] = get_string('user');
				
			// column for grade attained
			$tablecolumns[] = 'grade';
			$tableheaders[] = get_string('grade');
			
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

			// building sql according to the criteria and selected mark
			switch ($gradecriteria)
			{
			case 0:
			  $gradesql = "AND finalgrade < $selectedgrade";
			  break;
			case 1:
			  $gradesql = "AND finalgrade <= $selectedgrade";
			  break;
			case 2:
			  $gradesql = "AND finalgrade = $selectedgrade";
			  break;
			case 3:
			  $gradesql = "AND finalgrade > $selectedgrade";
			  break;
			case 4:
			  $gradesql = "AND finalgrade >= $selectedgrade";
			  break;
			default:
			  $gradesql = "";
			} 
			
			$extrauserfields = get_extra_user_fields_sql($context, 'u', '', array(
					'id', 'username', 'firstname', 'lastname', 'idnumber','email', 'city', 'country',
					'picture', 'lang', 'timezone', 'maildisplay', 'imagealt', 'lastaccess'));

			$mainuserfields = user_picture::fields('u', array('username', 'idnumber','email', 'city', 'country', 'lang', 'timezone', 'maildisplay'));

	
			//its a simple sql, will change
			$sql = "SELECT $mainuserfields, $extrauserfields g.finalgrade
					FROM (SELECT userid, finalgrade FROM mdl_grade_grades WHERE itemid = $gradeid $gradesql) g 
					 JOIN mdl_user u ON u.id = g.userid";
					
		   $countsql = "SELECT COUNT(*) 
						FROM (SELECT userid, finalgrade FROM mdl_grade_grades WHERE itemid = $gradeid $gradesql) AS count";
					
				
			//$params['courseid'] = $course->id;
			//$params['roleid'] = 5;

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

			$table->initialbars($totalcount > $perpage);
			$table->pagesize($perpage, $matchcount);

			if (!$users = $DB->get_records_sql($sql, $params, $table->get_page_start(), $table->get_page_size())) {
				$users = array(); // tablelib will handle saying 'Nothing to display' for us.
			}
	

			// if some reult the allow to generate report
			if($totalcount >= 1){
				$parameters = array('cid' => $course->id, 'gradecriteria'=> $gradecriteria, 'gradeitemid'=> $gradeid, 'selectedgrade'=>$selectedgrade, 'name'=>$name, 'maxgrade'=>$maxgrade);
				$url = new moodle_url('/blocks/usp_ews/report/gradecustom/xls_report.php', $parameters);
				$label = get_string('excel', 'block_usp_ews');
				echo $OUTPUT->single_button($url, $label);
			}
		
			// table data to be added here
			$data = array();
			
			echo HTML_WRITER::start_tag('h2') . $totalcount . get_string('total_records', 'block_usp_ews') . HTML_WRITER::end_tag('h2');
			
			echo '<form action="'. $CFG->wwwroot.'/blocks/usp_ews/notification/action_redir.php' . '" method="post" id="studentsform">'."\n";
			
			echo '<div>'."\n";
			echo '<input type="hidden" name="id" value="'.$course->id.'" />'."\n";
			echo '<input type="hidden" name="inst_id" value="'.$inst_id.'" />'."\n";
			echo '<input type="hidden" name="returnto" value="'. s($PAGE->url) .'" />'."\n";
			echo '<input type="hidden" name="reporttype" value="backtogradereport" />'."\n";
			echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />'."\n";

			// for each user fill the data
			foreach($users as $rs){
				$data = array();
				// checkbox option
				$data[] = '<input type="checkbox" class="usercheckbox" name="user'.$rs->id .'"/>';
				// idnumber column
				$data[] = $rs->idnumber;		
				$data[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$rs->id.'&amp;course='.$course->id.'">'.fullname($rs,true).'</a>';			
				// grade attained column
				$data[] = usp_ews_format_number($rs->finalgrade);			
				// putting in the table
				$table->add_data($data);

			}
			
			// printing the table
			$table->print_html();

			if ($perpage == SHOW_ALL_PAGE_SIZE) {
				echo '<div id="showall"><a href="'. $baseurl .'&amp;perpage='.DEFAULT_PAGE_SIZE.'">'.get_string('showperpage', '', DEFAULT_PAGE_SIZE).'</a></div>'."\n";
			}
			else if ($matchcount > 0 && $perpage < $matchcount) {
				echo '<div id="showall"><a href="'. $baseurl .'&amp;perpage='.SHOW_ALL_PAGE_SIZE.'">'.get_string('showall', '', $matchcount).'</a></div>'."\n";
			}
			
			// selcting the user for emailing
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
	}
	 echo $OUTPUT->container_end();
	 echo $OUTPUT->footer();