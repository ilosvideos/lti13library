<?php
namespace IMSGlobal\LTI;

class Cookie {
    public function get_cookie($name) {
        return $_COOKIE[$name];
    }

    public function set_cookie($name, $value, $exp = 14400) {
        setcookie($name, $value, [
            'expires' => time() + $exp,
            'samesite' => 'none',
            'path' => '/',
            'secure' => true
        ]);

        return $this;
    }
}
?>