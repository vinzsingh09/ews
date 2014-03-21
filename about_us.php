<?php

	// Displays different views of the logs.

	// Include required files
	require_once(dirname(__FILE__) . '/../../config.php'); // moodle config file
	require_once($CFG->dirroot.'/blocks/usp_ews/lib.php'); // usp_ews library file
	require_once($CFG->dirroot.'/course/lib.php');

	
	// message at the begining of the page
	$inforoption = array('class' => 'usp_ews_info_message_aboutus');
	$contenthtml .= HTML_WRITER::start_tag('div', array('class' => 'usp_ews_message_content'));
	// EWS description
	$contenthtml .= HTML_WRITER::start_tag('p', $inforoption) . get_string('about_us_intro', 'block_usp_ews') . $end_p;

	// images of the dashboard and the flag
	$param_header_item = array('style' => 'font:16pt bold; margin: 10px 0px 3px; ');
	$contenthtml .= HTML_WRITER::start_tag('div', array('class' => 'usp_ews_dashboard_infor')) . HTML_WRITER::start_tag('p', $param_header_item) . get_string('about_us_indicator', 'block_usp_ews') . $end_p . $end_div;

	// summary of indictors message
	$contenthtml .= HTML_WRITER::start_tag('div', array('class' => 'usp_ews_dashboard_img')) . '<img id="usp_ews_dashboard_img" src="'. $OUTPUT->pix_url('dashboard', 'block_usp_ews') . '" alt="" />' . $end_div	;
	$contenthtml .= HTML_WRITER::start_tag('div') . get_string('about_us_indicator_message', 'block_usp_ews'). $end_div;

	// information about interaction graph
	$contenthtml .= HTML_WRITER::start_tag('div', array('style' => 'float: left;'))  
					. HTML_WRITER::start_tag('p', $param_header_item) . get_string('about_us_interacttitle', 'block_usp_ews') . $end_p 
					. $start_p . get_string('about_us_interactmsg', 'block_usp_ews') . $end_p . $end_div;
	$contenthtml .= HTML_WRITER::start_tag('div') . '<img id="usp_ews_partgraph_img" width="800" height="560" src="'. $OUTPUT->pix_url('participationgraph', 'block_usp_ews') . '" alt="" />' . $end_div	;

	// information on login trend graph
	$contenthtml .= HTML_WRITER::start_tag('div', array('style' => 'float: left;')) 
				. HTML_WRITER::start_tag('p', $param_header_item) . get_string('about_us_logintitle', 'block_usp_ews') . $end_p 
				. $start_p . get_string('about_us_loginmsg', 'block_usp_ews') . $end_p . $end_div;
	// login graph image
	$contenthtml .= HTML_WRITER::start_tag('div') . '<img id="usp_ews_partgraph_img" width="600" height="360" src="'. $OUTPUT->pix_url('logingraph', 'block_usp_ews') . '" alt="" />' . $end_div;
		
	$contenthtml .= $end_div;

	// display on the page			 
	echo $contenthtml;


