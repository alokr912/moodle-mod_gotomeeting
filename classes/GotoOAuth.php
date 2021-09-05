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
 * GoToMeeting module manage Authentincation with GoTo
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gotomeeting;

class GotoOAuth {

    public const BASE_URL = "https://api.getgo.com";
    public const PLUGIN_NAME = "gotomeeting";
    public const ACCESS_TOKEN = "access_token";
    public const REFRESH_TOKEN = "refresh_token";
    public const ORGANISER_KEY = "organizer_key";
    public const ACCOUNT_KEY = "account_key";
    public const ACCESS_TOKEN_TIME = "access_token_time";
    public const EXPIRY_TIME_IN_SECOND = 3500;

    private $accesstoken;
    private $refreshtoken;
    private $organizerkey;
    private $accountkey;
    private $accesstokentime;
    private $consumerkey;
    private $consumersecret;

    public function __construct() {

        $config = get_config(self::PLUGIN_NAME);
        if (isset($config)) {
            $this->organizerkey = !empty($config->organizer_key) ? $config->organizer_key : null;
            $this->refreshtoken = !empty($config->refresh_token) ? $config->refresh_token : null;
            $this->accesstoken = !empty($config->access_token) ? $config->access_token : null;
        }
    }

    public function getaccesstokenwithcode($code) {
        global $CFG;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
        $pluginconfig = get_config(self::PLUGIN_NAME);
        $authorization = base64_encode($pluginconfig->consumer_key . ":" . $pluginconfig->consumer_secret);
        $headers = [
            'Authorization: Basic ' . $authorization,
            'Accept:application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $redirecturl = $CFG->wwwroot . '/mod/gotomeeting/oauthCallback.php';
        $data = ['redirect_uri' => $redirecturl, 'grant_type' => 'authorization_code', 'code' => $code];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $serveroutput = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($serveroutput);

        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) &&
                isset($response->organizer_key) && isset($response->account_key)) {
            set_config(self::ACCESS_TOKEN, $response->access_token, self::PLUGIN_NAME);
            set_config(self::REFRESH_TOKEN, $response->refresh_token, self::PLUGIN_NAME);
            set_config(self::ORGANISER_KEY, $response->organizer_key, self::PLUGIN_NAME);
            set_config(self::ACCESS_TOKEN_TIME, time(), self::PLUGIN_NAME);
            set_config(self::ACCOUNT_KEY, $response->account_key, self::PLUGIN_NAME);
            return true;
        } else {
            return false;
        }
    }

    public function getaccesstokenwithrefreshtoken($refreshtoken) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
        $gotowebinarconfig = get_config('gotowebinar');

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $refreshtoken];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($serveroutput);

        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) &&
                isset($response->organizer_key) && isset($response->account_key)) {
            set_config(self::ACCESS_TOKEN, $response->access_token, self::PLUGIN_NAME);
            set_config(self::REFRESH_TOKEN, $response->refresh_token, self::PLUGIN_NAME);
            set_config(self::ACCESS_TOKEN_TIME, time(), self::PLUGIN_NAME);

            $this->accesstoken = $response->access_token;
            $this->refreshtoken = $response->refresh_token;

            $this->accesstokentime = time();

            return $response->access_token;
        }
        return false;
    }

    public function getaccesstoken() {
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);
        if (isset($gotowebinarconfig->access_token_time) && !empty($gotowebinarconfig->access_token_time) &&
                $gotowebinarconfig->access_token_time + self::EXPIRY_TIME_IN_SECOND > time()) {
            return $gotowebinarconfig->access_token;
        } else {
            return $this->getaccesstokenwithrefreshtoken($gotowebinarconfig->refresh_token);
        }
    }

    public function post($endpoint, $data) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);

        curl_close($ch);

        return json_decode($serveroutput);
    }

    public function put($endpoint, $data) {
        global $CFG;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($serveroutput);
        return true;
    }

    public function get($endpoint) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);

        curl_close($ch);

        return json_decode($serveroutput);
    }

    public function delete($endpoint, $data) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);
        $accesstoken = $gotowebinarconfig->access_token;

        $headers = [
            'Authorization: Bearer ' . $accesstoken
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($serveroutput);
    }

    public function getsetupstatus() {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
        $gotowebinarconfig = get_config('gotowebinar');

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $this->refreshtoken];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $serveroutput = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        curl_close($ch);

        if ($chinfo['http_code'] === 200) {

            return json_decode($serveroutput);
        }

        return false;
    }

    public static function encode_attributes($attributes) {

        $return = array();
        foreach ($attributes as $key => $value) {
            $return[] = urlencode($key) . '=' . urlencode($value);
        }
        return join('&', $return);
    }

}
