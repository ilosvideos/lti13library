<?php
namespace IMSGlobal\LTI;

class LtiException extends \Exception {

    public $details;

    public function __construct($message = '', $code = 0, Throwable $previous = null, $details = null) {
        $this->details = $details;
        parent::__construct($message, $code, $previous);
    }

    public function getDetails() {
        return $this->details;
    }
}
?>
