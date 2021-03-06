/**
 * plugin.contactimporter.js, Kopano Webapp contact to vcf im/exporter
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

Ext.namespace("Zarafa.plugins.contactimporter");									// Assign the right namespace

Zarafa.plugins.contactimporter.ImportPlugin = Ext.extend(Zarafa.core.Plugin, {		// create new import plugin

    /**
     * @constructor
     * @param {Object} config Configuration object
     *
     */
    constructor: function (config) {
        config = config || {};

        Zarafa.plugins.contactimporter.ImportPlugin.superclass.constructor.call(this, config);
    },

    /**
     * initialises insertion point for plugin
     * @protected
     */
    initPlugin: function () {
        Zarafa.plugins.contactimporter.ImportPlugin.superclass.initPlugin.apply(this, arguments);

        /* our panel */
        Zarafa.core.data.SharedComponentType.addProperty('plugins.contactimporter.dialogs.importcontacts');

        /* directly import received vcfs */
        this.registerInsertionPoint('common.contextmenu.attachment.actions', this.createAttachmentImportButton, this);

        /* export a contact via rightclick */
        this.registerInsertionPoint('context.contact.contextmenu.actions', this.createItemExportInsertionPoint, this);
    },

    /**
     * This method hooks to the contact context menu and allows users to export users to vcf.
     *
     * @param include
     * @param btn
     * @returns {Object}
     */
    createItemExportInsertionPoint: function (include, btn) {
        return {
            text: dgettext('plugin_contactimporter', 'Export vCard'),
            handler: this.exportToVCF.createDelegate(this, [btn]),
            scope: this,
            iconCls: 'icon_contactimporter_export'
        };
    },

    /**
     * Generates a request to download the selected records as vCard.
     * @param {Ext.Button} btn
     */
    exportToVCF: function (btn) {
        if (btn.records.length == 0) {
            return; // skip if no records where given!
        }

        var recordIds = [];

        for (var i = 0; i < btn.records.length; i++) {
            recordIds.push(btn.records[i].get("entryid"));
        }

        Zarafa.plugins.contactimporter.data.Actions.exportToVCF(btn.records[0].get("store_entryid"), recordIds, undefined);
    },

    /**
     * Insert import button in all attachment suggestions

     * @return {Object} Configuration object for a {@link Ext.Button button}
     */
    createAttachmentImportButton: function (include, btn) {
        return {
            text: dgettext('plugin_contactimporter', 'Import to Contacts'),
            handler: this.getAttachmentFileName.createDelegate(this, [btn]),
            scope: this,
            iconCls: 'icon_contactimporter_button',
            beforeShow: function (item, record) {
                var extension = record.data.name.split('.').pop().toLowerCase();

                if (record.data.filetype == "text/vcard" || record.data.filetype == "text/x-vcard" || extension == "vcf" || extension == "vcard") {
                    item.setVisible(true);
                } else {
                    item.setVisible(false);
                }
            }
        };
    },

    /**
     * Callback for getAttachmentFileName
     * @param {Object} response
     */
    gotAttachmentFileName: function (response) {
        if (response.status == true) {
            this.openImportDialog(response.tmpname);
        } else {
            Zarafa.common.dialogs.MessageBox.show({
                title: dgettext('plugin_contactimporter', 'Error'),
                msg: _(response["message"]),
                icon: Zarafa.common.dialogs.MessageBox.ERROR,
                buttons: Zarafa.common.dialogs.MessageBox.OK
            });
        }
    },

    /**
     * Clickhandler for the button
     * @param {Ext.Button} btn
     */
    getAttachmentFileName: function (btn) {
        Zarafa.common.dialogs.MessageBox.show({
            title: dgettext('plugin_contactimporter', 'Please wait'),
            msg: dgettext('plugin_contactimporter', 'Loading attachment...'),
            progressText: dgettext('plugin_contactimporter', 'Initializing...'),
            width: 300,
            progress: true,
            closable: false
        });

        // progress bar... ;)
        var f = function (v) {
            return function () {
                if (v == 100) {
                    Zarafa.common.dialogs.MessageBox.hide();
                } else {
                    // # TRANSLATORS: {0} will be replaced by the percentage value (0-100)
                    Zarafa.common.dialogs.MessageBox.updateProgress(v / 100, String.format(dgettext('plugin_contactimporter', '{0}% loaded'), Math.round(v)));
                }
            };
        };

        for (var i = 1; i < 101; i++) {
            setTimeout(f(i), 20 * i);
        }

        /* store the attachment to a temporary folder and prepare it for uploading */
        var attachmentRecord = btn.records;
        var attachmentStore = attachmentRecord.store;

        var store = attachmentStore.getParentRecord().get('store_entryid');
        var entryid = attachmentStore.getAttachmentParentRecordEntryId();
        var attachNum = new Array(1);
        if (attachmentRecord.get('attach_num') != -1) {
            attachNum[0] = attachmentRecord.get('attach_num');
        } else {
            attachNum[0] = attachmentRecord.get('tmpname');
        }
        var dialog_attachments = attachmentStore.getId();
        var filename = attachmentRecord.data.name;

        var responseHandler = new Zarafa.plugins.contactimporter.data.ResponseHandler({
            successCallback: this.gotAttachmentFileName.createDelegate(this),
            scope: this
        });

        // request attachment preperation
        container.getRequest().singleRequest(
            'contactmodule',
            'importattachment',
            {
                entryid: entryid,
                store: store,
                attachNum: attachNum,
                dialog_attachments: dialog_attachments,
                filename: filename
            },
            responseHandler
        );
    },

    /**
     * Open the import dialog.
     * @param {String} filename
     */
    openImportDialog: function (filename) {
        var componentType = Zarafa.core.data.SharedComponentType['plugins.contactimporter.dialogs.importcontacts'];
        var config = {
            filename: filename,
            modal: true
        };

        Zarafa.core.data.UIFactory.openLayerComponent(componentType, undefined, config);
    },

    /**
     * Bid for the type of shared component
     * and the given record.
     * This will bid on calendar.dialogs.importcontacts
     * @param {Zarafa.core.data.SharedComponentType} type Type of component a context can bid for.
     * @param {Ext.data.Record} record Optionally passed record.
     * @return {Number} The bid for the shared component
     */
    bidSharedComponent: function (type, record) {
        var bid = -1;
        switch (type) {
            case Zarafa.core.data.SharedComponentType['plugins.contactimporter.dialogs.importcontacts']:
                bid = 1;
                break;
            case Zarafa.core.data.SharedComponentType['common.contextmenu']:
                if (record instanceof Zarafa.core.data.MAPIRecord) {
                    if (record.get('object_type') == Zarafa.core.mapi.ObjectType.MAPI_FOLDER && record.get('container_class') == "IPF.Contact") {
                        bid = 2;
                    }
                }
                break;
        }
        return bid;
    },

    /**
     * Will return the reference to the shared component.
     * Based on the type of component requested a component is returned.
     * @param {Zarafa.core.data.SharedComponentType} type Type of component a context can bid for.
     * @param {Ext.data.Record} record Optionally passed record.
     * @return {Ext.Component} Component
     */
    getSharedComponent: function (type, record) {
        var component;
        switch (type) {
            case Zarafa.core.data.SharedComponentType['plugins.contactimporter.dialogs.importcontacts']:
                component = Zarafa.plugins.contactimporter.dialogs.ImportContentPanel;
                break;
            case Zarafa.core.data.SharedComponentType['common.contextmenu']:
                component = Zarafa.plugins.contactimporter.ui.ContextMenu;
                break;
        }

        return component;
    }
});


/*############################################################################################################################
 * STARTUP 
 *############################################################################################################################*/
Zarafa.onReady(function () {
    container.registerPlugin(new Zarafa.core.PluginMetaData({
        name: 'contactimporter',
        displayName: dgettext('plugin_contactimporter', 'Contactimporter Plugin'),
        about: Zarafa.plugins.contactimporter.ABOUT,
        pluginConstructor: Zarafa.plugins.contactimporter.ImportPlugin
    }));
});
