<?php
	/** Disable the import plugin for all clients */
	define('PLUGIN_CONTACTIMPORTER_USER_DEFAULT_ENABLE', false);
	/** Disable the export feature for all clients */
	define('PLUGIN_CONTACTIMPORTER_USER_DEFAULT_ENABLE_EXPORT', false); // currently not available
	
	/** The default addressbook to import to (default: contact)*/
	define('PLUGIN_CONTACTIMPORTER_DEFAULT', "contact");
	
	/** Tempory path for uploaded files... */
	define('PLUGIN_CONTACTIMPORTER_TMP_UPLOAD', "/var/lib/zarafa-webapp/tmp/");
?>
