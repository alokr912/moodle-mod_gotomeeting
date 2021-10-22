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
 * GoToMeeting module local library file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/gotomeeting/classes/GotoOAuth.php');

function creategotomeeting($gotomeeting) {
    global $USER, $DB, $CFG;

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->licence);

    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        print_error("Incomplete GoToMeeting setup");
    }

    $attributes = array();
    $dstoffset = dst_offset_on($gotomeeting->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotomeeting->name;
    $sdate = usergetdate(usertime($gotomeeting->startdatetime - $dstoffset));
    $attributes['starttime'] = $sdate['year'] . '-' . $sdate['mon'] . '-' . $sdate['mday'] . 'T' .
            $sdate['hours'] . ':' . $sdate['minutes'] . ':' . $sdate['seconds'] . 'Z';
    $edate = usergetdate(usertime($gotomeeting->enddatetime - $dstoffset));
    $attributes['endtime'] = $edate['year'] . '-' . $edate['mon'] . '-' . $edate['mday'] . 'T' .
            $edate['hours'] . ':' . $edate['minutes'] . ':' . $edate['seconds'] . 'Z';
    $attributes['passwordrequired'] = 'false';
    $attributes['conferencecallinfo'] = 'Hybrid';
    $attributes['meetingtype'] = 'scheduled';
    $attributes['timezonekey'] = get_user_timezone();

    $response = $gotooauth->post("/G2M/rest/meetings", $attributes);

    if ($response) {
        return $response;
    }
    return false;
}

function updategotomeeting($oldgotomeeting, $gotomeeting) {
    global $USER, $DB, $CFG;

    $result = false;
    $gotooauth = new mod_gotomeeting\GoToOAuth($oldgotomeeting->gotomeeting_licence);

    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        print_error("Incomplete GoToMeeting setup");
    }
    $attributes = array();
    $attributes['subject'] = $gotomeeting->name;
    $dstoffset = dst_offset_on($gotomeeting->startdatetime, get_user_timezone());
    $sdate = usergetdate(usertime($gotomeeting->startdatetime - $dstoffset));
    $attributes['starttime'] = $sdate['year'] . '-' . $sdate['mon'] . '-' . $sdate['mday'] . 'T' .
            $sdate['hours'] . ':' . $sdate['minutes'] . ':' . $sdate['seconds'] . 'Z';
    $edate = usergetdate(usertime($gotomeeting->enddatetime - $dstoffset));
    $attributes['endtime'] = $edate['year'] . '-' . $edate['mon'] . '-' . $edate['mday'] . 'T' .
            $edate['hours'] . ':' . $edate['minutes'] . ':' . $edate['seconds'] . 'Z';
    $attributes['passwordrequired'] = 'false';
    $attributes['conferencecallinfo'] = 'Hybrid';
    $attributes['meetingtype'] = 'scheduled';
    $attributes['timezonekey'] = get_user_timezone();

    $response = $gotooauth->put("/G2M/rest/meetings/{$oldgotomeeting->gotomeetingid}", $attributes);

    if ($response) {
        $result = true;
    }

    return $result;
}

function deletegotomeeting($gotowebinarid, $gotomeeting_licence) {

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        print_error("Incomplete GoToMeeting setup");
    }

    $responce = $gotooauth->delete("/G2M/rest/meetings/{$gotowebinarid}");
    if ($responce) {
        return true;
    } else {
        return false;
    }
}

function get_gotomeeting($gotomeeting) {

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        print_error("Incomplete GoToMeeting setup");
    }
    $context = context_course::instance($gotomeeting->course);
    if (is_siteadmin() OR has_capability('mod/gotomeeting:organiser', $context) OR
            has_capability('mod/gotomeeting:presenter', $context)) {

        $response = $gotooauth->get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/start");

        if ($response) {
            return $response->hostURL;
        }
    } else {
        $meetinginfo = json_decode($gotomeeting->meetinfo);
        return $meetinginfo->joinURL;
    }
}

function get_gotomeeting_attendance($gotomeeting) {

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        print_error("Incomplete GoToMeeting setup");
    }

    $response = $gotooauth->get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/attendees");
    if (!is_array($response)) {
        return null;
    }

    $duration = $gotomeeting->enddatetime - $gotomeeting->startdatetime;

    $table = new html_table();

    $table->head = array('Attendee', 'Join time', 'Leave time', 'Completed Percentage');

    $rows = array();
    foreach ($response as $attendance) {

      
        $joinTime = strtotime($attendance->joinTime);
        $leaveTime = strtotime($attendance->leaveTime);
        $differenceInSeconds = $leaveTime - $joinTime;

        $attendance_percentage = 0;
        if ($differenceInSeconds) {
            $attendance_percentage = number_format(($attendance->duration * 60 * 100) / $duration, 2);
        }

        $rows[] = array($attendance->attendeeName, $attendance->joinTime, $attendance->leaveTime, $attendance_percentage);
    }


    $table->data = $rows;

    return $table;
}

function get_gotomeeting_attendance_view($gotomeeting) {

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        print_error("Incomplete GoToMeeting setup");
    }

    $response = $gotooauth->get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/attendees");

    $duration = $gotomeeting->enddatetime - $gotomeeting->startdatetime;

    $table = new html_table();

    $table->head = array('Attendee', 'Join time', 'Leave time', 'Completed Percentage');

    $rows = array();
    foreach ($response as $attendance) {

        $joinTime = strtotime($attendance->joinTime);
        $leaveTime = strtotime($attendance->leaveTime);
        $differenceInSeconds = $leaveTime - $joinTime;

        $attendance_percentage = 0;
        if ($differenceInSeconds) {
            $attendance_percentage = number_format(($duration * 10 / $differenceInSeconds) * 10, 2);
        }

        $rows[] = array($attendance->attendeeName, $attendance->joinTime, $attendance->leaveTime, $attendance_percentage);
    }


    $table->data = $rows;

    return $table;
}

function get_gotomeeting_view($gotomeeting, $cmid) {
    $meetinginfo = json_decode($gotomeeting->meetinfo);
    $table = new html_table();
    $head = array();
    $head[] = get_string('meetingname', 'gotomeeting');
    $head[] = get_string('meeting_account', 'gotomeeting');
    $head[] = get_string('meetingid', 'gotomeeting');
    $head[] = get_string('startdatetime', 'gotomeeting');
    $head[] = get_string('enddatetime', 'gotomeeting');

    $head[] = get_string('conference_call_info', 'gotomeeting');
   // $head[] = get_string('join_url', 'gotomeeting');
    $head[] = get_string('report', 'gotomeeting');
   
    $table->head = $head;
    $data = array();
    $data[] = $gotomeeting->name;
    $data[] = gotomeeting_get_organiser_account_name($gotomeeting->gotomeeting_licence);
    $data[] = $gotomeeting->gotomeetingid;
    $data[] = userdate($gotomeeting->startdatetime);
    $data[] = userdate($gotomeeting->enddatetime);
    $data[] = $meetinginfo->conferenceCallInfo;

     $report_link = new moodle_url('attendance.php', array('id' => $cmid));
    $data[] = html_writer::link($report_link, get_string('report', 'gotomeeting'));
    
    $table->data[] = $data;
    
    $cell2 = new html_table_cell(html_writer::link(trim(get_gotomeeting($gotomeeting), '"'), get_string('join_url', 'gotomeeting'), array("target" => "_blank", 'class' => 'btn btn-primary')));
    $cell2->colspan = 7;
    $cell2->style = 'text-align:center;';
    $table->data[] = array($cell2);

    return $table;
}
