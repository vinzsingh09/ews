<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir.'/formslib.php';

class studentid_import_form extends moodleform {
    function definition (){
        global $COURSE;

        $mform =& $this->_form;

        if (isset($this->_customdata)) {  // hardcoding plugin names here is hacky
            $features = $this->_customdata;
        } else {
            $features = array();
        }

        // course id needs to be passed for auth purposes
        $mform->addElement('hidden', 'cid', optional_param('cid', 0, PARAM_INT));
        $mform->setType('cid', PARAM_INT);

        // inst_id needs to be passed for auth purposes
        $mform->addElement('hidden', 'inst_id', optional_param('inst_id', 0, PARAM_INT));
        $mform->setType('inst_id', PARAM_INT);
		
        $mform->addElement('header', 'general', get_string('importfile', 'block_usp_ews'));
        // file upload
        $mform->addElement('filepicker', 'usp_ews_csvfile', get_string('file'));
        $mform->addRule('usp_ews_csvfile', null, 'required');
        $encodings = textlib::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'block_usp_ews'), $encodings);

        if (!empty($features['includeseparator'])) {
            $radio = array();
            $radio[] = $mform->createElement('radio', 'separator', null, get_string('septab', 'block_usp_ews'), 'tab');
            $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepcomma', 'block_usp_ews'), 'comma');
            $mform->addGroup($radio, 'separator', get_string('separator', 'block_usp_ews'), ' ', false);
            $mform->setDefault('separator', 'comma');
        }
        $this->add_action_buttons(false, get_string('uploadcsvfile', 'block_usp_ews'));
    }
}

