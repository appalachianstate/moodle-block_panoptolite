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

    /*
     * Folder class object properties:
     *
     * [AllowPublicNotes]       => null or 1
     * [AllowSessionDownload]   => null or 1
     * [AudioPodcastITunesUrl]  => url
     * [AudioRssUrl]            => url
     * [ChildFolders]           => stdClass with 'guid' property, either single guid or array of guid
     * [Description]            => string
     * [EmbedUploaderUrl]       => url
     * [EmbedUrl]               => url
     * [EnablePodcast]          => null or 1
     * [Id]                     => guid
     * [IsPublic]               => null or 1
     * [ListUrl]                => url
     * [Name]                   => string
     * [ParentFolder]           => guid
     * [Presenters]             => stdClass with 'string' property, either single string or array of string
     * [Sessions]               => stdClass with 'guid' property, either single guid or array of guid
     * [SettingsUrl]            => url
     * [VideoPodcastITunesUrl]  => url
     * [VideoRssUrl]            => url
     * [ExternalId]             => string
     *
     *
     * Session class object properties:
     *
     * [CreatorId]          => guid
     * [Description]        => string
     * [Duration]           => 198.097
     * [EditorUrl]          => https://{hostname}/Panopto/Pages/Viewer.aspx?id=aaa6179d-1312-4852-a478-d22cea7b6955&edit=true
     * [ExternalId]         => string
     * [FolderId]           => guid
     * [FolderName]         => string
     * [Id]                 => guid
     * [IosVideoUrl]        => url
     * [IsBroadcast]        =>
     * [IsDownloadable]     =>
     * [MP3Url]             => url
     * [MP4Url]             => url
     * [Name]               => string
     * [NotesURL]           => url
     * [OutputsPageUrl]     => url
     * [RemoteRecorderIds]  => stdClass with guid property (single guid or array of guids)
     * [SharePageUrl]       => url
     * [StartTime]          => 2017-03-15T18:07:13.806Z
     * [State]              => 'Scheduled'|'Recording'|'Broadcasting'|'Processing'|'Complete'
     * [StatusMessage]      => 'Completed' |
     * [ThumbUrl]           => url relative to svc host
     * [ViewerUrl]          => url
     */

    namespace panoptolite;
    use \stdClass, \SoapFault;

    defined ('MOODLE_INTERNAL') || die();

    require_once(__DIR__ . '/base_soap_client.php');
    require_once(__DIR__ . '/list_folders_soap_param.php');
    require_once(__DIR__ . '/list_recordings_soap_param.php');



    /**
     * SOAP client wrapper tailored for Panopto's session (folder)
     * management web service endpoint
     */
    class folders_soap_client extends base_soap_client
    {


        /**
         * @var string Endpoint service name
         */
        const svcname = 'SessionManagement.svc';

        /**
         * @var string Endpoint version number
         */
        const svcvers = '4.6';


        const DUPLICATE_FOLDER_NAME = "s:Client:Duplicate";



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
         * Get a collection of folders.
         *
         * @param mixed(array|string) $folderids An array of folder id (GUID) values or a single value
         * @return array
         */
        public function get_folders($folderids)
        {

            if (empty($folderids)) {
                return null;
            }

            $params = array('folderIds' => is_array($folderids) ? $folderids : array($folderids));

            $result = $this->call_soap_method('GetFoldersWithExternalContextById', $params);
            if ($this->success() && $result != null) {
                return $result->FolderWithExternalContext;
            }

            return null;

        }


        /**
         * Create a Panopto folder (counterpart to Moodle course).
         *
         * @param string $name Folder name
         * @param string $courseid Moodle course id
         * @return mixed(null|stdClass)
         */
        public function create_folder($name, $courseid)
        {

            if (empty($name) || empty($courseid)) {
                return null;
            }

            // Note on external id: if a Panopto folder exists with a
            // matching external id, then that folder will be returned
            // instead of a newly created one.
            $params = array('name' => $name, 'externalId' => $this->instance_folder_external_id($courseid));

            $result = $this->call_soap_method('ProvisionExternalCourse', $params);
            if (!$this->success() || $result == null) {
                if ($this->result() instanceof SoapFault) {
                    // One expected fault would be when duplicate folder
                    // name encountered. Service doesn't indicate that
                    // situation too well, returns message:
                    // "Value cannot be null.\r\n"
                    // "Parameter name: sessionGroup"
                    if ($this->result()->faultstring === "Value cannot be null.\r\nParameter name: sessionGroup") {
                        // Assume is duplicate name, tweak fault code to
                        // be more informative.
                        $this->set_result(new SoapFault(self::DUPLICATE_FOLDER_NAME, "Duplicate folder name."));
                    }
                }
                return null;
            }

            return $result;

        }


        /**
         * Get list of folders for which specified user has a creator role.
         *
         * @param string $username Moodle username
         * @return mixed(array|null)
         */
        public function get_folders_for_user($username)
        {

            if (empty($username)) {
                return null;
            }

            $params = array('request' => new list_folders_soap_param(), 'searchQuery' => null);

            // $result has two members, Results and TotalNumberResults,
            // and Results has a single member Folder which is an array
            // of Panopto folder objects.
            $result = $this->call_soap_method('GetCreatorFoldersList', $params, $username);
            if ($this->success() && $result != null && $result->TotalNumberResults > 0) {
                return $result->Results->Folder;
            }

            return null;

        }


        /**
         * Get collection of folders with external ids corresponding to the array of course ids
         *
         * @param mixed(string|array) $courseids
         * @return mixed(null|array)
         */
        public function get_folders_by_courseids($courseids)
        {

            if (empty($courseids)) {
                return null;
            }
            if (!is_array($courseids)) {
                $courseids = array($courseids);
            }

            $externalids = array();
            foreach ($courseids as $courseid) {
                $externalids[] = $this->instance_folder_external_id($courseid);
            }

            $params = array('folderExternalIds' => $externalids, 'providerNames' => array($this->get_apiinstance()));

            $result = $this->call_soap_method('GetAllFoldersWithExternalContextByExternalId', $params);
            if ($this->success() && $result != null) {
                return $result->FolderWithExternalContext;
            }

            return null;

        }


        /**
         * Get collection of recordings for the specified recording id values
         *
         * @param mixed(string|array) $recordingids An array of recording id values or a single value
         * @return mixed(array)
         */
        public function get_recordings($recordingids)
        {

            if (empty($recordingids)) {
                return null;
            }

            $params = array('sessionIds' => is_array($recordingids) ? $recordingids : array($recordingids));

            $result = $this->call_soap_method('GetSessionsById', $params);
            if ($this->success() && $result != null) {
                return $result->Session;
            }

            return null;

        }


        /**
         * Get collection of recordings for the specified folder
         *
         * @param string $folderid Panopto folder id
         * @return mixed(null|array)
         */
        public function get_recordings_by_folderid($folderid)
        {

            if (empty($folderid)) {
                return null;
            }

            $params = array('request' => new list_recordings_soap_param($folderid), 'searchQuery' => null);

            // $result has two members, Results and TotalNumberResults,
            // and Results has a single member Session which is an array
            // of Panopto recordings (Session objects).
            $result = $this->call_soap_method('GetSessionsList', $params);
            if ($this->success() && $result != null) {
                if ($result->TotalNumberResults > 0) {
                    return $result->Results->Session;
                } else {
                    return array();
                }
            }

            return null;

        }


        /**
         * Get collection of recordings for the specified user
         *
         * @param string $username Moodle username
         * @return mixed(null|array)
         */
        public function get_recordings_by_username($username)
        {

            if (empty($username)) {
                return null;
            }

            $params = array('request' => new list_recordings_soap_param(), 'searchQuery' => null);

            // $result has two members, Results and TotalNumberResults,
            // and Results has a single member Session which is an array
            // of Panopto recordings (Session objects).
            $result = $this->call_soap_method('GetSessionsList', $params, $username);
            if ($this->success() && $result != null) {
                if ($result->TotalNumberResults > 0) {
                    return $result->Results->Session;
                } else {
                    return array();
                }
            }

            return null;

        }


        /**
         * Sets a folder's access groups.
         *
         * @param string $folderid Panopto folder id (GUID)
         * @param int $courseid Moodle course id
         * @return mixed(null|stdClass)
         */
        public function set_folder_groups($folderid, $courseid)
        {

            if (empty($folderid) || empty($courseid)) {
                return null;
            }

            $params = array(
                'name' => $this->instance_folder_external_id($courseid),
                'externalId' => $this->instance_folder_external_id($courseid),
                'folderIds' => array($folderid));

            $result = $this->call_soap_method('SetExternalCourseAccess', $params);
            if ($this->success() && $result != null && !empty($result->Folder) && is_array($result->Folder)) {
                return array_shift($result->Folder);
            }

            return null;

        }

    }
