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

    use \core\event\role_assigned,
        \core\event\role_unassigned,
        \panoptolite\folders_soap_client,
        \panoptolite\users_soap_client;

    defined('MOODLE_INTERNAL') || die();

    require_once(__DIR__ . '/classes/soap/folders_soap_client.php');
    require_once(__DIR__ . '/classes/soap/users_soap_client.php');
    require_once(__DIR__ . '/classes/renderable.php');



    class block_panoptolite_lib
    {


        /**
         * Get the singleton plugin configs.
         *
         * @return stdClass
         */
        public static function get_pluginconfig()
        {
            static $pluginconfig = null;


            if ($pluginconfig == null) {
                $pluginconfig = get_config('block_panoptolite');
                if (!$pluginconfig || !self::plugin_configured($pluginconfig)) {
                    return null;
                }
            }

            return $pluginconfig;

        }


        /**
         * Get the singleton soap client for Panopto folder (session) web service calls.
         *
         * @return folders_soap_client
         */
        private static function get_folderssoapclient()
        {
            static $folderssoapclient = null;


            $config = self::get_pluginconfig();
            if ($config == null) {
                return null;
            }

            if ($folderssoapclient == null) {
                $folderssoapclient = new folders_soap_client(
                    $config->apihost, $config->apiinstance,
                    $config->apiusername, $config->apikey);
            }

            return $folderssoapclient;

        }


        /**
         * Get the singleton soap client for Panopto user web service calls.
         *
         * @return users_soap_client
         */
        private static function get_userssoapclient()
        {
            static $userssoapclient = null;


            $config = self::get_pluginconfig();
            if ($config == null) {
                return null;
            }

            if ($userssoapclient == null) {
                $userssoapclient = new users_soap_client(
                    $config->apihost, $config->apiinstance,
                    $config->apiusername, $config->apikey);
            }

            return $userssoapclient;

        }


        /**
         * Get the singleton session cache.
         *
         * @return cache_session
         */
        private static function get_sessioncache()
        {
            static $sessioncache = null;


            if ($sessioncache == null) {
                $sessioncache = cache::make('block_panoptolite', 'cache');
            }

            return $sessioncache;

        }


        /**
         * Check if connection configs available.
         *
         * @param stdClass $config Plugin config returned from get_config().
         * @return bool
         */
        public static function plugin_configured($config = null)
        {

            if ($config == null) {
                $config = self::get_pluginconfig();
            }

            if (!$config || !is_object($config)
                || empty($config->apihost) || empty($config->apiinstance)
                || empty($config->apikey)  || empty($config->apiusername)) {
                return false;
            }

            return true;

        }


        /**
         * Prune the folder name down to a reasonable display size
         *
         * @param string $foldername
         * @return string
         */
        public static function prune_folder_name($foldername)
        {

            if (empty($foldername)) {
                return '';
            }

            $foldername = trim($foldername);

            $len = strlen($foldername);
            if ($len <= 25) {
                return $foldername;
            }

            if (($pos = strrpos(substr($foldername, 0, 25), ' ')) === false) {
                // No spaces, so truncate at 22
                return substr($foldername, 0, 22) . "...";
            } elseif ($pos > 15) {
                return substr($foldername, 0, $pos) . "...";
            } else {
                return substr($foldername, 0, 22) . "...";
            }


        }

        /**
         * Handles the role assigned or unassigned event, either case just sync the user
         *
         * @param mixed(role_assigned|role_unassigned) The role event
         */
        public static function handle_role_event($event)
        {
            global $DB;


            // Ready to do any work?
            if (!self::plugin_configured()) {
                return;
            }

            if ($event->contextlevel !== CONTEXT_COURSE) {
                return;
            }

            // See if block instance for the course. If not, get out
            $sql = "SELECT 1 FROM {block_instances}
                     WHERE blockname = :blockname
                       AND parentcontextid = :parentcontextid
                       AND configdata != ''";
            $params = array('blockname' => 'panoptolite', 'parentcontextid' => $event->contextid);
            $instance = $DB->get_record_sql($sql, $params);

            // If no configured instance of the block in the course
            // no need to do anything
            if (!$instance) {
                return;
            }

            $user = $DB->get_record('user', array('id' => $event->relateduserid));
            if (!$user) {
                return;
            }

            self::sync_user_enrollment($user, context::instance_by_id($event->contextid));

        }


        /**
         * Create a pair of Panopto access groups with external ids corresponding to a Moodle course id.
         *
         * @param int $courseid Moodle course id
         * @return array Array with two elements, the creators group id, and the viewers group id (both GUIDs)
         */
        public static function create_course_groups($courseid)
        {

            if (empty($courseid)) {
                return array(null, null);
            }

            $soapclient = self::get_userssoapclient();

            $creatorsgroup = $soapclient->create_group_for_course($courseid, $soapclient::FOLDER_ROLE_CREATOR);
            if ($creatorsgroup == null) {
                return array(null, null);
            }

            $viewersgroup = $soapclient->create_group_for_course($courseid, $soapclient::FOLDER_ROLE_VIEWER);
            if ($viewersgroup == null) {
                return array($creatorsgroup, null);
            }

            return array($creatorsgroup, $viewersgroup);

        }


        /**
         * Delete a Moodle course's corresponding pair of Panopto access groups.
         *
         * @param string $creatorsgroupid Panopto group id (GUID) for the creators group
         * @param string $viewersgroupid Panopto group id (GUID) for the viewers group
         */
        public static function delete_course_groups($creatorsgroupid, $viewersgroupid)
        {

            if (empty($creatorsgroupid) && empty($viewersgroupid)) {
                return;
            }

            $soapclient = self::get_userssoapclient();

            if (!empty($creatorsgroupid)) {
                $soapclient->delete_group($creatorsgroupid);
            }
            if (!empty($viewersgroupid)) {
                $soapclient->delete_group($viewersgroupid);
            }

        }


        /**
         * Create a Panopto folder with an external id corresponding to a Moodle course id.
         *
         * @param int $courseid Moodle course id
         * @param string $coursename Moodle course name
         * @return mixed(null|stdClass)
         */
        public static function create_course_folder($courseid, $coursename)
        {
            global $DB;


            $folderssoapclient = self::get_folderssoapclient();

            $folder = $folderssoapclient->create_folder($coursename, $courseid);
            if (!$folderssoapclient->success() || $folder == null) {
                return null;
            }

            return $folder;

        }


        /**
         * Get cached array of folder objects for the specified user.
         *
         * @param stdClass $user Moodle user object
         * @param bool $refresh Whether to clear the cache or not
         * @return array Array containing two elements, an array of folder objects, and an error message
         */
        private static function get_folder_objects_for_user($user = null, $refresh = false)
        {
            global $USER;


            if ($user == null) {
                $user = $USER;
            }

            $soapclient = self::get_folderssoapclient();
            $sessioncache = self::get_sessioncache();
            $config = self::get_pluginconfig();

            $errormsg = "";

            // If not directed to refresh, see if something in the
            // session cache we can use without hitting the wire.

            if (!$refresh) {
                $folders = $sessioncache->get("folders:{$user->username}");
                if (!empty($folders)) {
                    return array($folders, $errormsg);
                }
                // Nothing in the cache, but before we continue, check
                // if we should defer fetch due to AJAX preference.
                if (!AJAX_SCRIPT && $config->ajaxfirstload) {
                    return array(null, $errormsg);
                }
            }

            // Get to here then either need to refresh, or cache empty
            // and not deferring load...

            $rolemapfolders = array();
            if (!empty($config->creatorrolemap)) {
                // Find all the courses where the current user has a
                // role which is one mapped to Panopto creator role.
                $courseids = self::get_courses_for_user_by_roles($user->id, explode(',', $config->creatorrolemap));
                // Do a blind fetch to see if any of those courses
                // have a folder associated with them.
                $rolemapfolders = $soapclient->get_folders_by_courseids($courseids);
                if (!$soapclient->success()) {
                    $errormsg .= $soapclient->result()->getMessage();
                }
            }

            // Add to those, any folders to which current user has the
            // Panopto creator role, which may have been assigned on
            // Panopto side.
            $assignedfolders = $soapclient->get_folders_for_user($user->username);
            if (!$soapclient->success()) {
                $errormsg .= $soapclient->result()->getMessage();
            }

            // Instead of using array_merge, iterate through the two
            // sets of folders, keying by the folder id so we get a
            // list of distinct folders, no dups, and remove the user
            // private folder 'My Folder'
            $folders = array();
            if ($rolemapfolders != null) {
                foreach($rolemapfolders as $folder) {
                    $folders[$folder->Id] = $folder;
                }
            }
            if ($assignedfolders != null) {
                foreach($assignedfolders as $folder) {
                    if ($folder->Name == 'My Folder') continue;
                    $folders[$folder->Id] = $folder;
                }
            }

            if (empty($errormsg)) {
                $sessioncache->set("folders:{$user->username}", $folders);
            }

            return array($folders, $errormsg);

        }


        /**
         * Get a list of folder options (to use in a select form element) for the user.
         *
         * @param stdClass $user Moodle user object
         * @param bool $refresh Whether to clear the cache or not
         * @return array
         */
        public static function get_folder_options_for_user($user = null, $refresh = false)
        {
            global $USER;


            if ($user == null) {
                $user = $USER;
            }

            list($folders, $errormsg) = self::get_folder_objects_for_user($user, $refresh);
            if (!empty($errormsg)) {
                return array();
            }

            $options = array();
            foreach ($folders as $folder) {
                $options[$folder->Id] = $folder->Name;
            }

            return $options;

        }


        /**
         * Get cached array of recording objects for the specified folder.
         *
         * @param string $folderid Panopto folder id (GUID).
         * @param bool $refresh Whether to clear the cache or not.
         * @return array Array containing two elements, an array of recording objects, and an error message
         */
        public static function get_recordings_for_folder($folderid, $refresh)
        {

            if (!self::plugin_configured()) {
                return array(null, get_string('nositeconfig', 'block_panoptolite'));
            }

            if (empty($folderid)) {
                return array(null, get_string('noinstanceconfig', 'block_panoptolite'));
            }

            $config = self::get_pluginconfig();
            $sessioncache = self::get_sessioncache();

            // If not directed to refresh, see if something in the
            // session cache we can use without hitting the wire.
            if (!$refresh) {
                $recordings = $sessioncache->get("recordings:{$folderid}");
                if ($recordings !== false) {
                    return array($recordings, "");
                }
                // Nothing in the cache, but before we continue, check
                // if we should defer fetch due to AJAX preference.
                if (!AJAX_SCRIPT && $config->ajaxfirstload) {
                    return array(null, "");
                }
            }

            // Get to here then either need to refresh, or cache empty
            // and not deferring load...

            // Set up the soap client wrapper.
            $soapclient = self::get_folderssoapclient();

            $recordings = $soapclient->get_recordings_by_folderid($folderid);
            if (!$soapclient->success()) {
                return array(null, $soapclient->result()->getMessage());
            }

            $sessioncache->set("recordings:{$folderid}", $recordings);
            return array($recordings, "");

        }


        /**
         * Get cached array of recording objects for the specified user.
         *
         * @param string $user Moodle user object for whom to fetch the recordings.
         * @param bool $refresh Whether to clear the cache or not.
         * @return array Array containing two elements, an array of recording objects, and an error message
         */
        public static function get_recordings_for_user($user = null, $refresh)
        {
            global $USER;


            if (!self::plugin_configured()) {
                return array(null, get_string('nositeconfig', 'block_panoptolite'));
            }

            if ($user == null) {
                $user = $USER;
            }

            $config = self::get_pluginconfig();
            $sessioncache = self::get_sessioncache();

            // If not directed to refresh, see if something in the
            // session cache we can use without hitting the wire.
            if (!$refresh) {
                $tree = $sessioncache->get("recordings:{$user->username}");
                if ($tree !== false) {
                    return array($tree, "");
                }
                // Nothing in the cache, but before we continue, check
                // if we should defer fetch due to AJAX preference.
                if (!AJAX_SCRIPT && $config->ajaxfirstload) {
                    return array(null, "");
                }
            }

            // Get to here then either need to refresh, or cache empty
            // and not deferring load...

            // Set up the soap client wrapper.
            $soapclient = self::get_folderssoapclient();

            $recordings = $soapclient->get_recordings_by_username($user->username);
            if (!$soapclient->success()) {
                return array(null, $soapclient->result()->getMessage());
            }

            // Want a hierarchal foldername->recordings list suitable
            // for tree display
            $tree = array();
            foreach($recordings as $recording) {
                if (!array_key_exists($recording->FolderName, $tree)) {
                    $tree[$recording->FolderName] = new stdClass();
                    $tree[$recording->FolderName]->folderid = $recording->FolderId;
                    $tree[$recording->FolderName]->foldername = $recording->FolderName;
                    $tree[$recording->FolderName]->recordings = array();
                }
                $tree[$recording->FolderName]->recordings[] = $recording;
            }

            array_multisort($tree);
            foreach($tree as $branch) {
                array_multisort($branch->recordings);
            }

            $sessioncache->set("recordings:{$user->username}", $tree);
            return array($tree, "");

        }


        /**
         * Assigns the access groups (identifed by course id) to the specified folder
         *
         * @param int $courseid Moodle course id
         * @param string $folderid Panopto folder id (GUID)
         * @return boolean
         */
        public static function set_course_folder($courseid, $folderid)
        {

            $soapclient = self::get_folderssoapclient();

            $folder = $soapclient->set_folder_groups($folderid, $courseid);
            if (!$soapclient->success() || $folder == null) {
                return false;
            }

            return true;

        }


        /**
         * Synchronize (remove then add back) a single user into a course's corresponding Panopto access groups.
         *
         * @param stdClass $user
         * @param stdClass $context
         */
        private static function sync_user_enrollment(stdClass $user, stdClass $context)
        {

            // Clear the user from the access groups, and add back with
            // whatever roles are represented for user right now. This
            // should handle add, delete, second role add or delete, so
            // on.

            $config = self::get_pluginconfig();
            $soapclient = self::get_userssoapclient();

            $panoptouserid = $soapclient->create_user($user->username, $user->email, $user->firstname, $user->lastname);
            if (!$soapclient->success() || $panoptouserid == null) {
                error_log(get_string('wserrcreateusers', 'block_panoptolite'));
                return;
            }

            // Clear user from both access groups
            $soapclient->remove_users_from_group($context->instanceid, array($panoptouserid), $soapclient::FOLDER_ROLE_CREATOR);
            $soapclient->remove_users_from_group($context->instanceid, array($panoptouserid), $soapclient::FOLDER_ROLE_VIEWER);

            // Find out what roles they have now
            $roleassignments = get_user_roles($context, $user->id, false);
            $roleids = array();
            foreach($roleassignments as $roleassignment) {
                $roleids[] = $roleassignment->roleid;
            }

            // If user un-enrolled, they will not have any roles
            if (!$roleids) {
                return;
            }

            // Creators access group, are any of the roleids resulting
            // from the enrol event present in the mapping for creator
            // roles?
            $creatorroleids = explode(',', $config->creatorrolemap);
            if ($creatorroleids && array_intersect($roleids, $creatorroleids)) {
                // Add that user's Panopto user id to creator access
                // group
                $soapclient->add_users_to_group($context->instanceid, array($panoptouserid), $soapclient::FOLDER_ROLE_CREATOR);
                if (!$soapclient->success()) {
                    error_log(get_string('wserradduseracl', 'block_panoptolite'));
                    return;
                }
            }

            // Viewers access group... get all the assignable roles,
            // remove any roles handle by creator mapping, what remains
            // are viewer roles.
            $viewerroleids = array_keys(get_assignable_roles($context));
            $viewerroleids = array_diff($viewerroleids, $creatorroleids);
            if ($viewerroleids && array_intersect($roleids, $viewerroleids)) {
                $soapclient->add_users_to_group($context->instanceid, array($panoptouserid), $soapclient::FOLDER_ROLE_VIEWER);
                if (!$soapclient->success()) {
                    error_log(get_string('wserradduseracl', 'block_panoptolite'));
                    return;
                }
            }

        }


        /**
         * Synchronize the course enrollments to its corresponding Panopto access groups.
         *
         * @param int $courseid Moodle course id
         * @param string $creatorsgroupid Panopto group id (GUID) for the course's creators access group.
         * @param unknown $viewersgroupid Panopto group id (GUID) for the course's viewers access group.
         * @return boolean
         */
        public static function sync_course_enrollments($courseid, $creatorsgroupid = null, $viewersgroupid = null)
        {

            $config = self::get_pluginconfig();
            $soapclient = self::get_userssoapclient();
            $context = context_course::instance($courseid);

            // Clear 'em out

            if (!empty($creatorsgroupid)) {
                $externaluserids = $soapclient->get_users_in_group($creatorsgroupid);
                if ($externaluserids != null) {
                    $soapclient->remove_users_from_group($courseid, $externaluserids);
                }
            }
            if (!empty($viewersgroupid)) {
                $externaluserids = $soapclient->get_users_in_group($viewersgroupid);
                if ($externaluserids != null) {
                    $soapclient->remove_users_from_group($courseid, $externaluserids);
                }
            }

            // Put currently enrolled users back in

            // Creators access group
            $creatorroleids = explode(',', $config->creatorrolemap);
            if (!empty($creatorroleids)) {

                // Get list of users having a role in the course that
                // is mapped to the Panopto creator role, and who are
                // enrolled in the course
                $users = get_role_users($creatorroleids, $context, false, 'ra.id, u.id, u.username, u.email, u.firstname, u.lastname', 'u.username', false);

                // Make the list distinct since user may be included
                // more than once due to multiple roles.
                $creatorusers = array();
                foreach($users as $user) {
                    $creatorusers[$user->username] = $user;
                }

                if ($creatorusers) {
                    // Do not know if any of those users exist on Panopto,
                    // and need their GUIDs, so attempt to add them.
                    $panoptousers = $soapclient->create_users($creatorusers);
                    if (!$soapclient->success() || $panoptousers == null) {
                        return false;
                    }
                    // Now add those user's to creator access group
                    $panoptouserids = array();
                    foreach($panoptousers as $user) {
                        $panoptouserids[] = $user->UserId;
                    }
                    $soapclient->add_users_to_group($courseid, $panoptouserids, $soapclient::FOLDER_ROLE_CREATOR);
                    if (!$soapclient->success()) {
                        return false;
                    }
                }

            }

            // Viewers access group
            $viewerroleids = array_keys(get_assignable_roles($context));
            $viewerroleids = array_diff($viewerroleids, $creatorroleids);
            $users = get_role_users($viewerroleids, $context, false, 'ra.id, u.id, u.username, u.email, u.firstname, u.lastname', 'u.username', false);

            $viewerusers = array();
            foreach($users as $user) {
                if (array_key_exists($user->username, $creatorusers)) continue;
                $viewerusers[$user->username] = $user;
            }

            if ($viewerusers) {
                $panoptousers = $soapclient->create_users($viewerusers);
                if (!$soapclient->success() || $panoptousers == null) {
                    return false;
                }
                $panoptouserids = array();
                foreach($panoptousers as $user) {
                    $panoptouserids[] = $user->UserId;
                }
                $soapclient->add_users_to_group($courseid, $panoptouserids, $soapclient::FOLDER_ROLE_VIEWER);
                if (!$soapclient->success()) {
                    return false;
                }
            }

            return true;

        }


        /**
         * Get courses for a user where they have a role matching any of the supplied role ids.
         *
         * @param int $userid Moodle user id
         * @param array $roleids List of role ids to match
         * @return array Array of course id values
         */
        private static function get_courses_for_user_by_roles($userid, array $roleids)
        {
            global $DB;


            $params = array();
            list($inclause, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
            $sql = "SELECT DISTINCT cx.instanceid
                      FROM {role_assignments} ra
                INNER JOIN {context} cx ON ra.contextid = cx.id AND cx.contextlevel = :contextlevel
                     WHERE ra.roleid $inclause
                       AND ra.userid = :userid";

            $params['userid'] = $userid;
            $params['contextlevel'] = CONTEXT_COURSE;

            return $DB->get_fieldset_sql($sql, $params);

        }

    }
