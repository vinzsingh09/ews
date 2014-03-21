<?php
// from user folder of moodle
/**
 * This file is part of the block usp_ews section Moodle
 *
 * @package usp_ews
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot .'/notes/lib.php');

$id    = required_param('id', PARAM_INT);              // course id
$inst_id = required_param('inst_id',PARAM_INT);        //instance id of the block
$users = optional_param_array('userid', array(), PARAM_INT); // array of user id
$content = optional_param('content', '', PARAM_RAW); // note content
$state = optional_param('state', '', PARAM_ALPHA); // note publish state

$url = new moodle_url('/blocks/usp_ews/groupaddnote.php', array('id'=>$id, 'inst_id' => $inst_id));
if ($content !== '') {
    $url->param('content', $content);
}
if ($state !== '') {
    $url->param('state', $state);
}
$PAGE->set_url($url);

if (! $course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

$context = context_course::instance($course->id, MUST_EXIST);
require_login($course->id);

// to create notes the current user needs a capability
require_capability('moodle/notes:manage', $context);

if (empty($CFG->enablenotes)) {
    print_error('notesdisabled', 'notes');
}

if (!empty($users) && !empty($content) && confirm_sesskey()) {
    $note = new stdClass();
    $note->courseid = $id;
    $note->format = FORMAT_PLAIN;
    $note->content = $content;
    $note->publishstate = $state;
    foreach ($users as $k => $v) {
        if(!$user = $DB->get_record('user', array('id'=>$v))) {
            continue;
        }
        $note->id = 0;
        $note->userid = $v;
        if (note_save($note)) {
            add_to_log($note->courseid, 'notes', 'add', 'index.php?course='.$note->courseid.'&amp;user='.$note->userid . '#note-' . $note->id , 'add note');
        }
    }

    redirect("../overviewallstudents.php?cid=$id&inst_id=$inst_id");
}

$straddnote = get_string('groupaddnewnote', 'notes');

$PAGE->navbar->add($straddnote);
$PAGE->set_title("$course->shortname: ".get_string('extendenrol'));
$PAGE->set_heading($course->fullname);

/// Print headers
echo $OUTPUT->header();

// this will contain all available the based On select options, but we'll disable some on them on a per user basis

echo $OUTPUT->heading($straddnote);
echo '<form method="post" action="groupaddnote.php" >';
echo '<div style="width:100%;text-align:center;">';
echo '<input type="hidden" name="id" value="'.$course->id.'" />';
echo '<input type="hidden" name="inst_id" value="'. $inst_id .'" />';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
$state_names = note_get_state_names();

// the first time list hack
if (empty($users) and $post = data_submitted()) {
    foreach ($post as $k => $v) {
        if (preg_match('/^user(\d+)$/',$k,$m)) {
            $users[] = $m[1];
        }
    }
}

$userlist = array();
foreach ($users as $k => $v) {
    if (!$user = $DB->get_record('user', array('id'=>$v))) {
        continue;
    }
    echo '<input type="hidden" name="userid['.$k.']" value="'.$v.'" />';
    $userlist[] = fullname($user, true);
}
echo '<p>';
echo get_string('users'). ': ' . implode(', ', $userlist) . '.';
echo '</p>';

echo '<p>' . get_string('content', 'notes');
echo '<br /><textarea name="content" rows="5" cols="50">' . strip_tags(@$content) . '</textarea></p>';

echo '<p>';
echo get_string('publishstate', 'notes');
echo $OUTPUT->help_icon('publishstate', 'notes');
echo html_writer::select($state_names, 'state', empty($state) ? NOTES_STATE_PUBLIC : $state, false);
echo '</p>';

echo '<input type="submit" value="' . get_string('savechanges'). '" /></div></form>';
echo $OUTPUT->footer();
