<?php  // $Id: tabs.php,v 1.43.2.8 2008/12/19 01:44:48 moodler Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set

/*
 * This script is used to add tabs for the usp_ews
**/

    $tabs = $row = $toprow = $inactive = $activited = array();

	// remove the other tabs from the lecturer
	if(!has_capability('block/usp_ews:overview', $context)){
	
		$toprow[] = new tabobject('mystats', new moodle_url('/blocks/usp_ews/mydashboard.php', array('inst_id' => $inst_id, 'cid' => $course->id)), get_string('mystats','block_usp_ews'));

		$url_mycompletion = new moodle_url('/blocks/usp_ews/mydashboard.php', array('inst_id' => $inst_id, 'cid' => $course->id, 'modetab'=>'mycom'));
		$url_myinteraction = new moodle_url('/blocks/usp_ews/mydashboard.php', array('inst_id' => $inst_id, 'cid' => $course->id, 'modetab'=>'myint'));
		$url_aboutus = new moodle_url('/blocks/usp_ews/mydashboard.php', array('inst_id' => $inst_id, 'cid' => $course->id, 'modetab'=>'aboutus'));
	
		// my dashboard
		$row[] = new tabobject('mycom', $url_mycompletion, get_string('mycom','block_usp_ews'));
		// participation
		$row[] = new tabobject('myint', $url_myinteraction, get_string('myint','block_usp_ews'));
		// about us page
		$row[] = new tabobject('aboutus', $url_aboutus, get_string('aboutus','block_usp_ews'));
		
		if (in_array($currenttab, array('mycom', 'myint' , 'aboutus'))) {
			$inactive[] = 'mystats';
			$activited[] = 'mystats';               
		}           
    }
	// top tab, overviewallstudents tab
	// only course cordinator, lecturer and tutors to see this tab
	// students are restricted from this tab
	if ( has_capability('block/usp_ews:overview', $context) ) {
	
		$params = array('inst_id' => $inst_id, 'cid' => $course->id);
		$urloverview = new moodle_url('/blocks/usp_ews/overviewallstudents.php', $params);
		$urlfilter = new moodle_url('/blocks/usp_ews/report/csv/index.php', $params);
		
		$urlonlineinteract_report = new moodle_url('/blocks/usp_ews/report/activitycustom/onlineinteract_report.php', $params);
		$urlcoursework_report = new moodle_url('/blocks/usp_ews/report/gradecustom/coursework_report.php', $params);
		
		$toprow[] = new tabobject('allstats', $urloverview, get_string('allstats','block_usp_ews'));
		// overviewallstudent tab
		$row[] = new tabobject('overview', $urloverview, get_string('overview','block_usp_ews'));    
		// filtered report tab
		$row[] = new tabobject('filter_report', $urlfilter, get_string('filter_report','block_usp_ews'));
			
		if (in_array($currenttab, array('overview', 'filter_report'))) {
			$inactive[] = 'allstats';
			$activited[] = 'allstats';
			//$row = array();
		}
		
		// Custom report
		$toprow[] = new tabobject('customreport', $urlonlineinteract_report, get_string('customtabtitle','block_usp_ews'));
		
		if (in_array($currenttab, array('onlineinteract_report', 'coursework_report'))) {
			$inactive[] = 'customreport';
			$activited[] = 'customreport';    
			$row = array();
			// activity custom report
			$row[] = new tabobject('onlineinteract_report', $urlonlineinteract_report, get_string('customonlineinteract','block_usp_ews'));    
			// grade custom report
			$row[] = new tabobject('coursework_report', $urlcoursework_report, get_string('myach','block_usp_ews'));			
		}
	}
	
	// Add second row to display if there is one
    if (!empty($row)) {
        $tabs = array($toprow, $row);
    } else {
	// else only the top row
        $tabs = array($toprow);
    }

    /// Print out the tabs and continue!
    print_tabs($tabs, $currenttab, $inactive, $activited);
