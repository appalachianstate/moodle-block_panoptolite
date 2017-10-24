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

    defined('MOODLE_INTERNAL') || die();



    /**
     * Container glass for the Panopto web service authentication param
     */
    class auth_soap_param
    {

        /**
         * @var string Computed hash value with which to authenticate
         */
        private $AuthCode;

        /**
         * @var string Panopto username with which to authenticate
         */
        private $UserKey;

        /**
         * @var string Empty string placeholder for the password param
         */
        private $Password = '';


        /**
         * Class constructor
         *
         * @param string $apihost Service hostname
         * @param string $apiinstance Panopto identity provider instance name to use for external keys
         * @param string $apiuser Panopto username with which to authenticate
         * @param string $apikey Panopto identity provider API key (shared secret)
         */
        public function __construct($apihost, $apiinstance, $apiuser, $apikey)
        {

            $this->UserKey  = "{$apiinstance}\\{$apiuser}";
            $this->AuthCode = strtoupper(sha1("{$this->UserKey}@{$apihost}|{$apikey}"));

        }

    }
