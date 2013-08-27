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
 * Strings for component 'qtype_YOURQTYPENAME', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage remoteprocessed
 * @copyright  2013 Leif Johnson (leif.t.johnson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['answerformula']       = 'Answer {$a} formula';
$string['answerdisplay']       = 'Answer display';
$string['answerno']            = 'Answer {$a}';
$string['answerhdr']           = 'Answer';
$string['answertolerance']     = 'Tolerance';
$string['answerwithtolerance'] = '{$a->answer} (±{$a->tolerance} {$a->tolerancetype})';

$string['imagecode']    = 'Image Code';
$string['variablecode'] = 'Variables';
$string['server']       = 'Server';
$string['remotegrade']  = 'Remote Grade';
$string['remotegrade_help'] = 'If checked, grading is done on the remote server.  If un-checked, grading is done on the Moodle server.  For anything other than simple numerical grading, submitted answer within the interval (answer ± tolerance), remote grading is necessary.';

$string['pluginname'] = 'Remote Processed';
$string['pluginname_help'] = 'Create a question type with components processed by remote servers.';
$string['pluginname_link'] = 'question/type/remoteprocessed';
$string['pluginnameadding'] = 'Adding a remoteprocessed question';
$string['pluginnameediting'] = 'Editing a remoteprocessed question';
$string['pluginnamesummary'] = 'A remoteprocessed question type which allows the definition of questions where some components of the question are processed with a remote server.  This allows linking in functionality that may not be appropriate for integrating directly into Moodle.';

// Strings used for the server management and editing pages.
$string['editserver_addserver'] = 'Add a new server';
$string['editserver_configured_servers'] = 'Configured Servers';
$string['editserver_configured_servers_help'] = 'Add, test, edit and delete servers.';
$string['editserver_deleteconf'] = 'Are you sure you wish to delete the server, {$a}?';
$string['editserver_navbar'] = 'RP Question Server';
$string['editserver_servercantbedeleted'] = 'Server can\'t be deleted.  There are questions using this server.';
$string['editserver_servername'] = 'Server Name';
$string['editserver_servername_missing'] = 'This is the name used to refer to this server when writing questions.';
$string['editserver_servername_unused'] = '{$a->name} (Unused)';
$string['editserver_servername_usagecount'] = '{$a->name} (Used by {$a->count} questions)';
$string['editserver_serverurl'] = 'Remote Server URL';
$string['editserver_serverurl_missing'] = 'The url of the server.';
$string['editserver_testserver'] = 'Get Server Status';
$string['editserver_title']  = 'Edit Remote Processed Questions Server';
