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

namespace mod_gotomeeting;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/filelib.php');

use curl;

/**
 * GoToMeeting module manage Authentincation with GoTo
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class GotoOAuth {

    /**
     * @var string
     */
    public const BASE_URL = "https://api.getgo.com";

    /**
     * @var string
     */
    public const OAUTH_URL = "https://authentication.logmeininc.com";

    /**
     * @var string
     */
    public const PLUGIN_NAME = "gotomeeting";

    /**
     * @var string
     */
    public const ACCESS_TOKEN = "access_token";

    /**
     * @var string
     */
    public const REFRESH_TOKEN = "refresh_token";

    /**
     * @var string
     */
    public const ORGANISER_KEY = "organizer_key";

    /**
     * @var string
     */
    public const ACCOUNT_KEY = "account_key";

    /**
     * @var string
     */
    public const ACCESS_TOKEN_TIME = "access_token_time";

    /**
     * @var string
     */
    public const EXPIRY_TIME_IN_SECOND = 3500;

    /**
     * @var string
     */
    private $accesstoken;

    /**
     * @var string
     */
    private $refreshtoken;

    /**
     * @var string
     */
    public $organizerkey;

    /**
     * @var string
     */
    private $accountkey;

    /**
     * @var string
     */
    private $accesstokentime;

    /**
     * @var string
     */
    private $consumerkey;

    /**
     * @var string
     */
    private $consumersecret;

    /**
     * @var string
     */
    private $curl;

    /**
     * Constructor for the GotoOAuth .
     * @param int $licenceid
     */
    public function __construct($licenceid = null) {
        global $DB;
        $this->curl = new curl();
        $licence = $DB->get_record('gotomeeting_licence', ['id' => $licenceid]);

        if ($licence) {
            $this->organizerkey = !empty($licence->organizer_key) ? $licence->organizer_key : null;
            $this->refreshtoken = !empty($licence->refresh_token) ? $licence->refresh_token : null;
            $this->accesstoken = !empty($licence->access_token) ? $licence->access_token : null;
            $this->accesstokentime = !empty($licence->access_token_time) ? $licence->access_token_time : null;
        }
    }

    /**
     * Get accesstoken with code.
     * @param string $code
     * @return Boolean
     */
    public function getaccesstokenwithcode($code) {
        global $CFG;

        $pluginconfig = get_config(self::PLUGIN_NAME);
        $authorization = base64_encode($pluginconfig->consumer_key . ":" . $pluginconfig->consumer_secret);
        $headers = [
            'Authorization: Basic ' . $authorization,
            'Accept:application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ];
        $this->curl->setHeader($headers);

        $redirecturl = $CFG->wwwroot . '/mod/gotomeeting/oauthCallback.php';
        $data = ['redirect_uri' => $redirecturl, 'grant_type' => 'authorization_code', 'code' => $code,
            'client_id' => $this->consumerkey, ];
        $serveroutput = $this->curl->post(self::OAUTH_URL . '/oauth/token', self::encode_attributes($data));

        $token = json_decode($serveroutput);

        $profile = $this->getprofileinfo($token->access_token);

        return $this->update_access_token($token, $profile);
    }

    /**
     * Get access token from refresh token
     * @param string $refreshtoken
     * @return boolean
     */
    public function getaccesstokenwithrefreshtoken($refreshtoken) {
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ];

        $this->curl->setHeader($headers);
        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $refreshtoken];

        $serveroutput = $this->curl->post(self::OAUTH_URL . '/oauth/token', self::encode_attributes($data));

        $response = json_decode($serveroutput);

        if (isset($response) && isset($response->access_token)) {
            $profile = $this->getprofileinfo($response->access_token);
            $response->email = $response->principal;
            $this->update_access_token($response, $profile);
            $this->accesstoken = $response->access_token;
            $this->accesstokentime = time();

            return $response->access_token;
        }
        return false;
    }

    /**
     * This method return a valid access token.
     * @return string
     */
    public function getaccesstoken() {

        if (isset($this->accesstokentime) && !empty($this->accesstokentime) &&
                $this->accesstokentime + self::EXPIRY_TIME_IN_SECOND > time()) {
            return $this->accesstoken;
        } else {
            return $this->getaccesstokenwithrefreshtoken($this->refreshtoken);
        }
    }

    /**
     * This method send data to goto server.
     * @param string $endpoint
     * @param mixed $data
     * @return mixed
     */
    public function post($endpoint, $data) {

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];
        $this->curl->resetHeader();
        $this->curl->setHeader($headers);

        $serveroutput = $this->curl->post(self::BASE_URL . $endpoint, json_encode($data));

        return json_decode($serveroutput);
    }

    /**
     * This method send data to goto server.
     * @param string $endpoint
     * @param mixed $data
     * @return boolean
     */
    public function put($endpoint, $data) {

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];
        $this->curl->resetHeader();
        $this->curl->setHeader($headers);

        $serveroutput = $this->curl->put(self::BASE_URL . $endpoint, json_encode($data));

        $result = json_decode($serveroutput);
        return true;
    }

    /**
     * This method send data to goto server.
     * @param string $endpoint
     * @return mixed
     */
    public function get($endpoint) {

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];
        $this->curl->resetHeader();
        $this->curl->setHeader($headers);

        $serveroutput = $this->curl->get(self::BASE_URL . $endpoint);

        return json_decode($serveroutput);
    }

    /**
     * This method send data to goto server.
     * @param string $endpoint
     * @param mixed $data
     * @return boolean
     */
    public function delete($endpoint, $data = null) {

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];
        $this->curl->resetHeader();
        $this->curl->setHeader($headers);

        $serveroutput = $this->curl->delete(self::BASE_URL . $endpoint, json_encode($data));

        if (empty($serveroutput)) {
            return true;
        }
        return false;
    }

    /**
     * This method get the status of setting.
     * @return mixed
     */
    public function getsetupstatus() {

        $gotowebinarconfig = get_config(self::PLUGIN_NAME);

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ];
        $this->curl->setHeader($headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $this->refreshtoken];

        $serveroutput = $this->curl->post(self::OAUTH_URL . "/oauth/token", self::encode_attributes($data));

        return json_decode($serveroutput);
    }

    /**
     * This method encode url params attributes.
     * @param array $attributes
     * @return string
     */
    public static function encode_attributes($attributes) {

        $return = [];
        foreach ($attributes as $key => $value) {
            $return[] = urlencode($key) . '=' . urlencode($value);
        }
        return join('&', $return);
    }

    /**
     * Get the user profile info.
     * @param mixed $accesstoken
     * @return mixed
     */
    private function getprofileinfo($accesstoken) {

        $headers = [
            'Authorization: Bearer ' . $accesstoken,
        ];
        $this->curl->resetHeader();
        $this->curl->setHeader($headers);
        $serveroutput = $this->curl->get(self::BASE_URL . "/admin/rest/v1/me?includeAdmins=false&includeInvitation=false");

        return json_decode($serveroutput);
    }

    /**
     * This method updated the access token
     * @param string $token
     * @param mixed $profile
     * @return boolean
     */
    private function update_access_token($token, $profile) {

        global $DB;
        if (isset($token) && isset($token->access_token)) {
            $licence = $DB->get_record('gotomeeting_licence', ['email' => $profile->email]);
            if (!$licence) {
                $licence = new \stdClass();
                $licence->email = $profile->email;
                $licence->first_name = $profile->firstName;
                $licence->last_name = $profile->lastName;
                $licence->access_token = $token->access_token;
                $licence->refresh_token = $token->refresh_token;
                $licence->principal = $token->principal;
                $licence->locale = $profile->locale;
                $licence->time_zone = $profile->timeZone;
                $licence->token_type = $token->token_type;
                $licence->expires_in = $token->expires_in;
                $licence->account_key = $profile->accountKey;
                $licence->organizer_key = $profile->key;
                $licence->active = 1;
                $licence->timecreated = time();
                $licence->timemodified = time();
                $licence->access_token_time = time();
                $DB->insert_record('gotomeeting_licence', $licence);
            } else {
                $licence->access_token = $token->access_token;
                $licence->timemodified = time();
                $licence->access_token_time = time();
                if(isset($token->refresh_token) && !empty($token->refresh_token)){
                     $licence->refresh_token = $token->refresh_token;
                }

                $DB->update_record('gotomeeting_licence', $licence);
            }

            return true;
        } else {
            return false;
        }
    }

}
