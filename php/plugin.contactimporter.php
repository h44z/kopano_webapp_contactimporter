<?php
/**
 * plugin.contactimporter.php, zarafa contact to vcf im/exporter
 *
 * Author: Christoph Haas <christoph.h@sprinternet.at>
 * Copyright (C) 2012-2013 Christoph Haas
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
 
/**
 * contactimporter Plugin
 *
 * With this plugin you can import a vcf file to your zarafa addressbook
 *
 */
class Plugincontactimporter extends Plugin {
	/**
	 * Constructor
	 */
	function Plugincontactimporter() {}

	/**
	 * Function initializes the Plugin and registers all hooks
	 *
	 * @return void
	 */
	function init() {
		$this->registerHook('server.core.settings.init.before');
	}

	/**
	 * Function is executed when a hook is triggered by the PluginManager
	 *
	 * @param string $eventID the id of the triggered hook
	 * @param mixed $data object(s) related to the hook
	 * @return void
	 */
	function execute($eventID, &$data) {
		switch($eventID) {
			case 'server.core.settings.init.before' :
				$this->injectPluginSettings($data);
				break;
		}
	}

	/**
	 * Called when the core Settings class is initialized and ready to accept sysadmin default
	 * settings.
	 * @param Array $data Reference to the data of the triggered hook
	 */
	function injectPluginSettings(&$data) {
		$data['settingsObj']->addSysAdminDefaults(Array(
			'zarafa' => Array(
				'v1' => Array(
					'plugins' => Array(
						'contactimporter' => Array(
							'enable'        => PLUGIN_CONTACTIMPORTER_USER_DEFAULT_ENABLE,
							'enable_export' => PLUGIN_CONTACTIMPORTER_USER_DEFAULT_ENABLE_EXPORT,
							'default_addressbook'	=> PLUGIN_CONTACTIMPORTER_DEFAULT
						)
					)
				)
			)
		));
	}
}
?>
