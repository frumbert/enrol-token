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
 * Token user enrolment creation.
 *
 * @package    enrol_token
 * @copyright  2020 Tim St.Clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once ($CFG->dirroot.'/enrol/token/locallib.php');
require_once ($CFG->libdir . '/formslib.php');

$enrolid      	= optional_param('enrolid',0,PARAM_INT);
$roleid       	= optional_param('roleid',-1,PARAM_INT);
$courseid 		= optional_param('courseid',0,PARAM_INT);

if ($courseid > 0) { // look up using a course

	$course 		= $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
	$instance 		= $DB->get_record('enrol', array('enrol' => 'token', 'courseid' => $course->id), '*', MUST_EXIST);
	$enrolid 		= $instance->id;

} else if ($enrolid > 0) { // look up using an enrolment instance

	$instance 		= $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'token'), '*', MUST_EXIST);
	$course 		= $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);

} else { // unsupported use

	throw new moodle_exception('invalidaction');

}
$context 		= context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canconfigure 	= has_capability('enrol/token:config', $context);
$canmanage 		= has_capability('enrol/token:manage', $context);


if (!$canconfigure and !$canmanage) {
    // No need to invent new error strings here...
    require_capability('enrol/token:config', $context);
    require_capability('enrol/token:manage', $context);
}

if ($roleid < 0) {
    $roleid = $instance->roleid;
}
$roles = get_assignable_roles($context);
$roles = array('0'=>get_string('none')) + $roles;

if (!isset($roles[$roleid])) {
    // Weird - security always first!
    $roleid = 0;
}

if (!$enrol_token = enrol_get_plugin('token')) {
    throw new coding_exception('Can not instantiate enrol_token');
}

$instancename = $enrol_token->get_instance_name($instance);

$PAGE->set_url('/enrol/token/create.php', array('enrolid'=>$instance->id));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($enrol_token->get_instance_name($instance));
$PAGE->set_heading($course->fullname);

navigation_node::override_active_url(new moodle_url('/user/index.php', array('id'=>$course->id)));


// initialise the form using the enrolid so it works after postback
$url = new moodle_url('/enrol/token/create.php', ['enrolid' => $enrolid]);
$form = new create_enrol_tokens_form($url, ["instancename" => $instancename]);

if ($form->is_cancelled()) {
	$url = new moodle_url('/enrol/instances.php', ['id' => $course->id]);
	redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('create_token', 'enrol_token'));
echo html_writer::tag('p', "Course: {$course->fullname}, Enrolment instance: {$instancename}");


if (($data = $form->get_data()) === null) {

	$form->display();

} else {

	// make an array of tokens
	$tokens = enrol_token_manager_generate_token_data($data->tokennumber, $data->prefix);
	$feedback = '';

	// get or create the cohort
	$cohortid = (empty($data->cohortid) === true) ? 0 : $data->cohortid;
	if (!empty($data->cohortnew)) {
		$cohort_idnumber = preg_replace('/\s+/', '', strtolower(substr($data->cohortnew, 0, 99)));
		$cohortid = enrol_token_manager_create_cohort_id($data->cohortnew, $cohort_idnumber);
	} else {
		$cohortid = $data->cohortexisting;
	}

	// store the tokens in the database (transacted)
	enrol_token_manager_insert_tokens($cohortid, $course->id, $tokens, $data->seatspertoken, $data->expirydate);

	// construct a summary of this action to send
	$data->coursename = $course->fullname;
	$data->tokennumberplural = ($data->tokennumber > 1) ? 's' : '';
	$data->seatspertokenplural = ($data->seatspertoken > 1) ? 's' : '';
	$data->wwwroot = $CFG->wwwroot;
	$data->tokens = implode(PHP_EOL, $tokens);
	$data->adminsignoff = generate_email_signoff();

	// get text to use for on-screen notice and email
	$array = (array) $data;
	$array = array_combine(
		array_map(function($k){ return '{'.$k.'}'; }, array_keys($array)),
		$array
	);
	$messagehtml = format_text($data->mailbody['text']);
	unset($array['{mailbody}']);
	$messagehtml = str_replace(array_keys($array), array_values($array), $messagehtml);
	$messagetext = html_to_text($messagehtml, 75, false);

	// queue email for sending if required
	if ((isset($data->emailaddress) === true) && (trim($data->emailaddress != ''))) {

		// create a fake user to send email to because the email recipient may not be a system user (yet)
		$fakeUser = new stdClass();
		$fakeUser->id = -1;
		$fakeUser->deleted = false;
		$fakeUser->mailformat = 1;
		$fakeUser->email = $data->emailaddress;
		$fakeUser->firstname = "";
		$fakeUser->username = "";
		$fakeUser->lastname =  "";
		$fakeUser->confirmed = 1;
		$fakeUser->suspended = 0;
		$fakeUser->deleted = 0;
		$fakeUser->picture = 0;
		$fakeUser->auth = "manual";
		$fakeUser->firstnamephonetic = "";
		$fakeUser->lastnamephonetic =  "";
		$fakeUser->middlename =  "";
		$fakeUser->alternatename =  "";
		$fakeUser->imagealt =  "";
		$fakeUser->maildisplay = 1;
		$fakeUser->emailstop = 0;


		// if email fails to send - warn token creating user on screen
		// function email_to_user($user, $from, $subject, $messagetext, $messagehtml = '', $attachment = '', $attachname = '',
        //                $usetrueaddress = true, $replyto = '', $replytoname = '', $wordwrapwidth = 79) {

		if (email_to_user($fakeUser, core_user::get_support_user(), $data->mailsubject, $messagetext, $messagehtml) === false) {
			$feedback = $OUTPUT->error_text('Warning - there was a problem automatcially emailing the token code(s).');
		}
	}

	echo $OUTPUT->container_start('results');
	echo html_writer::tag('h3', get_string('create_token_result_header', 'enrol_token'));
	if (!empty($feedback)) echo html_writer::tag('p', $feedback);
	$table = new html_table();
	$table->head = array(get_string('create_token_tokens', 'enrol_token'));
	$table->attributes['class'] = 'admintable generaltable';
	$table->id = 'createtokens';
	$table->data = [];
	foreach ($tokens as $row) $table->data[] = [$row];
	echo html_writer::table($table);
	echo $OUTPUT->single_button(new moodle_url('/enrol/token/manage.php', array('enrolid' => $enrolid, 'execute' => 1)), get_string('view_token_usage', 'enrol_token'), 'get');
	echo $OUTPUT->container_end();

}

echo $OUTPUT->footer();


/* ------------------------------------------------------------------------------------- */


