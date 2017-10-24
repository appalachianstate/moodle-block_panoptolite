## PanoptoLite block plugin

**This plugin is neither endorsed nor supported by Panopto, Inc.**

This block uses the Panopto web service APIs to integrate Moodle courses and users with Panopto folders and access groups. When the block is loaded onto a course page, and then configured, it will display the recordings (sessions) contained in the selected folder on the course page. When it is loaded onto a user dashboard (/my page), it will display a list of recordings to which the user has access, grouped by folder.

**This plugin is not interoperable with Panopto's block plugin. You must choose to use one or the other.**

#### **Reason for this plugin?**

Panopto's new version of their Moodle plugin was written to use their new web services API endpoints, rather than their old _ClientData.svc_ endpoint. However, where they tried to make some improvements in efficiency, they made choices about how and when enrollments should be synchronized. Those choices were not the best ones for our particular LMS. Their plugin synchronizes each user's entire course enrollments to Panopto's site when they login to Moodle. This plugin only syncs a user enrollment to the corresponding external (access) groups when there is an enrollment change signalled by the role_assigned and role_unassigned events.

Some distinctions between this plugin and Panopto's are:

 - Sync enrollments on the role_assigned and role_unassigned events
 - Use standard Moodle block instance configuration form
 - Use block instance config data, i.e. no custom tables created
 - Can use block on dashboard (/my page) as well as course page
 - Select existing folder without having to create (provision) one
 - Uses specific Panopto user account for access (some exceptions)
 - Does not allow for multi-server configuration
 - Reduces soap client code to only what is needed 
