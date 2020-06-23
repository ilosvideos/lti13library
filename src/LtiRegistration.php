<?php
namespace IMSGlobal\LTI;

class LtiRegistration {

    public $issuer;
    public $client_id;
    public $key_set_url;
    public $key_id;
    public $auth_token_url;
    public $auth_login_url;
    public $tool_key_set;
    public $audience;
    private $tool_private_key;


    public static function new() {
        return new LtiRegistration();
    }

    public function get_issuer() {
        return $this->issuer;
    }

    public function set_issuer($issuer) {
        $this->issuer = $issuer;
        return $this;
    }

    public function get_client_id() {
        return $this->client_id;
    }

    public function set_client_id($client_id) {
        $this->client_id = $client_id;
        return $this;
    }

    public function get_key_set_url() {
        return $this->key_set_url;
    }

    public function set_key_set_url($key_set_url) {
        $this->key_set_url = $key_set_url;
        return $this;
    }

    public function get_key_id() {
        return $this->key_id;
    }

    public function set_key_id($key_id) {
        $this->key_id = $key_id;
        return $this;
    }

    public function get_auth_token_url() {
        return $this->auth_token_url;
    }

    public function set_auth_token_url($auth_token_url) {
        $this->auth_token_url = $auth_token_url;
        return $this;
    }

    public function get_auth_login_url() {
        return $this->auth_login_url;
    }

    public function set_auth_login_url($auth_login_url) {
        $this->auth_login_url = $auth_login_url;
        return $this;
    }

    public function get_tool_private_key() {
        return $this->tool_private_key;
    }

    public function set_tool_private_key($tool_private_key) {
        $this->tool_private_key = $tool_private_key;
        return $this;
    }

    public function get_audience() {
        return $this->audience;
    }

    public function set_audience($audience) {
        $this->audience = $audience;
        return $this;
    }
}

?>
