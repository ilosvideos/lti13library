<?php
namespace IMSGlobal\LTI;

include_once("LtiRegistration.php");
include_once("LtiDeployment.php");

interface Database {
    public function find_registration_by_issuer($iss);
    public function find_deployment($iss, $deployment_id);
}

?>