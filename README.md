# enrol-token

This plugin lets you enrol into a course using a token code. A token is a short alphanumeric text value that is hard to guess and easy to distribute. It can be single use, or multiple use, and have an expiry date. It features IP throttling to prevent the same token being used too many times in a given period.

## Previous version

This plugin is a rewrite and simplification of my previous token enrolment plugin avaliable at https://github.com/frumbert/moodle-token-enrolment. The database and main logic remains compatible.

## Screenshots

Creating an instance
![Creating an instance](https://imgur.com/ZOeiggY.jpg)

Enrolment methods screen icons
![Enrolment methods](https://imgur.com/DHiCwbW.jpg)

Creating tokens
![Create Tokens](https://imgur.com/ARZvRfN.jpg)

View & manage tokens
![View & manage tokens](https://imgur.com/GrzB9md.jpg)

## Usage

1. Include the plugin onto your site using the plugin installer, or put it into `/enrol/token`
2. Set your instance defaults during installation (use the help icons for explanations of each feature)
3. Enable the enrolment method through Site Administration > Plugins > Enrolments
4. On a course, enable Token enrolment
5. Press the "+" button (Create Tokens) and create some token codes.
6. Have a learner access the course. Enter a token code. They are now enrolled.
7. Use the circle button (Manage tokens) to view tkens, usage or revoke individual tokens.

## Licence

GPL3, as per Moodle