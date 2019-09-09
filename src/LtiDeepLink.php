<?php
namespace IMSGlobal\LTI;

require_once('LtiDeepLinkResource.php');
use \Firebase\JWT\JWT;
class LtiDeepLink {

    private $registration;
    private $deployment_id;
    private $deep_link_settings;

    public function __construct($registration, $deployment_id, $deep_link_settings) {
        $this->registration = $registration;
        $this->deployment_id = $deployment_id;
        $this->deep_link_settings = $deep_link_settings;
    }

    public function get_response_jwt($resources) {
        $message_jwt = [
            "iss" => $this->registration->get_client_id(),
            "aud" => [$this->registration->get_issuer()],
            "exp" => time() + 600,
            "iat" => time(),
            "nonce" => uniqid("nonce"),
            "https://purl.imsglobal.org/spec/lti/claim/deployment_id" => $this->deployment_id,
            "https://purl.imsglobal.org/spec/lti/claim/message_type" => "LtiDeepLinkingResponse",
            "https://purl.imsglobal.org/spec/lti/claim/version" => "1.3.0",
            "https://purl.imsglobal.org/spec/lti-dl/claim/content_items" => array_map(function($resource) { return $resource->to_array(); }, $resources),
            "https://purl.imsglobal.org/spec/lti-dl/claim/data" => $this->deep_link_settings['data'] ?? null,
        ];
        return JWT::encode($message_jwt, $this->registration->get_tool_private_key(), 'RS256');
    }

    public function output_response_form($resources) {
        $jwt = $this->get_response_jwt($resources);
        ?>
        <form id="auto_submit" action="<?= $this->deep_link_settings['deep_link_return_url']; ?>" method="POST">
            <input type="hidden" name="JWT" value="<?= $jwt ?>" />
            <input type="submit" name="Go" />
        </form>
        <script>
            document.getElementById('auto_submit').submit();
        </script>
        <?php
    }

    public function get_deep_link_return_url() {
        return $this->deep_link_settings['deep_link_return_url'];
    }
}
?>