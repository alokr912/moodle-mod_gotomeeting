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

use mod_gotomeeting\GoToOAuth as GoToOAuth;

/**
 * Create GoToMeeting instance at GoToMeeting site.
 * @param mixed $gotomeeting
 * @return boolean
 * @throws moodle_exception
 */
function creategotomeeting($gotomeeting) {

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->licence);

    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotomeeting');
    }

    $attributes = [];
    $dstoffset = dst_offset_on($gotomeeting->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotomeeting->name;
    $sdate = usergetdate(usertime($gotomeeting->startdatetime - $dstoffset));
    $attributes['starttime'] = $sdate['year'] . '-' . $sdate['mon'] . '-' . $sdate['mday'] . 'T' .
            $sdate['hours'] . ':' . $sdate['minutes'] . ':' . $sdate['seconds'] . 'Z';
    $edate = usergetdate(usertime($gotomeeting->enddatetime - $dstoffset));
    $attributes['endtime'] = $edate['year'] . '-' . $edate['mon'] . '-' . $edate['mday'] . 'T' .
            $edate['hours'] . ':' . $edate['minutes'] . ':' . $edate['seconds'] . 'Z';
    $attributes['passwordrequired'] = false;
    $attributes['conferencecallinfo'] = 'Hybrid';
    $attributes['meetingtype'] = 'scheduled';
    $attributes['timezonekey'] = '';

    $response = $gotooauth->post("/G2M/rest/meetings", $attributes);

    if ($response) {
        return $response;
    }
    return false;
}

/**
 * Update GoToMeeting instance at GoToMeeting site.
 * @param mixed $oldgotomeeting
 * @param mixed $gotomeeting
 * @return boolean
 * @throws moodle_exception
 */
function updategotomeeting($oldgotomeeting, $gotomeeting) {

    $result = false;

    $gotooauth = new mod_gotomeeting\GoToOAuth($oldgotomeeting->gotomeeting_licence);

    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotomeeting');
    }
    $attributes = [];
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
    $attributes['timezonekey'] = '';
    $response = $gotooauth->put("/G2M/rest/meetings/{$oldgotomeeting->gotomeetingid}", $attributes);

    if ($response) {
        $result = true;
    }

    return $result;
}

/**
 * Delete meeting from gotomeeting server.
 * @param int $gotowebinarid
 * @param int $gotomeetinglicence
 * @return boolean
 * @throws moodle_exception
 */
function deletegotomeeting($gotowebinarid, $gotomeetinglicence) {

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeetinglicence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotomeeting');
    }

    $responce = $gotooauth->delete("/G2M/rest/meetings/{$gotowebinarid}");
    if ($responce) {
        return true;
    } else {
        return false;
    }
}

/**
 * Get GoToMeeting instance at GoToMeeting site.
 * @param mixed $gotomeeting
 * @return string
 * @throws moodle_exception
 */
function get_gotomeeting($gotomeeting) {
    $meetinginfo = json_decode($gotomeeting->meetinfo);
    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotomeeting');
    }
    $context = context_course::instance($gotomeeting->course);

    if (has_capability('mod/gotomeeting:organiser', $context) ||
            has_capability('mod/gotomeeting:presenter', $context)) {

        $response = $gotooauth->get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/start");

        if ($response) {
            return $response->hostURL;
        }
        return $meetinginfo->joinURL;
    } else {

        return $meetinginfo->joinURL;
    }
}

/**
 * Getting GoTomeeting attendance.
 * @param string $gotomeeting
 * @return \html_table
 * @throws moodle_exception
 */
function get_gotomeeting_attendance($gotomeeting) {
    global $PAGE;
    $tdir = optional_param('tdir', 3, PARAM_INT);
    $tsort = optional_param('tsort', 'name', PARAM_ALPHAEXT);

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotomeeting');
    }

    $response = $gotooauth->get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/attendees");
    if (!is_array($response)) {
        return null;
    }

    $duration = $gotomeeting->enddatetime - $gotomeeting->startdatetime;

    $table = new flexible_table('mod-gotomeeting-attendance-table');

    $table->define_headers([get_string('name', 'gotomeeting'), get_string('email', 'gotomeeting'), get_string('jointime', 'gotomeeting'),
        get_string('leavetime', 'gotomeeting'), get_string('duration', 'gotomeeting'), get_string('completedpercentage', 'gotomeeting'),]);

    $table->define_columns(array('name', 'email', 'jointime', 'leavetime', 'duration', 'completedpercentage'));
    //$table->sortable(true, $tsort, $tdir);
    $table->sortable(true, 'name', SORT_DESC);
    $table->define_baseurl($PAGE->url);
    $table->set_attribute('id', 'mod-gotomeeting-attendance-table');
    $table->set_attribute('class', 'admintable generaltable');
    $table->setup();
    $rows = [];

    foreach ($response as $attendance) {
        //  print_object($attendance);die;
        $jointime = strtotime($attendance->joinTime);
        $leavetime = strtotime($attendance->leaveTime);
        $differenceinseconds = $leavetime - $jointime;

        $attendancepercentage = 0;
        if ($differenceinseconds) {
            $attendancepercentage = number_format(($attendance->duration * 60 * 100) / $duration, 2);
        }

        $rows[] = [$attendance->attendeeName, $attendance->email, $attendance->joinTime,
            $attendance->leaveTime, $attendance->duration, $attendancepercentage,];
    }

    foreach ($rows as $row) {
        $table->add_data($row);
    }



    return $table;
}

/**
 * Preparing GoTomeeting attendance view.
 * @param mixed $gotomeeting
 * @return \html_table
 * @throws moodle_exception
 */
function get_gotomeeting_attendance_view($gotomeeting) {

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotomeeting');
    }

    $response = $gotooauth->get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/attendees");

    $duration = $gotomeeting->enddatetime - $gotomeeting->startdatetime;

    $table = new html_table();

    $table->head = [get_string('attendee', 'gotomeeting'), get_string('jointime', 'gotomeeting'),
        get_string('leavetime', 'gotomeeting'), get_string('completedpercentage', 'gotomeeting'),];

    $rows = [];
    foreach ($response as $attendance) {

        $jointime = strtotime($attendance->joinTime);
        $leavetime = strtotime($attendance->leaveTime);
        $differenceinseconds = $jointime - $leavetime;

        $attendancepercentage = 0;
        if ($differenceinseconds) {
            $attendancepercentage = number_format(($duration * 10 / $differenceinseconds) * 10, 2);
        }

        $rows[] = [$attendance->attendeeName, $attendance->joinTime, $attendance->leaveTime, $differenceinseconds];
    }

    $table->data = $rows;

    return $table;
}

/**
 * Prepare GoToMeeting view.
 * @param mixed $gotomeeting
 * @param int $cmid
 * @return \html_table
 */
function get_gotomeeting_view($gotomeeting, $cmid) {
    $meetinginfo = json_decode($gotomeeting->meetinfo);
    $context = context_module::instance($cmid);

    $table = new html_table();
    $head = [];
    $head[] = get_string('meetingname', 'gotomeeting');
    $head[] = get_string('gotomeetingintro', 'gotomeeting');
    if (has_capability('mod/gotomeeting:addinstance', $context)) {
        $head[] = get_string('meeting_account', 'gotomeeting');
    }
    $head[] = get_string('meetingid', 'gotomeeting');
    $head[] = get_string('startdatetime', 'gotomeeting');
    $head[] = get_string('enddatetime', 'gotomeeting');

    $head[] = get_string('conference_call_info', 'gotomeeting');
    if (has_capability('mod/gotomeeting:addinstance', $context)) {
        $head[] = get_string('report', 'gotomeeting');
    }

    $table->head = $head;
    $data = [];
    $data[] = $gotomeeting->name;

    $data[] = strip_tags($gotomeeting->intro);
    if (has_capability('mod/gotomeeting:addinstance', $context)) {
        $data[] = gotomeeting_get_organiser_account_name($gotomeeting->gotomeeting_licence);
    }

    $data[] = $gotomeeting->gotomeetingid;
    $data[] = userdate($gotomeeting->startdatetime);
    $data[] = userdate($gotomeeting->enddatetime);
    $data[] = $meetinginfo->conferenceCallInfo;
    if (has_capability('mod/gotomeeting:addinstance', $context)) {
        $reportlink = new moodle_url('attendance.php', ['id' => $cmid]);
        $data[] = html_writer::link($reportlink, get_string('report', 'gotomeeting'));
    }

    $table->data[] = $data;

    $cell2 = new html_table_cell(html_writer::link(trim(get_gotomeeting($gotomeeting), '"'),
                    get_string('join_url', 'gotomeeting'), ["target" => "_blank", 'class' => 'btn btn-primary']));
    $cell2->colspan = 8;
    $cell2->style = 'text-align:center;';
    $table->data[] = [$cell2];

    if ($link = trim(get_gotomeeting_recording($gotomeeting))) {
        $cell3 = new html_table_cell(html_writer::link($link,
                        get_string('recording_download_url', 'gotomeeting'), ["target" => "_blank", 'class' => 'btn btn-primary']));
        $cell3->colspan = 8;
        $cell3->style = 'text-align:center;';
        $table->data[] = [$cell3];
    }
    return $table;
}

function get_gotomeeting_recording($gotomeeting) {
    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotomeeting');
    }
    $dstoffset = dst_offset_on($gotomeeting->startdatetime, get_user_timezone());
    $sdate = usergetdate(usertime($gotomeeting->startdatetime - $dstoffset - 1000));
    $startDate = $sdate['year'] . '-' . $sdate['mon'] . '-' . $sdate['mday'] . 'T' .
            $sdate['hours'] . ':' . $sdate['minutes'] . ':' . $sdate['seconds'] . 'Z';
    $edate = usergetdate(usertime($gotomeeting->enddatetime - $dstoffset + 1000));
    $endDate = $edate['year'] . '-' . $edate['mon'] . '-' . $edate['mday'] . 'T' .
            $edate['hours'] . ':' . $edate['minutes'] . ':' . $edate['seconds'] . 'Z';

    $response = $gotooauth->get("/G2M/rest/organizers/$gotooauth->organizerkey/historicalMeetings?startDate=$startDate&endDate=$endDate");

    if (is_array($response)) {
        foreach ($response as $meeting) {
            if ($meeting->meetingId == $gotomeeting->gotomeetingid && !empty($meeting->recording) && !empty($meeting->recording->downloadUrl)) {
                return $meeting->recording->downloadUrl;
            }
        }
    } else {
        return null;
    }
}

/**
 * Getting GoTomeeting attendance.
 * @param string $gotomeeting
 * @return \html_table
 * @throws moodle_exception
 */
function get_gotomeeting_attendance_data($gotomeeting) {
    global $PAGE;

    $gotooauth = new mod_gotomeeting\GoToOAuth($gotomeeting->gotomeeting_licence);
    if (!isset($gotooauth->organizerkey) || empty($gotooauth->organizerkey)) {
        throw new moodle_exception('incompletesetup', 'gotomeeting');
    }

    $response = $gotooauth->get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/attendees");
    if (!is_array($response)) {
        return null;
    }

    $duration = $gotomeeting->enddatetime - $gotomeeting->startdatetime;

    $rows = [];

    foreach ($response as $attendance) {
       
        $jointime = strtotime($attendance->joinTime);
        $leavetime = strtotime($attendance->leaveTime);
        $differenceinseconds = $leavetime - $jointime;

        $attendancepercentage = 0;
        if ($differenceinseconds) {
            $attendancepercentage = number_format(($attendance->duration * 60 * 100) / $duration, 2);
        }

        $rows[] = ['name' => $attendance->attendeeName, 'email' => $attendance->email, 'jointime' => $attendance->joinTime,
            'leavetime' => $attendance->leaveTime, 'duration' => $attendance->duration, 'completedpercentage' => $attendancepercentage,];
    }
    $data = [['header' => [['key' => 'name', 'label' => get_string('name', 'gotomeeting'), 'sortable' => true],
        ['key' => 'email', 'label' => get_string('email', 'gotomeeting'), 'sortable' => true],
        ['key' => 'jointime', 'label' => get_string('jointime', 'gotomeeting'), 'sortable' => true],
        ['key' => 'leavetime', 'label' => get_string('leavetime', 'gotomeeting'), 'sortable' => true],
        ['key' => 'duration', 'label' => get_string('duration', 'gotomeeting'), 'sortable' => true],
        ['key' => 'completedpercentage', 'label' => get_string('completedpercentage', 'gotomeeting'), 'sortable' => true],
    ], 'data' => $rows]];

    return $data;
}
