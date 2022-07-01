<?php
// This file is part of the GoToMeeting plugin for Moodle - http://moodle.org/
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

/**
 * GoToMeeting module form
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/gotomeeting/locallib.php');

class mod_gotomeeting_mod_form extends moodleform_mod {

    public function definition() {

        $mform = $this->_form;

        $licences = $this->get_gotomeeting_licence();
        if (!$licences) {
            throw new moodle_exception('licencerequired', 'gotomeeting', $link);
        }
        $mform->addElement('header', 'general', get_string('generalsetting', 'gotomeeting'));

        $mform->addElement('select', 'licence', get_string('licence', 'gotomeeting'), $licences);
        if (isset($this->get_current()->update)) {
            $mform->disabledIf('licence', null);
        } else {
            $mform->addRule('licence', get_string('licencerequired', 'gotomeeting'), 'required', '', 'client');
        }
        $mform->addElement('text', 'name', get_string('meetingname', 'gotomeeting'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('meetingnamerequired', 'gotomeeting'), 'required', '', 'server');

        $this->standard_intro_elements();

        $mform->addElement('header', 'meetingheader', get_string('meetingheader', 'gotomeeting'));

        $mform->addElement('date_time_selector', 'startdatetime', get_string('startdatetime', 'gotomeeting'));
        $mform->setDefault('startdatetime', time() + 300);
        $mform->addRule('startdatetime', get_string('startdatetimerequired', 'gotomeeting'), 'required', 'client');

        $mform->addElement('date_time_selector', 'enddatetime', get_string('enddatetime', 'gotomeeting'));

        $mform->setDefault('enddatetime', time() + 3900);
        $mform->addRule('enddatetime', get_string('enddatetimerequired', 'gotomeeting'), 'required', 'client');

        // Adding hidden items.
        $mform->addElement('hidden', 'meetingpublic', 1);
        $mform->setType('meetingpublic', PARAM_INT);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);
    }

    public function add_completion_rules() {
        $mform = & $this->_form;

        return array();
    }

    public function completion_rule_enabled($data) {
        return (!empty($data['completionparticipationenabled']) && $data['completionparticipation'] != 0);
    }

    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($data['startdatetime'] < time()) {
            $errors['startdatetime'] = get_string('future_startdatetime_required', 'gotomeeting');
        }
        if ($data['enddatetime'] < time()) {
            $errors['enddatetime'] = get_string('future_enddatetime_required', 'gotomeeting');
        }
        if ($data['startdatetime'] >= $data['enddatetime']) {
            $errors['enddatetime'] = 'End date time should be more that Start date time';
        }

        $course = get_course($data['course']);

        if ($course->format == 'weeks') {

            $dates = course_get_format($course)->get_section_dates($this->current->section);

            if (($data['startdatetime'] < $dates->start) || ($data['startdatetime'] > $dates->end)) {
                $errors['startdatetime'] = "Start date must be in the range of the course week";
            }

            if (($data['enddatetime'] < $dates->start) && ($data['enddatetime'] < $dates->end)) {
                $errors['enddatetime'] = "Start date must be in the range of the course week";
            }
        }

        if (!empty($data['completionunlocked']) && (!empty($data['completionparticipationenabled']))) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data['completion']) && $data['completion'] == COMPLETION_TRACKING_AUTOMATIC;
            if ($autocompletion && ($data['completionparticipation'] > 100 || $data['completionparticipation'] <= 0)) {
                $errors['completiongotomeetinggroup'] = 'Please enter a valid percentage value between 1 and 100';
            }
        }
        return $errors;
    }

    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionparticipationenabled) || !$autocompletion) {
                $data->completionparticipation = 0;
            }
        }
        return $data;
    }

    private function get_gotomeeting_licence() {
        global $DB;
        $licences = array();
        $gotomeetinglicences = $DB->get_records('gotomeeting_licence', null, 'email');
        foreach ($gotomeetinglicences as $gotomeetinglicence) {

            $licences[$gotomeetinglicence->id] = $gotomeetinglicence->email;
        }
        return $licences;
    }

}
