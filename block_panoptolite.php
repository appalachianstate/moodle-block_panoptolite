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

    require_once(__DIR__ . '/lib.php');



    /**
     * Block plugin
     */
    class block_panoptolite extends block_base
    {


        /**
         * {@inheritDoc}
         * @see block_base::applicable_formats()
         */
        public function applicable_formats()
        {
            return array('course-view' => true, 'my' => true);
        }


        /**
         * {@inheritDoc}
         * @see block_base::get_aria_role()
         */
        public function get_aria_role()
        {
            return 'complementary';
        }


        /**
         * {@inheritDoc}
         * @see block_base::has_config()
         */
        public function has_config()
        {
            return true;
        }


        /**
         * {@inheritDoc}
         * @see block_base::instance_allow_config()
         */
        public function instance_allow_config()
        {
            return true;
        }


        /**
         * Called by the parent class constructor
         */
        public function init()
        {
            $this->content_type = BLOCK_TYPE_TREE;
            $this->title = get_string('pluginname', 'block_panoptolite');
        }


        /**
         * {@inheritDoc}
         * @see block_base::get_required_javascript()
         */
        public function get_required_javascript()
        {

            // Want the Tree module set up using a page requirement,
            // but the ajax loader will be load by using a data- tag
            // attribute (data-ajax-loader) emitted in the renderer

            parent::get_required_javascript();
            $this->page->requires->js_call_amd('block_panoptolite/module', 'init', array('blockinstanceid' => $this->instance->id));

        }


        /**
         * {@inheritDoc}
         * @see block_base::get_content()
         */
        public function get_content()
        {

            // This method called initially only to see if content
            // exists, then a second time to actually emit it.
            if ($this->content !== null) {
                return $this->content;
            }

            // Check the query string for the refresh param, if there
            // set indicator and remove the param
            $refresh = false;
            $pageurl = $this->page->url;
            if (array_key_exists("bid-{$this->instance->id}-refresh", $pageurl->params())) {
                $refresh = true;
                $pageurl->remove_params("bid-{$this->instance->id}-refresh");
                $this->page->set_url($pageurl);
            }

            // Renderer will create markup for both the content's text
            // and footer properties
            $this->content = $this->page->get_renderer('block_panoptolite')
                ->render(block_panoptolite_renderable::factory($this, $refresh));

            return $this->content;

        }


        /**
         * {@inheritDoc}
         * @see block_base::instance_delete()
         */
        function instance_delete()
        {

            if ($this->config == null) {
                // Never been configured
                return;
            }

            $parentcontext = $this->context->get_parent_context();
            if ($parentcontext->contextlevel == CONTEXT_USER) {
                // Not in a course, nothing to do
                return;
            }

            $creatorsgroupid = empty($this->config->creatorsgroupid) ? '' : $this->config->creatorsgroupid;
            $viewersgroupid = empty($this->config->viewersgroupid) ? '' : $this->config->viewersgroupid;

            block_panoptolite_lib::delete_course_groups($creatorsgroupid, $viewersgroupid);

        }


        /**
         * {@inheritDoc}
         * @see block_base::instance_config_save()
         */
        public function instance_config_save($data, $nolongerused = false)
        {
            global $COURSE, $OUTPUT, $PAGE;


            $errormsg = '';

            // Not in a course, then no custom data to save
            if ($this->context->get_parent_context()->contextlevel == CONTEXT_USER) {
                parent::instance_config_save($data);
                return;
            }

            // In case first save for this block instance
            if ($this->config == null) {
                $this->config = new stdClass();
            }

            // Is already a bespoke folder id, if so use that one.
            if (!empty($this->config->bespokefolderid) && ($data->folderid == "0" || empty($data->folderid))) {
                $data->folderid = $this->config->bespokefolderid;
            }

            // Whether this is first config of a new block instance
            // or re-adding a block instance after having deleted it,
            // always want to have the access groups associated with
            // this course pre-created and known, so we can control
            // their naming, and capture their ids.
            if (empty($config->folderid) && empty($this->config->creatorsgroupid) || empty($this->config->viewersgroupid)) {
                list($creatorsgroup, $viewersgroup) = block_panoptolite_lib::create_course_groups($COURSE->id);
                if ($creatorsgroup == null) {
                    $errormsg = get_string('wserrcreatorsgroup', 'block_panoptolite');
                    return;
                }
                if ($viewersgroup == null) {
                    $errormsg = get_string('wserrviewersgroup', 'block_panoptolite');
                    return;
                }
                $data->creatorsgroupid =
                $this->config->creatorsgroupid = $creatorsgroup->Id;
                $data->viewersgroupid =
                $this->config->viewersgroupid = $viewersgroup->Id;
            }

            // Dummy loop so we can use break statement as jump to end
            do {


                // No change to folderid, sync enrollments if indicated
                // then get out unless there is an error.
                if (!empty($this->config->folderid) && $this->config->folderid == $data->folderid) {
                    if ($data->synconsave) {
                        if (!block_panoptolite_lib::sync_course_enrollments($COURSE->id, $this->config->creatorsgroupid, $this->config->viewersgroupid)) {
                            $errormsg = get_string('wserrsyncenrollments', 'block_panoptolite');
                            break;
                        }
                    }
                    // No change to folder and sync successful
                    return;
                }


                // New folder
                if ($data->folderid == '0') {

                    // Create a folder for this course
                    $folder = block_panoptolite_lib::create_course_folder($COURSE->id, $COURSE->shortname);
                    if ($folder == null) {
                        $errormsg = get_string('wserrcreatefolder', 'block_panoptolite');
                        break;
                    }
                    $data->folderid             =
                    $data->bespokefolderid      = $folder->Id;
                    $data->foldername           =
                    $data->bespokefoldername    = $folder->Name;
                    // Sync users in course to folder access group
                    if (!block_panoptolite_lib::sync_course_enrollments($COURSE->id, $this->config->creatorsgroupid, $this->config->viewersgroupid)) {
                        $errormsg = get_string('wserrsyncenrollments', 'block_panoptolite');
                    }

                    break;

                }

                // Selected an existing folder. Get the list of valid
                // folders against which to validate. Validation list
                // should have at least one item for current course.
                $folders = block_panoptolite_lib::get_folder_options_for_user();

                // Validate the folder id POSTED against list
                if (!array_key_exists($data->folderid, $folders)) {
                    $errormsg = get_string('invalidfolderid', 'block_panoptolite');
                    break;
                }

                $data->foldername = $folders[$data->folderid];

                // Set the course folder by assigning the course's access
                // groups to the folder.
                if (!block_panoptolite_lib::set_course_folder($COURSE->id, $data->folderid)) {
                    $errormsg = get_string('wserrsetcoursefolder', 'block_panoptolite');
                    break;
                }

                // Sync users in course to folder access group
                if ($data->synconsave && !block_panoptolite_lib::sync_course_enrollments($COURSE->id, $this->config->creatorsgroupid, $this->config->viewersgroupid)) {
                    $errormsg = get_string('wserrsyncenrollments', 'block_panoptolite');
                }

            } while(false);


            if (empty($errormsg)) {

                $data->foldername = block_panoptolite_lib::prune_folder_name($data->foldername);

                unset($data->synconsave);
                parent::instance_config_save($data);

                return true;

            }

            // MDL-60149
            return get_string('saveinstanceerror', 'block_panoptolite') .  '<br /><br />' . $errormsg;

            // Otherwise
            // If we get here there is a problem with the data, notify
            // the user, and continue to the course view page.
            /*
            $heading = get_string('blockconfiga', 'moodle', $this->get_title());
            $PAGE->set_title($heading);
            $PAGE->set_heading($heading);
            $PAGE->navbar->add($this->get_title());
            $PAGE->navbar->add(get_string('configuration'));

            echo $OUTPUT->header();
            echo $OUTPUT->heading($heading, 2);

            echo $OUTPUT->spacer(null, true);
            echo $OUTPUT->notification(
                get_string('saveinstanceerror', 'block_panoptolite')
                . '<br /><br />'
                . $errormsg,
                \core\output\notification::NOTIFY_ERROR);
            echo $OUTPUT->spacer(null, true);

            $url = $PAGE->url;
            $url->remove_params('bui_editid');

            echo $OUTPUT->continue_button($url);
            echo $OUTPUT->footer();

            // return back to block_manager::process_url_edit() where
            // block position params will be saved.
            */

        }

    }
