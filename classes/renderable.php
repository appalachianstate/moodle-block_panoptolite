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



    /**
     * Data container class for PanoptoLite block used with a custom
     * plugin renderer class
     */
    abstract class block_panoptolite_renderable implements renderable
    {


        /**
         * @var string Error message occurring from data collection.
         */
        public $errormsg = '';

        /**
         * @var integer Block instance id value.
         */
        public $blockinstanceid = 0;

        /**
         * @var stdClass This block instance's config.
         */
        public $pluginconfig = null;



        /**
         * Constructor
         *
         * @param int $blockinstanceid Block instance id
         */
        protected function __construct(block_panoptolite $blockinstance)
        {

            $this->blockinstanceid = $blockinstance->instance->id;

            // Need the global (site) config
            if (!block_panoptolite_lib::plugin_configured()) {
                $this->errormsg = get_string('nositeconfig', 'block_panoptolite');
                return;
            }

            $this->pluginconfig = block_panoptolite_lib::get_pluginconfig();

        }


        /**
         * Return a folder list URL for the given folder id
         *
         * @param string $folderid Panopto folder id (GUID)
         * @return string
         */
        public function get_folder_list_url($folderid)
        {
            return "https://{$this->pluginconfig->apihost}/Panopto/Pages/Sessions/List.aspx#folderID={$folderid}";
        }


        /**
         * Called from the block get_content method to collect data
         * (webservice, DB) needed before being passed to the renderer
         * where the markup (block content) is built.
         *
         * @param block_panoptolite $blockinstance Block instance
         * @param bool $refresh Whether to clear the cache or not
         */
        static public function factory(block_panoptolite $blockinstance, $refresh)
        {

            // From the parent context determine which type of widget
            // to return to the caller.
            $parentcontext = $blockinstance->context->get_parent_context();

            if ($parentcontext->contextlevel == CONTEXT_USER) {
                $widget = new block_panoptolite_user_renderable($blockinstance, $refresh);
            } else {
                $widget = new block_panoptolite_course_renderable($blockinstance, $refresh);
            }

            return $widget;

        }

    }


    /**
     * Custom data container widget for list of Panopto folders to
     * which the user has access as creator.
     */
    class block_panoptolite_user_renderable extends block_panoptolite_renderable
    {


        /**
         * @var array of stdClass objects, each containing Panopto
         * folder information and a list (array) recording objects
         */
        public $tree = null;


        /**
         * Constructor
         *
         * @param int $blockinstanceid Block instance id
         * @param bool $refresh Whether to clear the cache or not
         */
        public function __construct(block_panoptolite $blockinstance, $refresh = false)
        {

            parent::__construct($blockinstance);
            if ($this->errormsg) {
                return;
            }

            // On a user's /my page. Show their folders.
            list($this->tree, $this->errormsg)
                = block_panoptolite_lib::get_recordings_for_user(null, $refresh);

        }

    }


    /**
     * Custom data container widget for Panopto folder with collection
     * (array) of recordings.
     */
    class block_panoptolite_course_renderable extends block_panoptolite_renderable
    {


        /**
         * @var string Panopto folder id (GUID)
         */
        public $folderid = '';

        /**
         * @var string Panopto folder name
         */
        public $foldername = '';

        /**
         * @var array Array of recordings (Panopto session objects)
         */
        public $recordings = null;



        /**
         * Constructor
         *
         * @param int $blockinstanceid Block instance id
         * @param string $blockinstance Moodle block instance object
         * @param bool $refresh Whether to clear the cache or not
         */
        public function __construct(block_panoptolite $blockinstance, $refresh)
        {

            parent::__construct($blockinstance);
            if ($this->errormsg) {
                return;
            }

            // Is the plugin global config present
            if (!block_panoptolite_lib::plugin_configured()) {
                $this->errormsg = get_string('nositeconfig', 'block_panoptolite');
                return;
            }

            // Is the instance config present
            if (empty($blockinstance->config->folderid)) {
                $errormsg = 'noinstanceconfig';
                if (has_capability('block/panoptolite:addinstance', $blockinstance->context)) {
                    $errormsg .= 'withcap';
                }
                $this->errormsg = get_string($errormsg, 'block_panoptolite');
                return;
            }

            $this->folderid = $blockinstance->config->folderid;
            $this->foldername = empty($blockinstance->config->foldername)
                              ? get_string('recordings', 'block_panoptolite')
                              : $blockinstance->config->foldername;

            list($this->recordings, $this->errormsg)
                = block_panoptolite_lib::get_recordings_for_folder($this->folderid, $refresh);

        }

    }
