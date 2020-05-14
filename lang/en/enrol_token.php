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
 * Strings for component 'enrol_token', language 'en'.
 *
 * @package    enrol_token
 * @copyright  2013 CourseSuite
 * @link http://coursesuite.ninja
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Link to user\'s profile page {$a->profileurl}';
$string['databaseerror'] = 'Sorry, a system error occurred whilst enroling with your token.';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during token enrolment';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can enrol themselves until this date only.';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';
$string['enrolme'] = 'Enrol';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user enrols themselves. If disabled, the enrolment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can enrol themselves from this date onward only.';
$string['expiredaction'] = 'Enrolment expiration action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['expirymessageenrollersubject'] = 'Token enrolment expiry notification';
$string['expirymessageenrollerbody'] = 'Token enrolment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:

{$a->users}

To extend their enrolment, go to {$a->extendurl}';
$string['expirymessageenrolledsubject'] = 'Token enrolment expiry notification';
$string['expirymessageenrolledbody'] = 'Dear {$a->user},

This is a notification that your enrolment in the course \'{$a->course}\' is due to expire on {$a->timeend}.

If you need help, please contact {$a->enroller}.';
$string['ipthrottlingperiod'] = 'IP throttling period (mins)';
$string['ipthrottlingperiod_desc'] = 'The period, in minutes, that a client IP address can be used to enter 10 tokens before they are disallowed from entering more';
$string['ipthrottlingperiod_help'] = 'Use this value to stop possible token-guessing attempts. ' .
                                     'For instance, a value of 20 means that an IP address can only be used to try a maximum of 10 tokens in a 20 minute period. ' .
                                     'Lower values are more restrictive ' .
                                     'A value of 0 turns IP throttling off.';
$string['longtimenosee'] = 'Unenrol inactive after';
$string['longtimenosee_help'] = 'If users haven\'t accessed a course for a long time, then they are automatically unenrolled. This parameter specifies that time limit.';
$string['messageprovider:expiry_notification'] = 'Token enrolment expiry notifications';
$string['newenrols'] = 'Allow new enrolments';
$string['newenrols_desc'] = 'Allow users to token enrol into new courses by default.';
$string['newenrols_help'] = 'This setting determines whether a user can enrol into this course.';
$string['noseatsavailable'] = 'Sorry, that token can no longer be used for enrolments.';
$string['notenrolable'] = 'Sorry, you can\'t enrol enrol in that course using a token.';
$string['pluginname'] = 'Token enrolment';
$string['pluginname_desc'] = 'The token enrolment plugin allows users to use a generated token to enrol in a course. Internally the enrolment is done via the manual enrolment plugin which has to be enabled in the same course.';
$string['role'] = 'Default assigned role';
$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = 'If enabled, users receive a welcome message via email when they token-enrol in a course.';
$string['status'] = 'Enable existing enrolments';
$string['status_desc'] = 'Enable token enrolment method in new courses.';
$string['status_help'] = 'If disabled all existing token enrolments are suspended and new users can not enrol.';
$string['tokeninput'] = 'Has your employer or job network agency issued you with an access token? Enter it below.';
$string['tokenexpired'] = 'Sorry, that token has expired and can no longer be used for enrolments.';
$string['toomanyattempts'] = 'Too many tokens have been entered in a short time. You must now wait some time before entering any other tokens.';
$string['token:config'] = 'Configure token enrol instances';
$string['token:manage'] = 'Manage enrolled users';
$string['token:unenrol'] = 'Unenrol users from course';
$string['token:unenrolself'] = 'Unenrol self from the course';
$string['tokendoesntexist'] = 'Sorry, that token is not valid for enrolment into this course.';
$string['unenrol'] = 'Unenrol user';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['unenroluser'] = 'Do you really want to unenrol "{$a->user}" from course "{$a->course}"?';
$string['userthrottlingperiod'] = 'User throttling period (mins)';
$string['userthrottlingperiod_desc'] = 'The period, in minutes, that a user account can be used to enter 10 tokens before they are disallowed from entering more';
$string['userthrottlingperiod_help'] = 'Use this value to stop possible token-guessing attempts. ' .
                                       'For instance, a value of 10 means that a user account can only be used to try a maximum of 10 tokens in a 10 minute period. ' .
                                       'Lower values are more restrictive ' .
                                       'A value of 0 turns user throttling off.';
$string['welcometocourse'] = 'Welcome to {$a}';
$string['welcometocoursetext'] = 'Welcome to {$a->coursename}!

If you have not done so already, you should edit your profile page:

  {$a->profileurl}';
$string['enrol_header'] = 'Enrol using a token';
$string['enrol_label'] = 'Token:';

$string['view_token_usage'] = 'View tokens & their usage';
$string['promptfiltertoken'] = 'Token search pattern';
$string['promptfiltertoken_help'] = 'You can use <b>&#42;</b> to search for any characters (e.g. <em>token&#42;</em> will match token1, token12, token_bob, etc), and <b>?</b> to represent a single unknown character (e.g. <em>to?en</em> will match token, tojen, to6en, etc). You can use both together (e.g. <em>to?en*</em>)';
$string['viewusers'] = 'View details';
$string['revoketokens'] = 'Delete selected token(s)';
$string['tokens_revoked'] = '{$a->count} token(s) were deleted';
$string['tokens_revoked_error'] = 'Something went wrong deleting the tokens';

$string['create_token'] = 'Create tokens';
$string['create_token_submit'] = 'Create';
$string['create_token_tokens'] = 'Tokens';

$string['create_cohort_header'] = 'Cohort selection';
$string['create_cohort_help'] = 'Users who enrol using a token will be added to a Cohort. Choose an existing cohort OR create a new cohort by specifying a name.';
$string['create_cohort_select'] = 'Existing Cohort';
$string['create_cohort_or'] = 'OR';
$string['create_cohort_new'] = 'New Cohort name';
$string['create_token_expiry'] = 'Token expiry date (if enabled).';

$string['create_token_header'] = 'Token parameters';
$string['create_token_help'] = 'Tokens look like random alphanumeric values and are up to 15 letters long.';
$string['create_token_prefix'] = 'Prefix';
$string['create_token_prefix_help'] = 'Generated tokens will begin with the text you enter here (default: blank, no spaces, up to 8 letters).';

$string['create_token_seats'] = 'Enrolments per token';
$string['create_token_seats_help'] = 'How many times each token can be used for an enrolment (between 1 and 1000)';
$string['create_token_count'] = 'Number of tokens to produce';
$string['create_token_count_help'] = 'Enter the number of tokens you want to generate using these details (between 1 and 1000)';

$string['create_token_email'] = 'Send tokens to this email';

$string['create_token_email_subject'] = 'Mail subject';
$string['create_token_email_subject_default'] = 'Your enrolment tokens for {$a->instancename}';

$string['create_token_email_body'] = 'Mail body';
$string['create_token_email_body_help'] = '<p>Body of email to be sent after tokens are generated. You can use the following merge fields:</p>
*{coursename}* - the full name of the course

*{tokennumber}* - the number of tokens that were generated

*{tokennumberplural}* - the letter "s" if there is more than one token

*{seatspertoken}* - the number of seats that were generated

*{seatspertokenplural}* - the letter "s" if there is more than one seat

*{wwwroot}* - the url of this server up until (but not including) the trailing slash (e.g. http://foo.com but not http://foo.com/ )

*{tokens}* - a list of the tokens (or a single token value, if there is only one)

*{adminsignoff}* - the site-wide standard email sign-off (configured elsewhere)

*{emailaddress}* - the email address you are sending to

HTML / Markdown is ok.
';
$string['create_token_email_body_default'] = "<p>Hello,</p>
<p>Please find below {tokennumber} token{tokennumberplural} that can be used to enrol into the {coursename} course on {wwwroot}. Each token can used {seatspertoken} time{seatspertokenplural}.</p>
<pre>{tokens}</pre>
<p>Regards,<br />
{adminsignoff}</p>";

$string['create_token_result_header'] = 'Here are your tokens';


$string['manage_token_header_token'] = 'Token';
$string['manage_token_header_cohort'] = 'Cohort';
$string['manage_token_header_seatsremaining'] = 'Seats remaining';
$string['manage_token_header_createdby'] = 'Created by';
$string['manage_token_header_datecreated'] = 'Date created';
$string['manage_token_header_dateexpires'] = 'Expiry date';
$string['manage_token_header_usedby'] = 'Used by';
$string['manage_token_header_dateused'] = 'Date used';
$string['manage_token_header_revoke'] = 'Delete';
$string['manage_token_aofb'] = '{$a->a} of {$a->b}';
