<?php

/**
 * GoToMeeting module local library file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function createGoToMeeting($gotomeeting) {
    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotomeeting/lib/OSD.php';
    $config = get_config('gotomeeting');
    OSD::setup(trim($config->consumer_key),trim($config->consumer_secret));
    OSD::authenticate_with_password(trim($config->userid), trim($config->password));
    $attributes = array();
    $dstoffset = dst_offset_on($gotomeeting->startdatetime, get_user_timezone());
    $attributes['subject'] = $gotomeeting->name;
    $startdate = usergetdate(usertime($gotomeeting->startdatetime - $dstoffset));
    $attributes['starttime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' . $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotomeeting->enddatetime - $dstoffset));
    $attributes['endtime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' . $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['passwordrequired'] = 'false';
    $attributes['conferencecallinfo'] = 'Hybrid';
    $attributes['meetingtype'] = 'scheduled';
    $attributes['timezonekey'] = get_user_timezone();

    $response = OSD::post("/G2M/rest/meetings", $attributes);

    if ($response && $response->status == 201) {
        return $response;
    }
    return false;
}

function updateGoToMeeting($oldgotomeeting, $gotomeeting) {
    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotomeeting/lib/OSD.php';
    $result = false;
    $config = get_config('gotomeeting');
    OSD::setup(trim($config->consumer_key),trim($config->consumer_secret));
    OSD::authenticate_with_password(trim($config->userid), trim($config->password));

    $attributes = array();
    $attributes['subject'] = $gotomeeting->name;
    $dstoffset = dst_offset_on($gotomeeting->startdatetime, get_user_timezone());
    $startdate = usergetdate(usertime($gotomeeting->startdatetime - $dstoffset));
    $attributes['starttime'] = $startdate['year'] . '-' . $startdate['mon'] . '-' . $startdate['mday'] . 'T' . $startdate['hours'] . ':' . $startdate['minutes'] . ':' . $startdate['seconds'] . 'Z';
    $endtdate = usergetdate(usertime($gotomeeting->enddatetime - $dstoffset));
    $attributes['endtime'] = $endtdate['year'] . '-' . $endtdate['mon'] . '-' . $endtdate['mday'] . 'T' . $endtdate['hours'] . ':' . $endtdate['minutes'] . ':' . $endtdate['seconds'] . 'Z';
    $attributes['passwordrequired'] = 'false';
    $attributes['conferencecallinfo'] = 'Hybrid';
    $attributes['meetingtype'] = 'scheduled';
    $attributes['timezonekey'] = get_user_timezone();

    $response = OSD::request('PUT', "/G2M/rest/meetings/{$oldgotomeeting->gotomeetingid}", $attributes);

    if ($response && $response->status == 204) {
        $result = true;
    }

    return $result;
}

function deleteGoToMeeting($gotowebinarid) {
    global $CFG;
    require_once $CFG->dirroot . '/mod/gotomeeting/lib/OSD.php';
    $config = get_config('gotomeeting');
    OSD::setup(trim($config->consumer_key),trim($config->consumer_secret));
    OSD::authenticate_with_password(trim($config->userid), trim($config->password));

    $responce = OSD::request('DELETE', "/G2M/rest/meetings/{$gotowebinarid}");
    if ($responce->status == 204) {
        return true;
    } else {
        return false;
    }
}

function get_gotomeeting($gotomeeting) {
    global $USER, $DB, $CFG;
    require_once $CFG->dirroot . '/mod/gotomeeting/lib/OSD.php';
    $result = false;
    $context = context_course::instance($gotomeeting->course);
    if (is_siteadmin() OR has_capability('mod/gotomeeting:organiser', $context) OR has_capability('mod/gotomeeting:presenter', $context)) {
        $config = get_config('gotomeeting');
        OSD::setup(trim($config->consumer_key),trim($config->consumer_secret));
        OSD::authenticate_with_password(trim($config->userid), trim($config->password));
        $response = OSD::get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/start");

        if ($response && $response->status == 200) {
            return json_decode($response->body)->hostURL;
        }
    } else {
        $meetinginfo = json_decode($gotomeeting->meetinfo);
        return $meetinginfo[0]->joinURL;
    }
}
