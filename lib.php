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
 * token enrolment plugin.
 *
 * @package    enrol_token
 * @copyright  2020 tim st. clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/cohort/lib.php");

class enrol_token_plugin extends enrol_plugin
{

    protected $lasternoller = null;
    protected $lasternollerinstanceid = 0;

    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/token:config', $context);
    }

    /**
     * Returns localised name of enrol instance
     *
     * @param stdClass $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB;

        if (empty($instance->name)) {
            if (!empty($instance->roleid) and $role = $DB->get_record('role', array('id' => $instance->roleid))) {
                $role = ' (' . role_get_name($role, context_course::instance($instance->courseid, IGNORE_MISSING)) . ')';
            }
            else {
                $role = '';
            }
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_' . $enrol) . $role;
        }
        else {
            return format_string($instance->name);
        }
    }

    public function roles_protected() {

        // Users may tweak the roles later.
        return false;
    }

    public function allow_unenrol(stdClass $instance) {

        // Users with unenrol cap may unenrol other users manually manually.
        return true;
    }

    public function allow_manage(stdClass $instance) {

        // Users with manage cap may tweak period and status.
        return true;
    }

    public function show_enrolme_link(stdClass $instance) {
        global $CFG, $USER;

        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return false;
        }

        if (!$instance->customint6) {

            // New enrols not allowed.
            return false;
        }

        return true;
    }

    /**
     * Sets up navigation entries.
     *
     * @param stdClass $instancesnode
     * @param stdClass $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'token') {
            throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/token:config', $context)) {
            $managelink = new moodle_url('/enrol/token/edit.php', array('courseid' => $instance->courseid, 'id' => $instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'token') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();
        if (has_capability('enrol/token:manage', $context) or has_capability('enrol/token:config', $context)) {

            $managelink = new moodle_url("/enrol/token/manage.php", array('enrolid'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($managelink, new pix_icon('t/groupv', get_string('view_token_usage', 'enrol_token'), 'core', array('class'=>'iconsmall')));

            $createlink = new moodle_url("/enrol/token/create.php", array('enrolid'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($createlink, new pix_icon('t/switch_plus', get_string('create_token', 'enrol_token'), 'core', array('class'=>'iconsmall')));

            // $createlink = new moodle_url("/enrol/token/view.php", array('enrolid'=>$instance->id));
            // $icons[] = $OUTPUT->action_icon($createlink, new pix_icon('t/tags', get_string('view_token', 'enrol_token'), 'core', array('class'=>'iconsmall')));

        }
        if (has_capability('enrol/token:config', $context)) {
            $editlink = new moodle_url("/enrol/token/edit.php", array('courseid' => $instance->courseid, 'id' => $instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit'), 'core', array('class' => 'iconsmall')));
        }

        $parenticons = parent::get_action_icons($instance);
        $icons = array_merge($icons, $parenticons);
        return $icons;
    }


    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/token:config', $context)) {
            return NULL;
        }

        // Multiple instances supported - different roles
        return new moodle_url('/enrol/token/edit.php', array('courseid' => $courseid));
    }

    // returns default plugin values as an object rather than an array
    public function getDefaultValuesAsObject() {
        $settings = new stdClass();

        $tmpArray = $this->get_instance_defaults();
        foreach ($tmpArray as $key => $value) $settings->$key = $value;

        return $settings;
    }

    // if you want to validate a token before enrolling with it
    // $errors['token'] = enrol_token_plugin::getTokenValidationErrors($tokenValue);
    // returns: a string containing the error message, or empty.
    public static function getTokenValidationErrors($value) {
        global $DB;
        if ($row = $DB->get_record('enrol_token_tokens', array('id' => $value), 'courseid, seatsavailable, numseats, timeexpire')) {
            $inst = self::getInstanceDataForCourse($row->courseid); // is static
            if (!$inst) {
                return 'Token enrolment is not yet set up for this course';
                 // that's a showstopper, for sure

            }
            else if ($row->seatsavailable <= 0) {
                return 'No places remaining on this token (' . $row->numseats . ' used)';
                 // get_string('noseatsavailable', 'enrol_token');

            }
            else if (($row->timeexpire != 0) && ($row->timeexpire < time())) {
                return 'Token has expired';
                 //get_string('tokenexpired', 'enrol_token');

            }
            else if ($inst && enrol_token_plugin::isThrottled($value, $inst) === true) {
                return get_string('toomanyattempts', 'enrol_token');
            }
            else if ($inst && $inst->enrolstartdate != 0 and $inst->enrolstartdate > time()) {
                return 'Enrolment begins ' . userdate($inst->enrolstartdate);
            }
            else if ($inst && $inst->enrolstartdate != 0 and $inst->enrolstartdate < time()) {
                return 'Enrolment ended ' . userdate($inst->enrolstartdate);
            }
            else if ($inst && !$inst->customint6) {
                return 'Tokens have been disabled';
            }
            return '';
        }
        else {
            return 'Invalid token (not found)';
        }
    }

    // check that ip address or user haven't entered too many enrolment tokens in a given period - this tries to stop token-guessing (manual or automated) attempts
    public static function isThrottled($token, $settings, $userId = null) {
        global $DB;

        // ensure userId has a sane value
        if ($userId === null) $userId = 0;
        $ip_throttling_period = isset($settings->ipthrottlingperiod) ? $settings->ipthrottlingperiod : 0;
        $user_throttling_period = isset($settings->userthrottlingperiod) ? $settings->userthrottlingperiod : 0;

        $insert_record = true;

        // add a token usage record only if token in question hasn't been used recently by this user
        if (($ip_throttling_period > 0) && ($DB->record_exists_select('enrol_token_usage', 'ip = \'' . getremoteaddr() . '\' AND token = \'' . $token . '\' AND timecreated > ' . (time() - ($ip_throttling_period * 60))) === false)) {
            if (($userId !== 0) && ($user_throttling_period > 0) && ($DB->record_exists_select('enrol_token_usage', 'userid = ' . $userId . ' AND token = \'' . $token . '\'  AND timecreated > ' . (time() - ($user_throttling_period * 60))) === false)) {

                // add token usage record
                $tokenUsageRec = new stdClass();
                $tokenUsageRec->token = $token;
                $tokenUsageRec->userid = $userId;
                $tokenUsageRec->ip = getremoteaddr();
                $tokenUsageRec->timecreated = time();
                $DB->insert_record('enrol_token_usage', $tokenUsageRec, false);
            }
        }

        // check if ip address should be throttled
        if (($ip_throttling_period > 0) && ($DB->count_records_select('enrol_token_usage', 'ip = \'' . getremoteaddr() . '\' AND timecreated > ' . (time() - ($ip_throttling_period * 60))) > 10)) {
            return true;
        }

        // check if user should be throttled
        if (($userId !== 0) && ($user_throttling_period > 0) && ($DB->count_records_select('enrol_token_usage', 'userid = ' . $userId . ' AND timecreated > ' . (time() - ($user_throttling_period * 60))) > 10)) {
            return true;
        }

        return false;
    }

    // ensures plugin settings are allowing enrolments
    public function isEnrolable($settings) {

        // can not enrol guest!!
        if (isguestuser()) return 1;

        // can not enrol yet
        if ($settings->enrolstartdate != 0 and $settings->enrolstartdate > time()) return 3;

        // enrolment is not possible any more
        if ($settings->enrolenddate != 0 and $settings->enrolenddate < time()) return 4;

        // new enrols not allowed
        if (!$settings->customint6) return 5;

        return true;
    }

    // returns token enrolment instance data for a given course
    protected static function getInstanceDataForCourse($courseId) {

        // get all enrol plugins available for course
        $enrolinstances = enrol_get_instances($courseId, true);
        foreach ($enrolinstances as $instance) {
            if ((isset($instance->enrol) === true) && ($instance->enrol == 'token')) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * checks that token is enrolable and enrols user
     * assumes that $USER and $SESSION are set up correctly - so enrols the current user (you can't specify one)
     *
     * @param string tokenValue containing token value
     * @param integer courseId  course that the token is for; will be set (byref)
     * @param string returnToUrl url to set WantsUrl for post-enrolment redirect
     * @return mixed true if successful, numerical error code if not
     */
    public function doEnrolment($tokenValue, &$courseId, $returnToUrl = null) {
        global $DB, $USER, $SESSION, $CFG;

        // get token record
        $tokenRec = $DB->get_record('enrol_token_tokens', array('id' => $tokenValue), 'courseid,seatsavailable,timeexpire,cohortid');

        // use default plugin values for instance data if no token record available (required for throttling values)
        $settings = ($tokenRec === false) ? $this->getDefaultValuesAsObject() : $this->getInstanceDataForCourse($tokenRec->courseid);

        // map crappy customint field names to descriptive field names
        if (isset($settings->ipthrottlingperiod) === false) $settings->ipthrottlingperiod = $settings->customint1;
        if (isset($settings->userthrottlingperiod) === false) $settings->userthrottlingperiod = $settings->customint3;

        // check if ip address or user is throttled from entering tokens
        if ($this->isThrottled($tokenValue, $settings, $USER->id) === true) {
            return 1;
        }

        // did token record exist? - this has to be checked *after* throttling is taken care of
        if ($tokenRec === false) {
            return 2;
        }

        // things we need
        $courseId = $tokenRec->courseid;
        $cohortid = $tokenRec->cohortid;

        // ensure course is enrollable using token
        $retVal = $this->isEnrolable($settings);
        if ($retVal != true) {
            return (10 + $retVal);
        }

        // user already enrolled in course? return SUCCESS
        var_dump('token rec course ' . $tokenRec->courseid);
        if ((isloggedin() === true) && (is_enrolled(context_course::instance($tokenRec->courseid), $USER, '', true) === true)) {
            return true;
        }

        // require user login at this point
        $SESSION->wantsurl = $returnToUrl;
        require_login(null, false, null, ($returnToUrl === null));

        // are seats available on token?
        if ($tokenRec->seatsavailable <= 0) {
            return 3;
        }

        // has token expired?
        if (($tokenRec->timeexpire != 0) && ($tokenRec->timeexpire < time())) {
            return 4;
        }

        // if an enrolment period has been defined, set it for this enrolment
        $timestart = time();
        $timeend = ($settings->enrolperiod) ? ($timestart + $settings->enrolperiod) : 0;

        // start db transaction that can be rolled back if any issues
        if (($transaction = $DB->start_delegated_transaction()) === null) throw new coding_exception('Invalid delegated transaction object');

        try {

            // enrol the user in the course
            $this->enrol_user($settings, $USER->id, $settings->roleid, $timestart, $timeend);

            // add the user to the cohort for this enrolment (cohort/lib.php)
            cohort_add_member($cohortid, $USER->id);

            // record this token enrolment for this user so we have an easy track of its usage
            $this->record_token($USER->id, $tokenValue);

            // decrement the seats available on the token
            $tokenRec->seatsavailable--;

            // update the token's persistent store record with the new seatsavailable value
            $tokenRec->id = $tokenValue;

            // update token record, on failure to update throw exception to force a rollback
            if ($DB->execute("UPDATE {enrol_token_tokens} SET seatsavailable = (seatsavailable - 1) WHERE id = '{$tokenValue}' AND seatsavailable > 0") === false) throw new Exception('', -5150);

            // commit the transaction
            $transaction->allow_commit();
        }
        catch(Exception $e) {
            $transaction->rollback($e);

            // if token got used up between first check and our update, return same error value as previous 'no seats available' error
            if ($e->code == -5150) {
                return 3;
            }

            return 5;
        }

        // send welcome message
        if ($settings->customint4) $this->email_welcome_message($settings, $USER);

        return true;

        // return SUCCESS


    }

   /**
     * assumes that the token and course is set up and is valid (i.e. has been checked, has seats remaining, etc)
     *
     * @param string tokenValue containing token value
     * @param user user user record
     * @return boolean true if enrolment was a success, false if not
     */
 
    public function perform_trusted_enrolment($token, $user) {
        global $DB;

        // set up objects we require
        $tokenRec = $DB->get_record('enrol_token_tokens', array('id' => $token), 'courseid,seatsavailable,timeexpire,cohortid');
        $courseId = $tokenRec->courseid;

        $settings = ($tokenRec === false) ? $this->getDefaultValuesAsObject() : $this->getInstanceDataForCourse($courseId);
        $cohortid = $tokenRec->cohortid;

        // user already enrolled in course? return SUCCESS
        if (is_enrolled(context_course::instance($courseId), $user, '', true) === true) return true;

        // if an enrolment period has been defined, set it for this enrolment
        $timestart = time();
        $timeend = ($settings->enrolperiod) ? ($timestart + $settings->enrolperiod) : 0;

        // start db transaction that can be rolled back if any issues
        if (($transaction = $DB->start_delegated_transaction()) === null) throw new coding_exception('Invalid delegated transaction object');

        try {

            // enrol the user in the course
            $this->enrol_user($settings, $user->id, $settings->roleid, $timestart, $timeend);

            // add the user to the cohort for this enrolment (cohort/lib.php)
            cohort_add_member($cohortid, $user->id);

            // record this token enrolment for this user so we have an easy track of its usage
            $this->record_token($user->id, $token);

            // decrement the seats available on the token
            $tokenRec->seatsavailable--;

            // update the token's persistent store record with the new seatsavailable value
            $tokenRec->id = $token;

            // update token record, on failure to update throw exception to force a rollback
            if ($DB->execute("UPDATE {enrol_token_tokens} SET seatsavailable = (seatsavailable - 1) WHERE id = '{$token}' AND seatsavailable > 0") === false) throw new Exception('', -5150);

            // commit the transaction
            $transaction->allow_commit();
        }
        catch(Exception $e) {
            $transaction->rollback($e);
            return false;
        }

        return true;

    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $CFG, $OUTPUT;

        require_once ("$CFG->dirroot/enrol/token/locallib.php");

        // process form
        $form = new enrol_token_enrol_form(NULL, $instance);

        // has form been posted back with a token value
        if (($data = $form->get_data()) && (empty($data->enroltoken) === false)) {

            // enrol the user using the token
            $courseId = 0;
            $tokenError = $this->doEnrolment($data->enroltoken, $courseId);

            // if there was an error, report it to user
            if ($tokenError !== true) {
                switch ($tokenError) {
                    case (1):
                        $tokenError = get_string('toomanyattempts', 'enrol_token');
                        break;

                    case (2):
                        $tokenError = get_string('tokendoesntexist', 'enrol_token');
                        break;

                    case (3):
                        $tokenError = get_string('noseatsavailable', 'enrol_token');
                        break;

                    case (4):
                        $tokenError = get_string('tokenexpired', 'enrol_token');
                        break;

                    case (5):
                        $tokenError = get_string('databaseerror', 'enrol_token');
                        break;

                    default:
                        $tokenError = get_string('notenrolable', 'enrol_token');
                        break;
                }

                $form->setElementError('enroltoken', $tokenError);
            }
        }

        ob_start();
        $form->display();
        $output = ob_get_clean();

        return $OUTPUT->box($output);
    }

    private function record_token($userid, $token) {
        global $DB;
        $record = new stdClass();
        $record->token = $token;
        $record->userid = $userid;
        $record->timecreated = time();
        $DB->insert_record('enrol_token_log', $record, false);
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param stdClass $course
     * @return int id of new instance
     */
    public function add_default_instance($course) {
        $fields = $this->get_instance_defaults();

        return $this->add_instance($course, $fields);
    }

    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {
        $expirynotify = $this->get_config('expirynotify');
        if ($expirynotify == 2) {
            $expirynotify = 1;
            $notifyall = 1;
        }
        else {
            $notifyall = 0;
        }

        $fields = array();
        $fields['status'] = $this->get_config('status');
        $fields['roleid'] = $this->get_config('roleid');
        $fields['enrolperiod'] = $this->get_config('enrolperiod');
        $fields['expirynotify'] = $expirynotify;
        $fields['notifyall'] = $notifyall;
        $fields['expirythreshold'] = $this->get_config('expirythreshold');
        $fields['ipthrottlingperiod'] = $this->get_config('ipthrottlingperiod');
        $fields['customint2'] = $this->get_config('longtimenosee');
        $fields['userthrottlingperiod'] = $this->get_config('userthrottlingperiod');
        $fields['customint4'] = $this->get_config('sendcoursewelcomemessage');
        $fields['customint6'] = $this->get_config('newenrols');

        return $fields;
    }

    /**
     * Send welcome email to specified user.
     *
     * @param stdClass $instance
     * @param stdClass $user user record
     * @return void
     */
    protected function email_welcome_message($instance, $user) {
        global $CFG, $DB;

        $course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, array('context' => $context));
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id&course=$course->id";

        if (trim($instance->customtext1) !== '') {
            $message = $instance->customtext1;
            $message = str_replace('{$a->coursename}', $a->coursename, $message);
            $message = str_replace('{$a->profileurl}', $a->profileurl, $message);
            if (strpos($message, '<') === false) {

                // Plain text only.
                $messagetext = $message;
                $messagehtml = text_to_html($messagetext, null, false, true);
            }
            else {

                // This is most probably the tag/newline soup known as FORMAT_MOODLE.
                $messagehtml = format_text($message, FORMAT_MOODLE, array('context' => $context, 'para' => false, 'newlines' => true, 'filter' => true));
                $messagetext = html_to_text($messagehtml);
            }
        }
        else {
            $messagetext = get_string('welcometocoursetext', 'enrol_token', $a);
            $messagehtml = text_to_html($messagetext, null, false, true);
        }

        $subject = get_string('welcometocourse', 'enrol_token', format_string($course->fullname, true, array('context' => $context)));
        $rusers = array();
        if (!empty($CFG->coursecontact)) {
            $croles = explode(',', $CFG->coursecontact);
            list($sort, $sortparams) = users_order_by_sql('u');
            $rusers = get_role_users($croles, $context, true, 'ra.id, r.sortorder, u.lastname, u.firstname, u.id', 'r.sortorder ASC, ' . $sort, null, '', '', '', '', $sortparams);
        }
        if ($rusers) {
            $contact = reset($rusers);
        }
        else {
            $contact = core_user::get_support_user();
        }

        // Directly emailing welcome message rather than using messaging.
        return email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
    }

    /**
     * Enrol token cron support.
     * @return void
     */
    public function cron() {
        $trace = new text_progress_trace();
        $this->sync($trace, null);
        $this->send_expiry_notifications($trace);
    }

    /**
     * Sync all meta course links.
     *
     * @param progress_trace $trace
     * @param int $courseid one course, empty mean all
     * @return int 0 means ok, 1 means error, 2 means plugin disabled
     */
    public function sync(progress_trace $trace, $courseid = null) {
        global $DB;

        if (!enrol_is_enabled('token')) {
            $trace->finished();
            return 2;
        }

        // Unfortunately this may take a long time, execution can be interrupted safely here.
        @set_time_limit(0);
        raise_memory_limit(MEMORY_HUGE);

        $trace->output('Verifying token-enrolments...');

        $params = array('now' => time(), 'useractive' => ENROL_USER_ACTIVE, 'courselevel' => CONTEXT_COURSE);
        $coursesql = "";
        if ($courseid) {
            $coursesql = "AND e.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        // Note: the logic of token enrolment guarantees that user logged in at least once (=== u.lastaccess set)
        //       and that user accessed course at least once too (=== user_lastaccess record exists).

        // First deal with users that did not log in for a really long time - they do not have user_lastaccess records.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'token' AND e.customint2 > 0)
                  JOIN {user} u ON u.id = ue.userid
                 WHERE :now - u.lastaccess > e.customint2
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint2 / 60 * 60 * 24;
            $trace->output("unenrolling user $userid from course $instance->courseid as they have did not log in for at least $days days", 1);
        }
        $rs->close();

        // Now unenrol from course user did not visit for a long time.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'token' AND e.customint2 > 0)
                  JOIN {user_lastaccess} ul ON (ul.userid = ue.userid AND ul.courseid = e.courseid)
                 WHERE :now - ul.timeaccess > e.customint2
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint2 / 60 * 60 * 24;
            $trace->output("unenrolling user $userid from course $instance->courseid as they have did not access course for at least $days days", 1);
        }
        $rs->close();

        $trace->output('...user token-enrolment updates finished.');
        $trace->finished();

        $this->process_expirations($trace, $courseid);

        return 0;
    }

    /**
     * Returns the user who is responsible for token enrolments in given instance.
     *
     * Usually it is the first editing teacher - the person with "highest authority"
     * as defined by sort_by_roleassignment_authority() having 'enrol/token:manage'
     * capability.
     *
     * @param int $instanceid enrolment instance id
     * @return stdClass user record
     */
    protected function get_enroller($instanceid) {
        global $DB;

        if ($this->lasternollerinstanceid == $instanceid and $this->lasternoller) {
            return $this->lasternoller;
        }

        $instance = $DB->get_record('enrol', array('id' => $instanceid, 'enrol' => $this->get_name()), '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);

        if ($users = get_enrolled_users($context, 'enrol/token:manage')) {
            $users = sort_by_roleassignment_authority($users, $context);
            $this->lasternoller = reset($users);
            unset($users);
        }
        else {
            $this->lasternoller = parent::get_enroller($instanceid);
        }

        $this->lasternollerinstanceid = $instanceid;

        return $this->lasternoller;
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol($instance) && has_capability("enrol/token:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class' => 'unenrollink', 'rel' => $ue->id));
        }
        if ($this->allow_manage($instance) && has_capability("enrol/token:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/edit', ''), get_string('edit'), $url, array('class' => 'editenrollink', 'rel' => $ue->id));
        }
        return $actions;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        }
        else {
            $merge = array('courseid' => $data->courseid, 'enrol' => $this->get_name(), 'roleid' => $data->roleid,);
        }
        if ($merge and $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        }
        else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Restore role assignment.
     *
     * @param stdClass $instance
     * @param int $roleid
     * @param int $userid
     * @param int $contextid
     */
    public function restore_role_assignment($instance, $roleid, $userid, $contextid) {

        // This is necessary only because we may migrate other types to this instance,
        // we do not use component in manual or token enrol.
        role_assign($roleid, $userid, $contextid, '', 0);
    }

    public function get_manual_enrol_button(course_enrolment_manager $manager) {
        if (is_siteadmin()) {

            $link = new moodle_url("/user/editadvanced.php", ["id" => "-1"]);
            $button = new single_button($link, get_string('addnewuser'), 'get');
            $button->class .= ' enrol_manual_plugin';

            return [null,$button]; // sUChgCguv
        }
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/self:config', $context);
    }

    /**
     * Returns link to manual enrol UI if exists.
     * Does the access control tests automatically.
     *
     * @param stdClass $instance
     * @return moodle_url
     */
    public function get_manual_enrol_link($instance) {
        $name = $this->get_name();
        if ($instance->enrol !== $name) {
            throw new coding_exception('invalid enrol instance!');
        }

        if (!enrol_is_enabled($name)) {
            return NULL;
        }

        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!has_capability('enrol/token:manage', $context)) {
            return NULL;
        }

        return new moodle_url('/enrol/token/manage.php', array('enrolid'=>$instance->id, 'id'=>$instance->courseid));
    }


}
