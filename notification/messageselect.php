<?php

// Sending message to users
// from user folder of moodle

/**
 * This file is part of the User section Moodle
 * @package usp_ews
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/message/lib.php');

//getting required parameters for functions and query

$id = required_param('id',PARAM_INT);
$messagebody = optional_param('messagebody','',PARAM_CLEANHTML);
$send = optional_param('send','',PARAM_BOOL);
$preview = optional_param('preview','',PARAM_BOOL);
$edit = optional_param('edit','',PARAM_BOOL);
$returnto = optional_param('returnto','',PARAM_LOCALURL);
$reporttype = optional_param('reporttype', 'backtooverviewall',PARAM_ALPHA);
$format = optional_param('format',FORMAT_MOODLE,PARAM_INT);
$deluser = optional_param('deluser',0,PARAM_INT);

$url = new moodle_url('/blocks/usp_ews/notification/messageselect.php', array('id'=>$id));
if ($messagebody !== '') {
    $url->param('messagebody', $messagebody);
}
if ($send !== '') {
    $url->param('send', $send);
}
if ($preview !== '') {
    $url->param('preview', $preview);
}
if ($edit !== '') {
    $url->param('edit', $edit);
}
if ($returnto !== '') {
    $url->param('returnto', $returnto);
}
if ($reporttype !== '') {
    $url->param('reporttype', $reporttype);
}
if ($format !== FORMAT_MOODLE) {
    $url->param('format', $format);
}
if ($deluser !== 0) {
    $url->param('deluser', $deluser);
}
$PAGE->set_url($url);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

require_login($course);

$coursecontext = context_course::instance($id);   // Course context
$systemcontext = context_system::instance();   // SYSTEM contextrequire_capability('moodle/course:bulkmessaging', $coursecontext);

if (empty($SESSION->emailto)) {
    $SESSION->emailto = array();
}
if (!array_key_exists($id,$SESSION->emailto)) {
    $SESSION->emailto[$id] = array();
}

if ($deluser) {
    if (array_key_exists($id,$SESSION->emailto) && array_key_exists($deluser,$SESSION->emailto[$id])) {
        unset($SESSION->emailto[$id][$deluser]);
    }
}

if (empty($SESSION->emailselect[$id]) || $messagebody) {
    $SESSION->emailselect[$id] = array('messagebody' => $messagebody);
}

$messagebody = $SESSION->emailselect[$id]['messagebody'];

$count = 0;

if ($data = data_submitted()) {
    require_sesskey();
    $namefields = get_all_user_name_fields(true);
    foreach ($data as $k => $v) {
        if (preg_match('/^(user|teacher)(\d+)$/',$k,$m)) {
            if (!array_key_exists($m[2],$SESSION->emailto[$id])) {
                if ($user = $DB->get_record_select('user', "id = ?", array($m[2]), 'id,
                        ' . $namefields . ',idnumber,email,mailformat,lastaccess, lang, maildisplay')) {
                    $SESSION->emailto[$id][$m[2]] = $user;
                    $count++;
                }
            }
        }
    }
}

$strtitle = get_string('coursemessage');

$link = null;
if (has_capability('moodle/course:viewparticipants', $coursecontext) || has_capability('moodle/site:viewparticipants', $systemcontext)) {
    $link = $returnto;
}

$strblockname = get_string("config_default_title", "block_usp_ews");
$title = get_string('overview', 'block_usp_ews');

$PAGE->navbar->add($strblockname, $link);
$PAGE->navbar->add($strtitle);
$PAGE->set_title($strtitle);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');
echo $OUTPUT->header();
// if messaging is disabled on site, we can still allow users with capabilities to send emails instead
if (empty($CFG->messaging)) {
    echo $OUTPUT->notification(get_string('messagingdisabled','message'));
}

if ($count) {
    if ($count == 1) {
        $heading = get_string('addedrecip','moodle',$count);
    } else {
        $heading = get_string('addedrecips','moodle',$count);
    }
    echo $OUTPUT->heading($heading);
}

if (!empty($messagebody) && !$edit && !$deluser && ($preview || $send)) {
    if (count($SESSION->emailto[$id])) {
        if (!empty($preview)) {
            echo '<form method="post" action="messageselect.php" style="margin: 0 20px;">
					<input type="hidden" name="returnto" value="'.s($returnto).'" />
					<input type="hidden" name="id" value="'. $id .'" />
					<input type="hidden" name="reporttype" value="'. $reporttype .'" />
					<input type="hidden" name="format" value="'.$format.'" />
					<input type="hidden" name="sesskey" value="' . sesskey() . '" />
					';
            echo "<h3>".get_string('previewhtml')."</h3><div class=\"messagepreview\">\n".format_text($messagebody,$format)."\n</div>\n";
            echo '<p align="center"><input type="submit" name="send" value="'.get_string('sendmessage', 'message').'" />'."\n";
            echo '<input type="submit" name="edit" value="'.get_string('update').'" /></p>';
            echo "\n</form>";
        } else if (!empty($send)) {
            $good = 1;
            foreach ($SESSION->emailto[$id] as $user) {
                $good = $good && message_post_message($USER,$user,$messagebody,$format);
            }
            if (!empty($good)) {
                echo $OUTPUT->heading(get_string('messagedselectedusers'));
                unset($SESSION->emailto[$id]);
                unset($SESSION->emailselect[$id]);
            } else {
                echo $OUTPUT->heading(get_string('messagedselectedusersfailed'));
            }

				echo '<p align="center"><a href="'. $returnto . '">' . get_string($reporttype, 'block_usp_ews') .'</a></p>';
			
        }
        echo $OUTPUT->footer();
        exit;
    } else {
        echo $OUTPUT->notification(get_string('nousersyet'));
    }
}


if ((!empty($send) || !empty($preview) || !empty($edit)) && (empty($messagebody))) {
    echo $OUTPUT->notification(get_string('allfieldsrequired'));
}

if (count($SESSION->emailto[$id])) {
    require_sesskey();
    require("message.html");
}

echo $OUTPUT->footer();


