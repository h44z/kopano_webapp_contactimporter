/**
 * ImportContentPanel.js, Kopano Webapp contact to vcf im/exporter
 *
 * Author: Christoph Haas <christoph.h@sprinternet.at>
 * Copyright (C) 2012-2018 Christoph Haas
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
 * ImportContentPanel
 *
 * Container for the importpanel.
 */
Ext.namespace("Zarafa.plugins.contactimporter.dialogs");

/**
 * @class Zarafa.plugins.contactimporter.dialogs.ImportContentPanel
 * @extends Zarafa.core.ui.ContentPanel
 *
 * The content panel which shows the hierarchy tree of Owncloud account files.
 * @xtype contactimportercontentpanel
 */
Zarafa.plugins.contactimporter.dialogs.ImportContentPanel = Ext.extend(Zarafa.core.ui.ContentPanel, {

    /**
     * @constructor
     * @param config Configuration structure
     */
    constructor: function (config) {
        config = config || {};
        var title = dgettext('plugin_contactimporter', 'Import Contacts');
        Ext.applyIf(config, {
            layout: 'fit',
            title: title,
            closeOnSave: true,
            width: 620,
            height: 465,
            //Add panel
            items: [
                {
                    xtype: 'contactimporter.importcontactpanel',
                    filename: config.filename,
                    folder: config.folder
                }
            ]
        });

        Zarafa.plugins.contactimporter.dialogs.ImportContentPanel.superclass.constructor.call(this, config);
    }

});

Ext.reg('contactimporter.contentpanel', Zarafa.plugins.contactimporter.dialogs.ImportContentPanel);