<?php


/**
 * GoToMeeting module form
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot . '/course/moodleform_mod.php');
require_once ($CFG->dirroot . '/mod/gotomeeting/locallib.php');

class mod_gotomeeting_mod_form extends moodleform_mod {

    function definition() {

        $mform = $this->_form;
        $gotomeetingconfig = get_config('gotomeeting');
        $mform->addElement('header', 'general', get_string('generalsetting', 'gotomeeting'));
        // Adding a text element
        $mform->addElement('text', 'name', get_string('meetingname', 'gotomeeting'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('meetingnamerequired', 'gotomeeting'), 'required', '', 'server');
        // $this->standard_intro_elements(get_string('gotomeetingintro', 'gotomeeting'));
        // Adding a new text editor
        $this->add_intro_editor(true, get_string('gotomeetingintro', 'gotomeeting'));
        
        
        $mform->addElement('header', 'meetingheader', get_string('meetingheader', 'gotomeeting'));


       



        $mform->addElement('date_time_selector', 'startdatetime', get_string('startdatetime', 'gotomeeting'));
        $mform->setDefault('startdatetime', time() + 300);
        $mform->addRule('startdatetime', 'Occurs required', 'required', 'client');

        $mform->addElement('date_time_selector', 'enddatetime', get_string('enddatetime', 'gotomeeting'));

        $mform->setDefault('enddatetime', time() + 3900);
        $mform->addRule('enddatetime', 'Occurs required', 'required', 'client');

        // Adding hidden items
        $mform->addElement('hidden', 'meetingpublic', 1);
        $mform->setType('meetingpublic', PARAM_INT);

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons(true, false, null);
    }

    function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
    }

    function add_completion_rules() {
        $mform = & $this->_form;

       /* $group = array();
        $group[] = & $mform->createElement('checkbox', 'completionparticipationenabled', '', get_string('completiongotomeeting', 'mod_gotomeeting'));
        $group[] = & $mform->createElement('text', 'completionparticipation', '', array('size' => 3, 'value' => 50));
        $mform->setType('completionparticipation', PARAM_INT);
        $mform->addGroup($group, 'completiongotomeetinggroup', get_string('completiongotomeetinggroup', 'mod_gotomeeting'), array(' '), false);
        $mform->addHelpButton('completiongotomeetinggroup', 'completiongotomeetinggroup', 'gotomeeting');
        $mform->disabledIf('completionparticipationenabled', 'meetingtype', 'eq', 'gotomeeting');
        $mform->disabledIf('completionparticipation', 'meetingtype', 'eq', 'gotomeeting');
        $mform->disabledIf('completionparticipation', 'completionparticipationenabled', 'notchecked');

        return array('completiongotomeetinggroup');*/
        return array();
    }

    function completion_rule_enabled($data) {
        return (!empty($data['completionparticipationenabled']) && $data['completionparticipation'] != 0);
    }

    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        if ($data['startdatetime'] < time()) {
            $errors['startdatetime'] = 'Start date time must be a future time';
        }
        if ($data['enddatetime'] < time()) {
            $errors['enddatetime'] = 'End date time must be future time';
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
        //

         if (!empty($data['completionunlocked']) && (!empty($data['completionparticipationenabled']))) {
        // Turn off completion settings if the checkboxes aren't ticked
        $autocompletion = !empty($data['completion']) && $data['completion']==COMPLETION_TRACKING_AUTOMATIC;
        if ($autocompletion && ($data['completionparticipation']>100 || $data['completionparticipation']<=0)) {
         $errors['completiongotomeetinggroup'] ='Please enter a valid percentage value between 1 and 100'; 
        }
        
    }
        return $errors;
    }

 function get_data() {
    $data = parent::get_data();
    if (!$data) {
        return $data;
    }
    if (!empty($data->completionunlocked)) {
        // Turn off completion settings if the checkboxes aren't ticked
        $autocompletion = !empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
        if (empty($data->completionparticipationenabled) || !$autocompletion) {
           $data->completionparticipation = 0;
        }
    }
    return $data;
}
    
}
