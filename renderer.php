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

    use \core\output\notification;

    defined('MOODLE_INTERNAL') || die();



    /**
     * Renderer for PanoptoLite block plugin.
     */
    class block_panoptolite_renderer extends plugin_renderer_base
    {

        /**
         * Custom rendering method for block_panoptolite, called
         * from plugin_renderer_base::render() method, emits markup
         * for use with the core/tree AMD module. Return value is
         * assigned to block->content property.
         *
         * @param  renderable $widget Data container (block_panoptolite_user_renderable) object
         * @return stdClass Block content object with text and footer properties
         */
        protected function render_block_panoptolite_user(block_panoptolite_user_renderable $widget)
        {

            $content = new stdClass();

            if (empty($widget->errormsg)) {

                $content->text = $this->render_content_text($widget->blockinstanceid, $this->render_content_text_for_user($widget), $widget->tree !== null);
                $content->footer = $this->render_content_footer($widget);

            } else {

                // Could not collect the data for some reason
                $content->text = $this->output->notification($widget->errormsg, notification::NOTIFY_ERROR);
                $content->footer = '';

            }

            return $content;

        }


        /**
         * Custom rendering method for block_panoptolite, called
         * from plugin_renderer_base::render() method, emits markup
         * for use with the core/tree AMD module. Return value is
         * assigned to block->content property.
         *
         * @param  renderable $widget Data container (block_panoptolite_course_renderable) object
         * @return stdClass Block content object with text and footer properties
         */
        protected function render_block_panoptolite_course(block_panoptolite_course_renderable $widget)
        {

            $content = new stdClass();

            if (empty($widget->errormsg)) {

                $content->text = $this->render_content_text($widget->blockinstanceid, $this->render_content_text_for_course($widget), $widget->recordings !== null);
                $content->footer = $this->render_content_footer($widget);

            } else {

                // Could not collect the data for some reason
                $content->text = $this->output->notification($widget->errormsg, notification::NOTIFY_ERROR);
                $content->footer = '';

            }

            return $content;

        }


        /**
         * Builds a aria tree wrapper for either widget's content
         *
         * @param int $blockinstanceid Block instance's id value
         * @param string $text Rendered content text from either widget type
         * @param bool $dataloaded Whether data was fetched and rendered
         * @return string Block content text ready for output
         */
        private function render_content_text($blockinstanceid, $text, $dataloaded)
        {

            return html_writer::tag(
                'ul', html_writer::tag(
                    'li', $text,
                    array('class' => 'type_custom depth_1 contains_branch current_branch')),
                array('id' => "bid_{$blockinstanceid}_list", 'class' => 'block_tree list', 'role' => 'tree', 'data-loaded' => $dataloaded ? 'true' : 'false'));

        }


        /**
         * Renders the footer content
         *
         * @param block_panoptolite_renderable $widget Data container
         * @return string
         */
        private function render_content_footer(block_panoptolite_renderable $widget)
        {

            // This fetches a copy of the page URL so we can
            // alter it.
            $url = $this->page->url;
            $url->param("bid-{$widget->blockinstanceid}-refresh", '1');

            // Need this for the minimal form element tacked on to the
            // end of the block content. Form is used to POST the api
            // instance name to Panopto (using URL of the folder list
            // or recording). In response, Panopto will bounce back to
            // the extauth.php page, a quasi-SSO validation page.
            $formid = "bid_{$widget->blockinstanceid}_form";

            $buf  = html_writer::start_div()
                  . html_writer::span(html_writer::link($url, get_string('refresh')))
                  . html_writer::end_div()
                  . html_writer::start_tag('form',
                        array('name' => $formid, 'id' => $formid, 'method' => 'POST', 'target' => '_blank'))
                  . html_writer::start_tag('input', array('type' => 'hidden', 'name' => 'instance', 'value' => $widget->pluginconfig->apiinstance))
                  . html_writer::end_tag('input')
                  . html_writer::end_tag('form');

            return $buf;

        }


        /**
         * Renders the widget for the user dashboard showing list of
         * folders to which the user has access.
         *
         * @param block_panoptolite_user_renderable $widget Data container for folder list
         * @return string
         */
        private function render_content_text_for_user(block_panoptolite_user_renderable $widget)
        {

            if ($widget->tree === null) {
                return;
            }

            // Fix up the list of folders. Keep count, if more than
            // reasonable, collapse the list initially
            $listid = "bid_{$widget->blockinstanceid}_folders";
            $list = html_writer::start_tag('ul', array('id' => $listid, 'role' => 'group'));

            $foldercount = 0;
            foreach($widget->tree as $folder) {
                $foldercount++;

                $folderlistid = "bid_{$widget->blockinstanceid}_subfolder_{$foldercount}";
                $folderlistname = s($folder->foldername);
                $folderspan  = html_writer::span($folderlistname, 'item-content-wrap');
                $folderlink  = html_writer::link($widget->get_folder_list_url($folder->folderid), $folderspan, array('title' => $folderlistname, 'target' => '_blank'));
                $folderpara  = html_writer::tag('p', $folderlink, array('class' => 'tree_item branch hasicon' . (empty($folder->recordings) ? ' emptybranch' : ''), 'role' => 'treeitem', 'aria-owns' => $folderlistid, 'aria-expanded' => 'false', 'data-collapsible' => 'true'));

                // List of recordings appended to the <p> element
                $folderpara .= html_writer::start_tag('ul', array('id' => $folderlistid, 'role' => 'group', 'aria-hidden' => 'true'));
                foreach($folder->recordings as $recording) {
                    // The nugget inside the link... the recording name
                    // and an image
                    $recordingname = s($recording->Name);
                    $recordingspan = $this->output->render(new pix_icon('/f/video', $recordingname))
                                   . html_writer::span($recordingname, 'item-content-wrap');

                    $recordinglink = html_writer::link($recording->ViewerUrl, $recordingspan, array('title' => $recordingname, 'target' => '_blank'));
                    $recordingpara = html_writer::tag('p', $recordinglink, array('class' => 'tree_item hasicon', 'role' => 'treeitem'));

                    $folderpara .= html_writer::tag('li', $recordingpara, array('class' => 'type_custom depth_2 item_with_icon'));

                }

                $folderpara .= html_writer::end_tag('ul');
                $list .= html_writer::tag('li', $folderpara, array('class' => 'type_custom depth_1 contains_branch item_with_icon'));

            }

            $list .= html_writer::end_tag('ul');

            return $list;
        }


        /**
         * Renders the widget for a course page showing list of
         * recordings from the folder associated with the course
         *
         * @param block_panoptolite_course_renderable $widget Data container for course folder
         * @return string
         */
        private function render_content_text_for_course(block_panoptolite_course_renderable $widget)
        {

            if ($widget->recordings === null) {
                return "";
            }

            // Fix up the list of recordings. Keep count, if more than
            // reasonable, collapse the list initially
            $listid = "bid_{$widget->blockinstanceid}_recordings";
            $list = html_writer::start_tag('ul', array('id' => $listid, 'role' => 'group'));

            foreach($widget->recordings as $recording) {

                // The nugget inside the link... the recording name
                // and an image
                $safename = s($recording->Name);
                $span  = $this->output->render(new pix_icon('/f/video', $safename))
                       . html_writer::span($safename, 'item-content-wrap');

                $link  = html_writer::link($recording->ViewerUrl, $span, array('title' => $safename, 'target' => '_blank'));
                $para  = html_writer::tag('p', $link, array('class' => 'tree_item hasicon', 'role' => 'treeitem'));

                $list .= html_writer::tag('li', $para, array('class' => 'type_custom depth_2 item_with_icon'));

            }

            $list .= html_writer::end_tag('ul');

            return html_writer::start_tag('p', array('class' => 'tree_item branch' . (empty($widget->recordings) ? ' emptybranch' : ''), 'role' => 'treeitem', 'aria-expanded' => 'true', 'aria-owns' => $listid, 'data-collapsible' => 'true'))
                 . html_writer::link($widget->get_folder_list_url($widget->folderid),
                                     $widget->foldername,
                                     array('target' => '_blank'))
                 . html_writer::end_tag('p')
                 . $list;

        }

    } // class
