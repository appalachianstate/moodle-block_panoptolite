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
     * Container glass for the Panopto list request param
     */
    class list_folders_soap_param
    {

        /**
         * @var array Pagination params (MaxNumberResults, PageNumber)
         */
        private $Pagination;

        /**
         * @var string Filter results to those with specified parent folder
         */
        private $ParentFolderId;

        /**
         * @var bool Filter out private folders
         */
        private $PublicOnly = false;

        /**
         * @var string "Name", "Sessions", "Relevance"
         */
        private $SortBy = "Name";

        /**
         * @var bool Up or down
         */
        private $SortIncreasing = true;

        /**
         * @var bool
         */
        private $WildcardSearchNameOnly = true;



        /**
         * Class constructor
         *
         * @param int $pagenum Which page number to return, 0 based
         * @param int $pagesize How many entries per page
         * @param string $parentfolderid Parent folder id to filter by
         */
        public function __construct($parentfolderid = null, $pagenum = 0, $pagesize = 100)
        {

            $this->ParentFolderId = $parentfolderid;
            $this->Pagination = array('MaxNumberResults' => $pagesize, 'PageNumber' => $pagenum);

        }

    }
