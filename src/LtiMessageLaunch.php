<?php
namespace IMSGlobal\LTI;

include_once("LtiException.php");
include_once("LtiNamesRolesProvisioningService.php");
include_once("LtiAssignmentsGradesService.php");
include_once("LtiServiceConnector.php");
require_once('LtiDeepLink.php');
include_once("Cookie.php");
include_once("Cache.php");
include_once("MessageValidator.php");
include_once("LtiGrade.php");
include_once("LtiLineItem.php");

use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;

class LtiMessageLaunch {

    private $db;
    private $cache;
    public $request;
    private $cookie;
    private $jwt;
    public $registration;
    public $launch_id;

    /**
     * Constructor
     *
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches. If non is provided launch data will be store in $_SESSION.
     * @param Cookie    $cookie     Instance of the Cookie interface used to set and read cookies. Will default to using $_COOKIE and setcookie.
     */
    function __construct(Database $database, Cache $cache = null, Cookie $cookie = null) {
        $this->db = $database;

        $this->launch_id = uniqid("lti1p3_launch_");

        if ($cache === null) {
            $cache = new Cache();
        }
        $this->cache = $cache;

        if ($cookie === null) {
            $cookie = new Cookie();
        }
        $this->cookie = $cookie;
    }

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new(Database $database, Cache $cache = null, Cookie $cookie = null) {
        return new LtiMessageLaunch($database, $cache, $cookie);
    }

    /**
     * Load an LtiMessageLaunch from a Cache using a launch id.
     *
     * @param string    $launch_id  The launch id of the LtiMessageLaunch object that is being pulled from the cache.
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches. If non is provided launch data will be store in $_SESSION.
     *
     * @throws LtiException        Will throw an LtiException if validation fails or launch cannot be found.
     * @return LtiMessageLaunch   A populated and validated LtiMessageLaunch.
     */
    public static function from_cache($launch_id, Database $database, Cache $cache = null) {
        $new = new LtiMessageLaunch($database, $cache, null);
        $new->launch_id = $launch_id;
        $new->jwt = [ 'body' => $new->cache->get_launch_data($launch_id) ];
        return $new->validate_registration();
    }

    /**
     * Validates all aspects of an incoming LTI message launch and caches the launch if successful.
     *
     * @param array|string  $request    An array of post request parameters. If not set will default to $_POST.
     *
     * @throws LtiException        Will throw an LtiException if validation fails.
     * @return LtiMessageLaunch   Will return $this if validation is successful.
     */
    public function validate(array $request = null) {

        if ($request === null) {
            $request = $_POST;
        }

        $this->request = $request;

        return $this->validate_state()
            ->validate_jwt_format()
            ->validate_nonce()
            ->validate_registration()
            ->validate_jwt_signature()
            ->validate_deployment()
            ->validate_message()
            ->cache_launch_data();
    }

    /**
     * Returns whether or not the current launch can use the names and roles service.
     *
     * @return boolean  Returns a boolean indicating the availability of names and roles.
     */
    public function has_nrps() {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice']['context_memberships_url']);
    }

    /**
     * Fetches an instance of the names and roles service for the current launch.
     *
     * @return LtiNamesRolesProvisioningService An instance of the names and roles service that can be used to make calls within the scope of the current launch.
     */
    public function get_nrps() {
        return new LtiNamesRolesProvisioningService(
            new LTIServiceConnector($this->registration),
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice']);
    }

    /**
     * Returns whether or not the current launch can use the assignments and grades service.
     *
     * @return boolean  Returns a boolean indicating the availability of assignments and grades.
     */
    public function has_ags() {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint']);
    }

    /**
     * Fetches an instance of the assignments and grades service for the current launch.
     *
     * @return LtiAssignmentsGradesService An instance of the assignments an grades service that can be used to make calls within the scope of the current launch.
     */
    public function get_ags() {
        return new LtiAssignmentsGradesService(
            new LTIServiceConnector($this->registration),
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint']);
    }

    /**
     * Fetches a deep link that can be used to construct a deep linking response.
     *
     * @return LtiDeepLink An instance of a deep link to construct a deep linking response for the current launch.
     */
    public function get_deep_link() {
        return new LtiDeepLink(
            $this->registration,
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id'],
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings']);
    }

    /**
     * Returns whether or not the current launch is a deep linking launch.
     *
     * @return boolean  Returns true if the current launch is a deep linking launch.
     */
    public function is_deep_link_launch() {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiDeepLinkingRequest';
    }

    /**
     * Returns whether or not the current launch is a resource launch.
     *
     * @return boolean  Returns true if the current launch is a resource launch.
     */
    public function is_resource_launch() {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiResourceLinkRequest';
    }

    /**
     * Fetches the decoded body of the JWT used in the current launch.
     *
     * @return array|object Returns the decoded json body of the launch as an array.
     */
    public function get_launch_data() {
        return $this->jwt['body'];
    }

    /**
     * Get the unique launch id for the current launch.
     *
     * @return string   A unique identifier used to re-reference the current launch in subsequent requests.
     */
    public function get_launch_id() {
        return $this->launch_id;
    }

    private function validate_jwt_format() {
        $jwt = $this->request['id_token'];

        if (empty($jwt)) {
            throw new LtiException("Missing id_token", 1);
        }

        // Get parts of JWT.
        $jwt_parts = explode('.', $jwt);

        if (count($jwt_parts) !== 3) {
            // Invalid number of parts in JWT.
            throw new LtiException("Invalid id_token, JWT must contain 3 parts", 1);
        }

        // Decode JWT headers.
        $this->jwt['header'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[0]), true);
        // Decode JWT Body.
        $this->jwt['body'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]), true);

        return $this;
    }

    private function get_public_key() {
        $key_set_url = $this->registration->get_key_set_url();

        $dargs= [
            "ssl"=> [
                "verify_peer"=>false,
                "verify_peer_name"=>false
            ],
            "http"=> [
                'timeout' => 60,
                'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/3.0.0.1'
            ]
        ];

        $context = stream_context_create($dargs);
        // Download key set
        $public_key_set = json_decode(file_get_contents($key_set_url, false, $context), true);

        if (empty($public_key_set)) {
            // Failed to fetch public keyset from URL.
            throw new LtiException("Failed to fetch public key", 1);
        }

        // Find key used to sign the JWT (matches the KID in the header)
        foreach ($public_key_set['keys'] as $key) {
            if ($key['kid'] == $this->jwt['header']['kid']) {
                try {
                    return openssl_pkey_get_details(JWK::parseKey($key));
                } catch(\Exception $e) {
                    return false;
                }
            }
        }

        // Could not find public key with a matching kid and alg.
        throw new LtiException("Unable to find public key", 1);
    }

    private function cache_launch_data() {
        $this->cache->cache_launch_data($this->launch_id, $this->jwt['body']);
        return $this;
    }

    private function validate_state() {
        // Check State for OIDC.
        if ($this->cookie->get_cookie('lti1p3_' . $this->request['state']) !== $this->request['state']) {
            // Error if state doesn't match
            throw new LtiException("State not found", 1);
        }
        return $this;
    }

    private function validate_nonce() {
        if (!$this->cache->check_nonce($this->jwt['body']['nonce'])) {
            //throw new LtiException("Invalid Nonce");
        }
        return $this;
    }

    private function validate_registration() {
        // Find registration.
        $this->registration = $this->db->find_registration_by_issuer($this->jwt['body']['iss']);

        if (empty($this->registration)) {
            throw new LtiException("Registration not found.", 1);
        }

        // Check client id.
        $client_id = is_array($this->jwt['body']['aud']) ? $this->jwt['body']['aud'][0] : $this->jwt['body']['aud'];
        if ( $client_id !== $this->registration->get_client_id()) {
            // Client not registered.
            $details = 'expected: ' . $this->registration->get_client_id() . ' received: '. $client_id . ' jwt[\'body\']: ' . print_r($this->jwt['body'], true);
            throw new LtiException("Client id not registered for this issuer", 1, null, $details);
        }

        return $this;
    }

    private function validate_jwt_signature() {
        // Fetch public key.
        $public_key = $this->get_public_key();

        // Validate JWT signature
        try {
            JWT::$leeway = 20; // $leeway in seconds
            JWT::decode($this->request['id_token'], $public_key['key'], array('RS256'));
        } catch(\Exception $e) {
            var_dump($e);
            // Error validating signature.
            throw new LtiException("Invalid signature on id_token", 1);
        }

        return $this;
    }

    private function validate_deployment() {
        // Find deployment.
        $deployment = $this->db->find_deployment($this->jwt['body']['iss'], $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id']);

        if (empty($deployment)) {
            // deployment not recognized.
            throw new LtiException("Unable to find deployment", 1);
        }

        return $this;
    }

    private function validate_message() {
        if (empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'])) {
            // Unable to identify message type.
            throw new LtiException("Invalid message type", 1);
        }

        // Do message type validation

        // Import all validators
        foreach (glob(__DIR__ . "/message_validators/*.php") as $filename) {
            include_once $filename;
        }

        // Create instances of all validators
        $classes = get_declared_classes();
        $validators = array();
        foreach ($classes as $class_name) {
            // Check the class implements message validator
            $reflect = new \ReflectionClass($class_name);
            if ($reflect->implementsInterface('\IMSGlobal\LTI\MessageValidator')) {
                // Create instance of class
                $validators[] = new $class_name();
            }
        }

        $message_validator = false;
        foreach ($validators as $validator) {
            if ($validator->can_validate($this->jwt['body'])) {
                if ($message_validator !== false) {
                    // Can't have more than one validator apply at a time.
                    throw new LtiException("Validator conflict", 1);
                }
                $message_validator = $validator;
            }
        }

        if ($message_validator === false) {
            throw new LtiException("Unrecognized message type.", 1);
        }

        if (!$message_validator->validate($this->jwt['body'])) {
            throw new LtiException("Message validation failed.", 1);
        }

        return $this;

    }
}
?>
