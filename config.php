<?php
/** Disable the import plugin for all clients */
define('PLUGIN_CONTACTIMPORTER_USER_DEFAULT_ENABLE', false);

/** The default addressbook to import to (default: Kontakte or Contacts - depending on your language)*/
define('PLUGIN_CONTACTIMPORTER_DEFAULT', "Kontakte");

/** Tempory path for uploaded files... */
define('PLUGIN_CONTACTIMPORTER_TMP_UPLOAD', "/var/lib/kopano-webapp/tmp/");
?>
