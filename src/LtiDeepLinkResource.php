<?php
namespace IMSGlobal\LTI;

class LtiDeepLinkResource {

    private $type = 'ltiResourceLink';
    private $title;
    private $url;
    private $lineitem;
    private $custom_params = [1 => 2];
    private $target = 'iframe';
    private $iframe;
    private $embed;
    private $available_start_date;
    private $available_end_date;

    public function new() {
        return new LtiDeepLinkResource();
    }

    public function get_type() {
        return $this->type;
    }

    public function set_type($value) {
        $this->type = $value;
        return $this;
    }

    public function get_title() {
        return $this->title;
    }

    public function set_title($value) {
        $this->title = $value;
        return $this;
    }

    public function get_url() {
        return $this->url;
    }

    public function set_url($value) {
        $this->url = $value;
        return $this;
    }

    public function get_lineitem() {
        return $this->lineitem;
    }

    public function set_lineitem($value) {
        $this->lineitem = $value;
        return $this;
    }

    public function get_custom_params() {
        return $this->custom_params;
    }

    public function set_custom_params($value) {
        $this->custom_params = $value;
        return $this;
    }

    public function get_target() {
        return $this->target;
    }

    public function set_target($value) {
        $this->target = $value;
        return $this;
    }

    public function set_iframe($value) {
        $this->iframe = $value;
        return $this;
    }

    public function set_embed($value) {
        $this->embed = $value;
        return $this;
    }

    public function set_available_start_date($value) {
        $this->available_start_date = $value;
        return $this;
    }

    public function set_available_end_date($value) {
        $this->available_end_date = $value;
        return $this;
    }


    public function to_array() {
        $resource = [
            "type" => $this->type,
            "title" => $this->title,
            "url" => $this->url,
            "embed" => [
                "html" => $this->embed
            ],
            "iframe" => $this->iframe,
            "presentation" => [
                "documentTarget" => $this->target,
            ],
            "custom" => $this->custom_params,
        ];
        if ($this->lineitem !== null) {
            $resource["lineItem"] = [
                "scoreMaximum" => $this->lineitem->get_score_maximum(),
                "label" => $this->lineitem->get_label(),
            ];
        }
        if($this->available_start_date || $this->available_end_date) {
            $resource["available"] = [
                "startDateTime" => $this->available_start_date,
                "endDateTime" => $this->available_end_date,
            ];
        }
        return $resource;
    }
}
?>
