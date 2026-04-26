<?php
// This file is part of Moodle - http://moodle.org/
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

declare(strict_types=1);

namespace enrol_token\form;

use core\context\course as context_course;
use core\context\system as context_system;
use core_form\dynamic_form;
use core_text;
use html_writer;
use moodle_url;

/**
 * Form for entering password for token enrolment
 *
 * @package    enrol_token
 * @copyright  tim.stclair@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_form extends dynamic_form {
    /** @var \stdClass */
    protected $instance;
    /** @var \enrol_token_plugin */
    protected $plugin = null;

    /**
     * Returns the enrolment method
     *
     * @return \enrol_token_plugin
     */
    protected function get_plugin(): \enrol_token_plugin {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        if ($this->plugin === null) {
            $this->plugin = enrol_get_plugin('token');
        }
        return $this->plugin;
    }

    /**
     * Returns the instance of the enrolment method
     *
     * @return \stdClass
     */
    protected function get_instance(): \stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        if ($this->instance === null) {
            // Method enrol_get_instances() will also validate that the enrolment method and the instance are enabled.
            $courseid = $this->optional_param('id', 0, PARAM_INT);
            $instanceid = $this->optional_param('instance', 0, PARAM_INT);
            $instances = enrol_get_instances($courseid, true);
            $this->instance = $instances[$instanceid] ?? null;
            if (empty($this->instance) || $this->instance->enrol !== 'token') {
                throw new \moodle_exception('invalidenrolinstance', 'enrol');
            }
        }
        return $this->instance;
    }

    #[\Override]
    public function definition() {
        global $USER, $OUTPUT, $CFG;

        $mform = $this->_form;

        $attribs = ['size' => '15', 'maxlength' => '15']; // largest token string according to db
        $mform->addElement('text', 'enroltoken', get_string('entertoken', 'enrol_token'), $attribs);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/enrol/token/lib.php');

        $errors = parent::validation($data, $files);

        $tve = \enrol_token_plugin::getTokenValidationErrors($data['enroltoken'], $data['id']);
        if ($tve != '') {
          $errors['enroltoken'] = $tve;
        }
        return $errors;
    }

    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        global $USER, $CFG;
        $instance = $this->get_instance();
        $courseid = $instance->courseid;
        $course = get_course($courseid);
        $context = context_course::instance($instance->courseid);
        if (!\core_course_category::can_view_course_info($course) && !is_enrolled($context, $USER, '', true)) {
            throw new \moodle_exception('coursehidden', '', $CFG->wwwroot . '/');
        }
        if (isguestuser()) {
            throw new \moodle_exception('noguestaccess', 'enrol');
        }
        $canselfenrol = $this->get_plugin()->can_token_enrol($instance);
        if ($canselfenrol !== true) {
            throw new \moodle_exception($canselfenrol);
        }
    }

    #[\Override]
    protected function get_context_for_dynamic_submission(): \context {
        // This form is used for users who are not yet enrolled in the course and do not have access to the course.
        // For the purpose of permission checks they must be able to access the course category for this course.
        return context_course::instance($this->get_instance()->courseid)->get_parent_context();
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $instance = $this->get_instance();
        return new moodle_url('/enrol/index.php', ['id' => $instance->courseid, 'instance' => $instance->id]);
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * Enrols the user in the course and returns the URL to redirect to
     *
     * @return string
     */
    public function process_dynamic_submission() {
        global $CFG, $SESSION, $USER;
        require_once($CFG->dirroot.'/enrol/token/lib.php');

        $data = $this->get_data();
        $tokenValue = $data->enroltoken;

        // $token_plugin = new \enrol_token_plugin(); // same as $this->get_plugin()
        $enrolled_ok = $this->get_plugin()->perform_trusted_enrolment($tokenValue, $USER);

        // Go to the originally requested page.
        if (!empty($SESSION->wantsurl)) {
            $destination = $SESSION->wantsurl;
            unset($SESSION->wantsurl);
        } else {
            require_once($CFG->dirroot . '/course/lib.php');
            $destination = course_get_url($this->get_instance()->courseid);
        }

        // validate that the course that the token was just used for is the course we are going to and modify if required
        $courseid = $this->get_plugin()->get_courseid_for_token($tokenValue);
        if ($destination instanceof \moodle_url) {
            $destinationpath = $destination->get_path();
            if ($destinationpath === '/course/view.php') {
                $destinationcourseid = $destination->get_param('id');
                if ($destinationcourseid && $destinationcourseid != $courseid) {
                    $destination = course_get_url($courseid); // token submitted was for a different course - go there instead
                }
            }
        } else if (is_string($destination)) {
            // Handle string URLs
            $parsedurl = parse_url($destination);
            if (isset($parsedurl['path']) && $parsedurl['path'] === '/course/view.php') {
                parse_str($parsedurl['query'] ?? '', $queryparams);
                if (isset($queryparams['id']) && $queryparams['id'] != $courseid) {
                    $destination = course_get_url($courseid);
                }
            }
        }

        return $destination;
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $instance = $this->get_instance();
        $this->set_data(['id' => $instance->courseid, 'instance' => $instance->id]);
    }
}
