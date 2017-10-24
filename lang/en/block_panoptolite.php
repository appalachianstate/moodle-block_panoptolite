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
     * block_panoptolite
     *
     * @author      Fred Woolard <woolardfa@appstate.edu>
     * @copyright   (c) 2017 Appalachian State Universtiy, Boone, NC
     * @license     GNU General Public License version 3
     * @package     block_panoptolite
     */

    defined('MOODLE_INTERNAL') || die();



    $string['pluginname']                   = 'PanoptoLite';

    $string['panoptolite:addinstance']      = 'Add new PanoptoLite block';
    $string['panoptolite:myaddinstance']    = 'Add new PanoptoLite block to my page';
    $string['cachedef_cache']               = 'PanoptoLite session cache';

    $string['connection_lbl']               = 'Web service connection';
    $string['connection_desc']              = 'Values used to connect to the Panopto API web service.';
    $string['apihost_lbl']                  = 'Hostname';
    $string['apihost_desc']                 = 'Hostname where your Panopto services are provided.';
    $string['apiinstance_lbl']              = 'Instance name';
    $string['apiinstance_desc']             = 'Identifies the identity provider to use for connections.';
    $string['apikey_lbl']                   = 'API key';
    $string['apikey_desc']                  = 'The shared secret key value from the identity provider.';
    $string['apiusername_lbl']              = 'Username';
    $string['apiusername_desc']             = 'The Panopto (external) user account to use for web services connections. This account requires Administrator system role in Panopto in order for this plugin to work correctly.';
    $string['roles_lbl']                    = 'Role mapping';
    $string['roles_desc']                   = 'How Moodle course roles map to Panopto folder roles';
    $string['creatorrolemap_lbl']           = 'Folder creators';
    $string['creatorrolemap_desc']          = 'Creators in Panopto can submit (upload) content to folders.';
    $string['ajax_lbl']                     = 'AJAX call';
    $string['ajax_desc']                    = 'When to use asynchronous page loading.';
    $string['ajaxfirstload_lbl']            = 'Initial load using AJAX';
    $string['ajaxfirstload_desc']           = 'Make initial load of block contents using AJAX call. If the first time a particular course page is loaded seems to take too long due to lengthy webservice calls (to Panopto), turn this on.';

    $string['folderid']                     = 'Panopto folder';
    $string['folderid_help']                = 'Create a new Panopto folder with which this course will be associated, or select one of the existing folders in which you have a Creator role.';
    $string['synconsave']                   = 'Synchronize users';
    $string['synconsave_help']              = 'Synchronize users in this course with Panopto folder. Users are synchronized upon enrollment and un-enrollment from the course. Usually need to do this only when an enrolled user can not access a recording.';

    $string['newfolder']                    = 'New folder';
    $string['bespokefolder']                = 'Original folder';
    $string['recordings']                   = 'Recordings';
    $string['nositeconfig']                 = 'PanoptoLite global (site) configuration incomplete. Contact your system admin. for assistance.';
    $string['noinstanceconfig']             = 'PanoptoLite block has not been configured. Contact your instructor for assistance.';
    $string['noinstanceconfigwithcap']      = 'PanoptoLite block has not been configured. Enable course editing, then use this block\'s gear menu (top-right corner) to select a Panopto folder to display.';
    $string['saveinstanceerror']            = 'Failed to configure this instance of the PanoptoLite block';

    $string['invalidauthcode']              = 'Authentication code is invalid.';
    $string['invalidfolderid']              = 'Folder id submitted is invalid.';

    $string['wserrsyncenrollments']         = 'Web service call to synchronize enrollments resulted in an error.';
    $string['wserrcreatefolder']            = 'Web service call to create folder resulted in an error.';
    $string['wserrsetcoursefolder']         = 'Web service call to set course folder resulted in an error'.
    $string['wserrcreatorsgroup']           = 'Web service call to create access group (creators) resulted in an error';
    $string['wserrviewersgroup']            = 'Web service call to create access group (viewers) resulted in an error';
