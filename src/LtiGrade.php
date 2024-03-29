<?php
namespace IMSGlobal\LTI;

class LtiGrade {
    private $score_given;
    private $score_maximum;
    private $activity_progress;
    private $grading_progress;
    private $timestamp;
    private $user_id;
    private $comment;
    private $submission_link;

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new() {
        return new LtiGrade();
    }

    public function get_score_given() {
        return $this->score_given;
    }

    public function set_score_given($value) {
        $this->score_given = $value;
        return $this;
    }

    public function get_score_maximum() {
        return $this->score_maximum;
    }

    public function set_score_maximum($value) {
        $this->score_maximum = $value;
        return $this;
    }

    public function get_activity_progress() {
        return $this->activity_progress;
    }

    public function set_activity_progress($value) {
        $this->activity_progress = $value;
        return $this;
    }

    public function get_grading_progress() {
        return $this->grading_progress;
    }

    public function set_grading_progress($value) {
        $this->grading_progress = $value;
        return $this;
    }

    public function get_timestamp() {
        return $this->timestamp;
    }

    public function set_timestamp($value) {
        $this->timestamp = $value;
        return $this;
    }

    public function get_user_id() {
        return $this->user_id;
    }

    public function set_user_id($value) {
        $this->user_id = $value;
        return $this;
    }

    public function get_comment() {
        return $this->comment;
    }

    public function set_comment($value) {
        $this->comment = $value;
        return $this;
    }

    public function get_submission_link() {
        return $this->submission_link;
    }

    public function set_submission_link($value) {
        $this->submission_link = $value;
        return $this;
    }

    public function __toString() {
        $filtered_array = array_filter([
            "scoreMaximum" => 0 + $this->score_maximum,
            "activityProgress" => $this->activity_progress,
            "gradingProgress" => $this->grading_progress,
            "comment" => $this->comment,
            "timestamp" => $this->timestamp,
            "userId" => $this->user_id,
            "https://canvas.instructure.com/lti/submission" => [
                "new_submission" => true,
                "submission_type" => "online_url",
                "submission_data" => $this->submission_link
            ]
        ]);
        if (isset($this->score_given)) { // "0" is a valid score to send.  But, array_filter treats it as falsy and strips out, so we need to handle it separately.
            $filtered_array["scoreGiven"] = 0 + $this->score_given;
        }
        return json_encode($filtered_array);
    }
}
?>
