<?php

/**
 * GoToMeeting module local library file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 require_once $CFG->dirroot . '/mod/gotomeeting/classes/GoToOAuth.php';
function createGoToMeeting($gotomeeting) {
    global $USER, $DB, $CFG;
   
     $goToOauth = new mod_gotomeeting\GoToOAuth();
     $config = get_config(mod_gotomeeting\GoToOAuth::PLUGIN_NAME);
     if(!isset( $config->organizer_key) || empty($config->organizer_key)){
         print_error("Incomplete GoToMeeting setup");
     }
  
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

    
    $response = $goToOauth->post("/G2M/rest/meetings", $attributes);
   
    if ($response) {
        return $response;
    }
    return false;
}

function updateGoToMeeting($oldgotomeeting, $gotomeeting) {
    global $USER, $DB, $CFG;
   
    $result = false;
     $goToOauth = new mod_gotomeeting\GoToOAuth();
     $config = get_config(mod_gotomeeting\GoToOAuth::PLUGIN_NAME);
     if(!isset( $config->organizer_key) || empty($config->organizer_key)){
         print_error("Incomplete GoToMeeting setup");
     }
    
   

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

    $response = $goToOauth->put("/G2M/rest/meetings/{$oldgotomeeting->gotomeetingid}", $attributes);

    if ($response) {
        $result = true;
    }

    return $result;
}

function deleteGoToMeeting($gotowebinarid) {
   
    $goToOauth = new mod_gotomeeting\GoToOAuth();
    $config = get_config(mod_gotomeeting\GoToOAuth::PLUGIN_NAME);
     
    
     if(!isset( $config->organizer_key) || empty($config->organizer_key)){
         print_error("Incomplete GoToMeeting setup");
     }
    
    $responce = $goToOauth->delete("/G2M/rest/meetings/{$gotowebinarid}");
    if ($responce) {
        return true;
    } else {
        return false;
    }
}

function get_gotomeeting($gotomeeting) {

    
     $goToOauth = new mod_gotomeeting\GoToOAuth();
     $config = get_config(mod_gotomeeting\GoToOAuth::PLUGIN_NAME);
     
     if(!isset( $config->organizer_key) || empty($config->organizer_key)){
         print_error("Incomplete GoToMeeting setup");
     }
    $context = context_course::instance($gotomeeting->course);
    if (is_siteadmin() OR has_capability('mod/gotomeeting:organiser', $context) OR has_capability('mod/gotomeeting:presenter', $context)) {
       
        $response = $goToOauth->get("/G2M/rest/meetings/{$gotomeeting->gotomeetingid}/start");
        
        if ($response) {
            return $response->hostURL;
        }
    } else {
        $meetinginfo = json_decode($gotomeeting->meetinfo);
        return $meetinginfo->joinURL;
    }
}
