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
 * GoToMeeting module library file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once('locallib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Provide a customized course module info
 * @param type $coursemodule
 * @return \cached_cm_info
 */
function gotomeeting_get_coursemodule_info($coursemodule) {
    global $DB;

    if ($gotomeeting = $DB->get_record('gotomeeting', array('id' => $coursemodule->instance), 'id, name, startdatetime')) {
        $info = new cached_cm_info();
        $info->name = $gotomeeting->name . "  " . userdate($gotomeeting->startdatetime, '%d/%m/%Y %H:%M');
        return $info;
    } else {
        return null;
    }
}

/**
 * Add a new module instance
 * @global type $USER
 * @global type $DB
 * @param type $data
 * @param type $mform
 * @return boolean
 */
function gotomeeting_add_instance($data, $mform = null) {

    global $USER, $DB;
    $response = creategotomeeting($data);

    if ($response) {
        $data->userid = $USER->id;
        $data->timecreated = time();
        $data->timemodified = time();
        $data->meetinfo = json_encode($response[0]);
        $data->gotomeetingid = $response[0]->meetingid;
        $data->gotomeeting_licence = $data->licence;

        $data->id = $DB->insert_record('gotomeeting', $data);
        if ($data->id) {
            // Add event to calendar.
            $event = new stdClass();
            $event->name = $data->name;
            $event->description = $data->intro;
            $event->courseid = $data->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->instance = $data->id;
            $event->eventtype = 'course';
            $event->timestart = $data->startdatetime;
            $event->timeduration = $data->enddatetime - $data->startdatetime;
            $event->visible = 1;
            $event->modulename = 'gotomeeting';
            calendar_event::create($event);
        }

        $event = \mod_gotomeeting\event\gotomeeting_created::create(array(
                    'objectid' => $data->id,
                    'context' => context_module::instance($data->coursemodule),
                    'other' => array('modulename' => $data->name, 'startdatetime' => $data->startdatetime),
        ));
        $event->trigger();

        return $data->id;
    }
    return false;
}

/**
 * List of features supported in Resource module
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function gotomeeting_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE: {
                return MOD_ARCHETYPE_RESOURCE;
            }
        case FEATURE_GROUPS: {
                return false;
            }
        case FEATURE_GROUPINGS: {
                return false;
            }
        case FEATURE_GROUPMEMBERSONLY: {
                return false;
            }
        case FEATURE_MOD_INTRO: {
                return true;
            }
        case FEATURE_COMPLETION_TRACKS_VIEWS: {
                return true;
            }
        case FEATURE_GRADE_HAS_GRADE: {
                return false;
            }
        case FEATURE_GRADE_OUTCOMES: {
                return false;
            }
        case FEATURE_BACKUP_MOODLE2: {
                return true;
            }
        case FEATURE_SHOW_DESCRIPTION: {
                return true;
            }
        case FEATURE_COMPLETION_HAS_RULES: {
                return false;
            }
        default: {
                return null;
            }
    }
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function gotomeeting_update_instance($gotomeeting) {

    global $DB;
    if (!$oldgotomeeting = $DB->get_record('gotomeeting', array('id' => $gotomeeting->instance))) {
        return false;
    }
    $result = false;
    $result = updategotomeeting($oldgotomeeting, $gotomeeting);

    if ($result) {

        $oldgotomeeting->name = $gotomeeting->name;
        $oldgotomeeting->intro = $gotomeeting->intro;
        $oldgotomeeting->startdatetime = $gotomeeting->startdatetime;
        $oldgotomeeting->enddatetime = $gotomeeting->enddatetime;
        $oldgotomeeting->timemodified = time();
        $DB->update_record('gotomeeting', $oldgotomeeting);
        $param = array('courseid' => $gotomeeting->course, 'instance' => $gotomeeting->instance,
            'groupid' => 0, 'modulename' => 'gotomeeting');

        $eventid = $DB->get_field('event', 'id', $param);

        if (!empty($eventid)) {

            $event = new stdClass();
            $event->id = $eventid;
            $event->name = $gotomeeting->name;
            $event->description = $gotomeeting->intro;
            $event->courseid = $gotomeeting->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->instance = $gotomeeting->instance;
            $event->eventtype = 'course';
            $event->timestart = $gotomeeting->startdatetime;
            $event->timeduration = $gotomeeting->enddatetime - $gotomeeting->startdatetime;
            $event->visible = 1;
            $event->modulename = 'gotomeeting';
            $calendarevent = calendar_event::load($eventid);
            $calendarevent->update($event);
        }
    }
    $event = \mod_gotomeeting\event\gotomeeting_updated::create(array(
                'objectid' => $gotomeeting->instance,
                'context' => context_module::instance($gotomeeting->coursemodule),
                'other' => array('modulename' => $gotomeeting->name, 'startdatetime' => $gotomeeting->startdatetime),
    ));
    $event->trigger();
    return $result;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $adobeconnect An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function gotomeeting_delete_instance($id) {
    global $DB;

    $result = false;
    if (!$gotomeeting = $DB->get_record('gotomeeting', array('id' => $id))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('gotomeeting', $id)) {
        return false;
    }
    $context = context_module::instance($cm->id);

    if (deletegotomeeting($gotomeeting->gotomeetingid, $gotomeeting->gotomeeting_licence)) {
        $params = array('id' => $gotomeeting->id);
        $result = $DB->delete_records('gotomeeting', $params);
    }

    // Delete calendar  event.
    $param = array('courseid' => $gotomeeting->course, 'instance' => $gotomeeting->id,
        'groupid' => 0, 'modulename' => 'gotomeeting');

    $eventid = $DB->get_field('event', 'id', $param);
    if ($eventid) {
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }

    $event = \mod_gotomeeting\event\gotomeeting_deleted::create(array(
                'objectid' => $id,
                'context' => $context,
                'other' => array('modulename' => $gotomeeting->name, 'startdatetime' => $gotomeeting->startdatetime),
    ));

    $event->trigger();

    return $result;
}

/**
 * 
 * @param type $course
 * @param type $cm
 * @param type $userid
 * @param type $type
 * @return boolean
 * @throws Exception
 */
function gotomeeting_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    if (!($gotomeeting = $DB->get_record('gotomeeting', array('id' => $cm->instance)))) {
        throw new Exception("Can't find GoToLMS {$cm->instance}");
    }
    return true;
}

/**
 * Provide list of active license in the system.
 * @param type $licence
 * @return type
 */
function gotomeeting_get_organiser_account_name($licence) {
    global $DB;

    if ($gotomeetinglicence = $DB->get_record('gotomeeting_licence', array('id' => $licence))) {
        return explode('@', $gotomeetinglicence->email)[0];
    }
    return null;
}
