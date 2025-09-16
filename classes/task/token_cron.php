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

/**
 * This task runs every couple of hours and sends followup emails to users who have completed a course
 * and who have not yet been emailed.
 * It relies on the 'followup_enabled', 'followup_template', 'followup_subject', and 'followup_delay' course custom fields
 *
 * @package   local_aurora
 * @copyright 2023 <tim.stclair@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 namespace enrol_token\task;

/**
 * An example of a scheduled task.
 */
class token_tasks extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('token_tasks', 'enrol_token');
    }

    public function execute() {
        global $CFG;

        require_once($CFG->dirroot.'/enrol/token/lib.php');
        mtrace("process_followups started");

        $token_plugin = new \enrol_token_plugin();

        $trace = new \text_progress_trace();
        $token_plugin->sync($trace, null);
        $token_plugin->send_expiry_notifications($trace);
    }
}