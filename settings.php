<?php

/**
 * GoToMeeting module global settings file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {



    //----------------  Consumer Key Settings -----------------------------------------//   
    $name = 'gotomeeting/consumer_key';
    $visiblename = get_string('gtm_consumer_key', 'gotomeeting');
    $description = get_string('gtm_consumer_key_desc', 'gotomeeting');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    //-----------------Consumer secret settings ----------------------------------------
    $name = 'gotomeeting/consumer_secret';
    $visiblename = get_string('gtm_consumer_secret', 'gotomeeting');
    $description = get_string('gtm_consumer_secret_desc', 'gotomeeting');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    //---------------------Userid(License userid) General settings -----------------------------------------------------------------------------------
    $name = 'gotomeeting/userid';
    $visiblename = get_string('gtm_userid', 'gotomeeting');
    $description = get_string('gtm_userid_desc', 'gotomeeting');
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, '', PARAM_RAW, 50));

    //---------------------Password settings -----------------------------------------------------------------------------------
    $name = 'gotomeeting/password';
    $visiblename = get_string('gtm_password', 'gotomeeting');
    $description = get_string('gtm_password_desc', 'gotomeeting');
    $settings->add(new admin_setting_configpasswordunmask($name, $visiblename, $description, ''));


    $url = $CFG->wwwroot . '/mod/gotomeeting/configtest.php';
    $url = htmlentities($url, ENT_COMPAT, 'UTF-8');
    $options = 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=700,height=300';
    $str = '<center><input type="button" onclick="window.open(\'' . $url . '\', \'\', \'' . $options . '\');" value="' .
            get_string('testconnection', 'gotomeeting') . '" /></center>';
    $settings->add(new admin_setting_heading('adobeconnect_test', '', $str));
}

