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

    namespace panoptolite;
    use \stdClass;

    defined('MOODLE_INTERNAL') || die();

    require_once(__DIR__ . '/base_soap_client.php');



    /**
     * SOAP client wrapper tailored for Panopto's user management
     * web service endpoint
     */
    class users_soap_client extends base_soap_client
    {


        /**
         * @var string Endpoint service name
         */
        const svcname = 'UserManagement.svc';

        /**
         * @var string Endpoint version number
         */
        const svcvers = '4.6';



        /**
         * Constructor
         *
         * @param string $apihost Service hostname
         * @param string $apiinstance Panopto identity provider instance name to use for external keys
         * @param string $apiuser Panopto username to authenticate for API call
         * @param string $apikey Panopto identity provider API key (shared secret)
         */
        public function __construct($apihost, $apiinstance, $apiuser, $apikey)
        {
            parent::__construct(self::svcname, self::svcvers, $apihost, $apiinstance, $apiuser, $apikey);
        }


        /**
         * Create a Panopto user. If a user already exists for a given
         * UserKey, the existing user's Id will be returned.
         *
         * @param string $username Moodle username
         * @param string $email Moodle user's email address
         * @param string $firstname Firstname
         * @param string $lastname Lastname
         * @return mixed(null|string) Panopto user Id (GUID)
         */
        public function create_user($username, $email, $firstname, $lastname)
        {

            $params = array('user' => array(), 'initialPassword' => null);
            $params['user']['Email'] = $email;
            $params['user']['FirstName'] = $firstname;
            $params['user']['LastName'] = $lastname;
            $params['user']['UserKey'] = $this->instance_username($username);

            $params['user']['EmailSessionNotifications'] = 0;
            $params['user']['GroupMemberships'] = null;
            $params['user']['SystemRole'] = 'None';
            $params['user']['UserBio'] = null;
            $params['user']['UserId'] = null;
            $params['user']['UserSettingsUrl'] = null;

            $result = $this->call_soap_method('CreateUser', $params);
            if (!$this->success() || $result == null) {
                return null;
            }

            return $result;

        }


        /**
         * Create several Panopto users.
         *
         * @param array $users Array of stdClass objects each with a username, email, firstname, and  lastname property
         * @return mixed(null|array)
         */
        public function create_users(array $userarray)
        {

            if (empty($userarray)) {
                return null;
            }

            $params = array('users' => array());
            foreach ($userarray as $user) {

                $item = array();
                $item['Email'] = $user->email;
                $item['FirstName'] = $user->firstname;
                $item['LastName'] = $user->lastname;
                $item['UserKey'] = $this->instance_username($user->username);

                $item['EmailSessionNotifications'] = 0;
                $item['GroupMemberships'] = null;
                $item['SystemRole'] = 'None';
                $item['UserBio'] = null;
                $item['UserId'] = null;
                $item['UserSettingsUrl'] = null;

                $params['users'][] = $item;

            }

            $result = $this->call_soap_method('CreateUsers', $params);
            if (!$this->success() || $result == null || !is_array($result->User)) {
                return null;
            }

            return $result->User;

        }


        /**
         * Get a Panopto user for the specified Moodle username
         *
         * @param string $username
         * @return mixed(null|stdClass|SoapFault|Exception)
         */
        public function get_user($username)
        {

            $params   = array('userKey' => $this->instance_username($username));

            $result = $this->call_soap_method('GetUserByKey', $params);
            if (!$this->success() || $result == null) {
                return null;
            }

            return $result;

        }


        /**
         * Create (folder) access group.
         *
         * @param int $courseid Moodle course id
         * @param int $role One of the FOLDER_ROLE_XXXX constant values
         * @param array $externaluserids Initial group members ids
         * @return mixed(null|stdClass)
         */
        public function create_group_for_course($courseid, $role = self::FOLDER_ROLE_VIEWER, $externaluserids = null)
        {

            if (empty($courseid)) {
                return null;
            }

            $params = array(
                'groupName' => $this->instance_group_external_name($courseid, $role),
                'externalProvider' => $this->get_apiinstance(),
                'externalId' => $this->instance_group_external_id($courseid, $role),
                'memberIds' => $externaluserids);

            $result = $this->call_soap_method('CreateExternalGroup', $params);
            if (!$this->success() || $result == null) {
                return null;
            }

            return $result;

        }


        /**
         * Fetch group by id.
         *
         * @param string $groupid Panopto group id (GUID).
         * @return mixed(null|stdClass)
         */
        public function get_group($groupid)
        {

            if (empty($groupid)) {
                return null;
            }

            $params = array('groupId' => $groupid);

            $result = $this->call_soap_method('GetGroup', $params);
            if (!$this->success() || $result == null) {
                return null;
            }

            return $result;

        }


        /**
         * Get user in a group.
         *
         * @param string $groupid Panopto group id (GUID).
         * @return mixed(null|stdClass)
         */
        public function get_users_in_group($groupid)
        {

            if (empty($groupid)) {
                return null;
            }

            $params = array('groupId' => $groupid);

            $result = $this->call_soap_method('GetUsersInGroup', $params);
            if (!$this->success() || $result == null || !is_array($result->guid)) {
                return null;
            }

            return $result->guid;

        }


        /**
         * Delete (folder) access group.
         *
         * @param string $groupid Panopto group id (GUID).
         */
        public function delete_group($groupid)
        {

            if (empty($groupid)) {
                return;
            }

            $params = array('groupId' => $groupid);

            $this->call_soap_method('DeleteGroup', $params);

        }


        /**
         * Add users to (folder) access group.
         *
         * @param int $courseid Moodle course id
         * @param array $externaluserids Panopto user ids (GUID).
         * @param int $role One of the FOLDER_ROLE_XXXX constant values
         */
        public function add_users_to_group($courseid, $externaluserids, $role = self::FOLDER_ROLE_VIEWER)
        {

            $params = array('externalProviderName' => $this->get_apiinstance(),
                          'externalGroupId' => $this->instance_group_external_id($courseid, $role),
                          'memberIds' => $externaluserids);

            $this->call_soap_method('AddMembersToExternalGroup', $params);

        }


        /**
         * Remove user from (folder) access group.
         *
         * @param int $courseid Moodle course id
         * @param string $externaluserid Panopto user id (GUID).
         * @param int $role One of the FOLDER_ROLE_XXXX constant values
         */
        public function remove_users_from_group($courseid, array $externaluserids, $role = self::FOLDER_ROLE_VIEWER)
        {

            $params = array('externalProviderName' => $this->get_apiinstance(),
                'externalGroupId' => $this->instance_group_external_id($courseid, $role),
                'memberIds' => $externaluserids);

            $result = $this->call_soap_method('RemoveMembersFromExternalGroup', $params);

        }

    }
