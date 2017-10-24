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
    use \stdClass, \SoapClient, \SoapFault, \Exception;

    defined('MOODLE_INTERNAL') || die();

    require_once(__DIR__ . '/auth_soap_param.php');



    /**
     * Base (abstract) class of a Panopto SOAP client that provides the
     * PHP SoapClient instance, and the low-level call mechanism via a
     * protected call_soap_method() function.
     */
    abstract class base_soap_client
    {

        /**
         * @var integer Folder role enum
         */
        const FOLDER_ROLE_VIEWER    = 1;

        /**
         * @var integer Folder role enum
         */
        const FOLDER_ROLE_CREATOR   = 2;

        /**
         * @var integer Folder role enum
         */
        const FOLDER_ROLE_PUBLISHER = 3;



        /**
         * @var SoapClient $soapclient A PHP SoapClient.
         */
        private $soapclient;

        /**
         * @var array Array with single element (key 'auth') containing panopto_auth_param object.
         */
        private $soapauth;

        /**
         * @var string $apihost Service hostname.
         */
        private $apihost;

        /**
         * @var string $apiinstance Panopto identity provider instance name to use for external keys.
         */
        private $apiinstance;

        /**
         * @var string $apikey Panopto identity provider API key (shared secret).
         */
        private $apikey;

        /**
         * @var string $request Last request (XML) made by SOAP client.
         */
        private $request;

        /**
         * @var mixed(stdClass|SoapFault|Exception) Result of last SOAP call.
         */
        private $result;



        /**
         * Class constructor
         *
         * @param string $svcname Service endpoint name
         * @param string $svcvers Service endpoint version
         * @param string $apihost Service hostname
         * @param string $apiinstance Panopto identity provider instance name to use for external keys
         * @param string $apiuser Panopto username to authenticate for API call
         * @param string $apikey Panopto identity provider API key (shared secret)
         */
        protected function __construct($svcname, $svcvers, $apihost, $apiinstance, $apiuser, $apikey)
        {

            // Set up the auth param to use for authenticated calls
            $this->soapauth = new auth_soap_param($apihost, $apiinstance, $apiuser, $apikey);

            // Build the service wsdl url to use for the client
            $wsdl = "https://{$apihost}/Panopto/PublicAPI/{$svcvers}/{$svcname}?wsdl";

            $this->soapclient = new SoapClient($wsdl, array('trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE | SOAP_SINGLE_ELEMENT_ARRAYS));

            // Need later when forming alternate auth (credentials)
            // where user context for particular web service call is
            // taken from the auth obj, e.g. GetCreatorFoldersList
            $this->apihost = $apihost;
            $this->apiinstance = $apiinstance;
            $this->apikey = $apikey;

        }


        /**
         * Execute the SOAP call
         *
         * @param string $methodname Name of the method exposed on SOAP service
         * @param array $methodparams Parameters to pass to the called method
         * @param string $authusername Alternate username for authentication
         * @return mixed(null|stdClass|SoapFault|Exception)
         */
        protected function call_soap_method($methodname = '', array $methodparams = null, $authusername = '')
        {

            // No method to call, nothing to do
            if (empty($methodname)) {
                return null;
            }

            // Specify the result (name) we expect to get
            $methodresult = "{$methodname}Result";

            // If an auth username supplied for this call, make up a
            // single use param, otherwise use the auth param created
            // earlier at instantiation
            $authparam = array('auth' => empty($authusername)
                                       ? $this->soapauth
                                       : new auth_soap_param($this->apihost, $this->apiinstance,
                                                             $authusername, $this->apikey));

            // Combine the call specific params with the auth param,
            $soapparams = array_merge($authparam, $methodparams);

            try {

                $callresult = $this->soapclient->__soapCall($methodname, array($soapparams));
                if (empty($callresult) || count(get_object_vars($callresult)) == 0) {
                    // Some methods have void return
                    $this->result = null;
                } elseif (!empty($callresult->{$methodresult})) {
                    // Got the expected result by name which will be stdClass
                    // object instance. If it has no properties, e.g. an
                    // empty list from failed search, return null instead.
                    if (is_object($callresult->{$methodresult}) && count(get_object_vars($callresult->{$methodresult})) == 0) {
                        return null;
                    } else {
                        $this->result = $callresult->{$methodresult};
                    }
                } else {
                    // Got something
                    $this->result = $callresult;
                }

                return $this->result;

            }
            catch (SoapFault $fault) {
                $this->result = $fault;
                return null;
            }
            catch (Exception $exc) {
                $this->result = $exc;
                return null;
            }
            finally {
                $this->request = $this->soapclient->__getLastRequest();
            }

        }


        /**
         * Return result from last SOAP call
         *
         * @return mixed(stdClass|SoapFault|Exception)
         */
        public function result()
        {
            return $this->result;
        }


        /**
         * Was last result successful, i.e. not a fault or exception
         *
         * @return boolean
         */
        public function success()
        {

            return !(   $this->result instanceof SoapFault
                     || $this->result instanceof Exception);

        }


        /**
         * Return last request made (XML)
         *
         * @return string
         */
        public function request()
        {
            return $this->request;
        }


        /**
         * Replace the last result. Used by exception and fault
         * handlers to provide more descriptive information about
         * a problem.
         *
         * @param SoapFault $fault
         */
        protected function set_result(SoapFault $fault)
        {
            $this->result = $fault;
        }


        /**
         * Get the API instance name used for this client.
         *
         * @return string
         */
        protected function get_apiinstance()
        {
            return $this->apiinstance;
        }


        /**
         * Form a Panopto external key for the username
         *
         * @param string $username Moodle username
         * @return string
         */
        protected function instance_username($username)
        {
            return "{$this->apiinstance}\\{$username}";
        }


        /**
         * Form a Panopto external id for folder
         *
         * @param int $courseid Moodle course id
         * @return string
         */
        protected function instance_folder_external_id($courseid)
        {
            return "course-id:{$courseid}";
        }


        /**
         * Form a Panopto external id for access group
         *
         * @param int $courseid Moodle course id
         * @param int $role Panopto role (Viewer|Creator|Publisher)
         * @return string
         */
        protected function instance_group_external_id($courseid, $role)
        {

            switch($role) {

                case self::FOLDER_ROLE_CREATOR:
                    $rolename = "creators";
                    break;
                case self::FOLDER_ROLE_PUBLISHER:
                    $rolename = "publishers";
                    break;
                default: // self::FOLDER_ROLE_VIEWER:
                    $rolename = "viewers";

            }

            return $this->instance_folder_external_id($courseid) . "_{$rolename}";

        }


        /**
         * Form a Panopto external name for access group
         *
         * @param int $courseid Moodle course id
         * @param int $role Panopto role (Viewer|Creator|Publisher)
         * @return string
         */
        protected function instance_group_external_name($courseid, $role)
        {

            switch($role) {

                case self::FOLDER_ROLE_CREATOR:
                    $rolename = "Creators";
                    break;
                case self::FOLDER_ROLE_PUBLISHER:
                    $rolename = "Publishers";
                    break;
                default: // self::FOLDER_ROLE_VIEWER:
                    $rolename = "Viewers";

            }

            return "extgroup-course-id:{$courseid}::{$rolename}";

        }

    }
