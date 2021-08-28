<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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

    private $access_token;
    private $refresh_token;
    private $organizer_key;
    private $account_key;
    private $access_token_time;
    private $consumer_key;
    private $consumer_secret;

    function __construct() {

        $config = get_config(self::PLUGIN_NAME);
        if (isset($config)) {
            $this->organizer_key = !empty($config->organizer_key) ? $config->organizer_key : null;
            $this->refresh_token = !empty($config->refresh_token) ? $config->refresh_token : null;
            $this->access_token = !empty($config->access_token) ? $config->access_token : null;
        }
    }

    public function getAccessTokenWithCode($code) {
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

        // echo $authorization;
        $redirect_url = $CFG->wwwroot . '/mod/gotomeeting/oauthCallback.php';
        $data = ['redirect_uri' => $redirect_url, 'grant_type' => 'authorization_code', 'code' => $code];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $server_output = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($server_output);

        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) && isset($response->organizer_key) && isset($response->account_key)) {
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

    public function getAccessTokenWithRefreshToken($refreshToken) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
        $gotowebinarconfig = get_config('gotowebinar');

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($server_output);

        if (isset($response) && isset($response->access_token) && isset($response->refresh_token) && isset($response->organizer_key) && isset($response->account_key)) {
            set_config(self::ACCESS_TOKEN, $response->access_token, self::PLUGIN_NAME);
            set_config(self::REFRESH_TOKEN, $response->refresh_token, self::PLUGIN_NAME);
            set_config(self::ACCESS_TOKEN_TIME, time(), self::PLUGIN_NAME);

            $this->access_token = $response->access_token;
            $this->refresh_token = $response->refresh_token;

            $this->access_token_time = time();

            return $response->access_token;
        }
        return false;
    }

    function getAccessToken() {
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);
        if (isset($gotowebinarconfig->access_token_time) && !empty($gotowebinarconfig->access_token_time) && $gotowebinarconfig->access_token_time + self::EXPIRY_TIME_IN_SECOND > time()) {
            return $gotowebinarconfig->access_token;
        } else {
            return $this->getAccessTokenWithRefreshToken($gotowebinarconfig->refresh_token);
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

        // echo $authorization;

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);

        return json_decode($server_output);
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

        // echo $authorization;

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($server_output);
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

        $server_output = curl_exec($ch);

        curl_close($ch);

        return json_decode($server_output);
    }

    public function delete($endpoint, $data) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $gotowebinarconfig = get_config(self::PLUGIN_NAME);
        $access_token = $gotowebinarconfig->access_token;

        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($server_output);
    }

    public function getSetupStatus() {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . "/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, true);
        $gotowebinarconfig = get_config('gotowebinar');

        $headers = [
            'Authorization: Basic ' . base64_encode($gotowebinarconfig->consumer_key . ":" . $gotowebinarconfig->consumer_secret),
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = ['grant_type' => 'refresh_token', 'refresh_token' => $this->refresh_token];
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::encode_attributes($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        curl_close($ch);

        if ($chinfo['http_code'] === 200) {

            return json_decode($server_output);
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
