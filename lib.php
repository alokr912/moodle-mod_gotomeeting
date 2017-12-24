<?php

/**
 * GoToMeeting module library file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once 'locallib.php';
require_once $CFG->dirroot . '/calendar/lib.php';

function gotomeeting_add_instance($data, $mform = null) {

    global $USER, $DB;
    $response = createGoToMeeting($data);

    if ($response && $response->status == 201) {
        $data->userid = $USER->id;
        $data->timecreated = time();
        $data->timemodified = time();
        $data->meetinfo = trim($response->body, '"');
        $jsonresponse = json_decode($response->body);
        $data->gotomeetingid = $jsonresponse[0]->meetingid;


        $data->id = $DB->insert_record('gotomeeting', $data);
        if ($data->id) {
            // Add event to calendar
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
        case FEATURE_GROUPS: return false;
        case FEATURE_GROUPINGS: return false;
        case FEATURE_GROUPMEMBERSONLY: return false;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE: return false;
        case FEATURE_GRADE_OUTCOMES: return false;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_COMPLETION_HAS_RULES: return false;
        default: return null;
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
    $result = updateGoToMeeting($oldgotomeeting, $gotomeeting);

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
    global $DB, $CFG;

    $result = false;
    if (!$gotomeeting = $DB->get_record('gotomeeting', array('id' => $id))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('gotomeeting', $id)) {
        return false;
    }
    $context = context_module::instance($cm->id);

    if (deleteGoToMeeting($gotomeeting->gotomeetingid)) {
        $params = array('id' => $gotomeeting->id);
        $result = $DB->delete_records('gotomeeting', $params);
    }
      
    // Delete calendar  event
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

/*
 * 
 * 
 * 
 */

function gotomeeting_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    $result = $type;
    if (!($gotomeeting = $DB->get_record('gotomeeting', array('id' => $cm->instance)))) {
        throw new Exception("Can't find GoToLMS {$cm->instance}");
    }
    return true;
}
