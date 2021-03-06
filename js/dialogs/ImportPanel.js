/**
 * ImportPanel.js, Kopano Webapp contact to vcf im/exporter
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
 * ImportPanel
 *
 * The main Panel of the contactimporter plugin.
 */
Ext.namespace("Zarafa.plugins.contactimporter.dialogs");

/**
 * @class Zarafa.plugins.contactimporter.dialogs.ImportPanel
 * @extends Ext.Panel
 */
Zarafa.plugins.contactimporter.dialogs.ImportPanel = Ext.extend(Ext.Panel, {

    /* path to vcf file on server... */
    vcffile: null,

    /* The store for the selection grid */
    store: null,

    /* selected folder */
    folder: null,

    /**
     * @constructor
     * @param {object} config
     */
    constructor: function (config) {
        config = config || {};
        var self = this;

        if (!Ext.isEmpty(config.filename)) {
            this.vcffile = config.filename;
        }

        if (!Ext.isEmpty(config.folder)) {
            this.folder = config.folder;
        }

        // create the data store
        // we only display the firstname, lastname, homephone and primary email address in our grid
        this.store = new Ext.data.ArrayStore({
            fields: [
                {name: 'display_name'},
                {name: 'given_name'},
                {name: 'surname'},
                {name: 'company_name'},
                {name: 'record'}
            ]
        });

        Ext.apply(config, {
            xtype: 'contactimporter.importpanel',
            ref: "importcontactpanel",
            layout: {
                type: 'form',
                align: 'stretch'
            },
            anchor: '100%',
            bodyStyle: 'background-color: inherit;',
            defaults: {
                border: true,
                bodyStyle: 'background-color: inherit; padding: 3px 0px 3px 0px; border-style: none none solid none;'
            },
            items: [
                this.createSelectBox(),
                this.initForm(),
                this.createGrid()
            ],
            buttons: [
                this.createSubmitAllButton(),
                this.createSubmitButton(),
                this.createCancelButton()
            ],
            listeners: {
                afterrender: function (cmp) {
                    this.loadMask = new Ext.LoadMask(this.getEl(), {msg: dgettext('plugin_contactimporter', 'Loading...')});

                    if (this.vcffile != null) { // if we have got the filename from an attachment
                        this.parseContacts(this.vcffile);
                    }
                },
                scope: this
            }
        });

        Zarafa.plugins.contactimporter.dialogs.ImportPanel.superclass.constructor.call(this, config);
    },

    /**
     * Init embedded form, this is the form that is
     * posted and contains the attachments
     * @private
     */
    initForm: function () {
        return {
            xtype: 'form',
            ref: 'addContactFormPanel',
            layout: 'column',
            fileUpload: true,
            autoWidth: true,
            autoHeight: true,
            border: false,
            bodyStyle: 'padding: 5px;',
            defaults: {
                anchor: '95%',
                border: false,
                bodyStyle: 'padding: 5px;'
            },
            items: [this.createUploadField()]
        };
    },

    /**
     * Reloads the data of the grid
     * @private
     */
    reloadGridStore: function (contactdata) {
        var parsedData = [];

        if (contactdata) {
            parsedData = new Array(contactdata.contacts.length);
            var i = 0;
            for (i = 0; i < contactdata.contacts.length; i++) {

                parsedData[i] = [
                    contactdata.contacts[i]["display_name"],
                    contactdata.contacts[i]["given_name"],
                    contactdata.contacts[i]["surname"],
                    contactdata.contacts[i]["company_name"],
                    contactdata.contacts[i]
                ];
            }
        } else {
            return null;
        }

        this.store.loadData(parsedData, false);
    },

    /**
     * Init embedded form, this is the form that is
     * posted and contains the attachments
     * @private
     */
    createGrid: function () {
        return {
            xtype: 'grid',
            ref: 'contactGrid',
            columnWidth: 1.0,
            store: this.store,
            width: '100%',
            height: 300,
            title: dgettext('plugin_contactimporter', 'Select contacts to import'),
            frame: false,
            viewConfig: {
                forceFit: true
            },
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    width: 300,
                    sortable: true
                },
                columns: [
                    {id: 'Displayname', header: dgettext('plugin_contactimporter', 'Displayname'), width: 350, sortable: true, dataIndex: 'display_name'},
                    {header: dgettext('plugin_contactimporter', 'Firstname'), width: 200, sortable: true, dataIndex: 'given_name'},
                    {header: dgettext('plugin_contactimporter', 'Lastname'), width: 200, sortable: true, dataIndex: 'surname'},
                    {header: dgettext('plugin_contactimporter', 'Company'), sortable: true, dataIndex: 'company_name'}
                ]
            }),
            sm: new Ext.grid.RowSelectionModel({multiSelect: true})
        }
    },

    /**
     * Generate the UI addressbook select box.
     * @returns {*}
     */
    createSelectBox: function () {
        var myStore = Zarafa.plugins.contactimporter.data.Actions.getAllContactFolders(true);

        return {
            xtype: "selectbox",
            ref: 'addressbookSelector',
            editable: false,
            name: "choosen_addressbook",
            value: Ext.isEmpty(this.folder) ? Zarafa.plugins.contactimporter.data.Actions.getContactFolderByName(container.getSettingsModel().get("zarafa/v1/plugins/contactimporter/default_addressbook")).entryid : this.folder,
            width: 100,
            fieldLabel: dgettext('plugin_contactimporter', 'Select folder'),
            store: myStore,
            mode: 'local',
            labelSeperator: ":",
            border: false,
            anchor: "100%",
            scope: this,
            hidden: Ext.isEmpty(this.folder) ? false : true,
            allowBlank: false
        }
    },

    /**
     * Generate the UI upload field.
     * @returns {*}
     */
    createUploadField: function () {
        return {
            xtype: "fileuploadfield",
            ref: 'contactfileuploadfield',
            columnWidth: 1.0,
            id: 'form-file',
            name: 'vcfdata',
            emptyText: dgettext('plugin_contactimporter', 'Select an .vcf addressbook'),
            border: false,
            anchor: "100%",
            height: "30",
            scope: this,
            allowBlank: false,
            listeners: {
                'fileselected': this.onFileSelected,
                scope: this
            }
        }
    },

    /**
     * Generate the UI submit button.
     * @returns {*}
     */
    createSubmitButton: function () {
        return {
            xtype: "button",
            ref: "../submitButton",
            disabled: true,
            width: 100,
            border: false,
            text: dgettext('plugin_contactimporter', 'Import'),
            anchor: "100%",
            handler: this.importCheckedContacts,
            scope: this,
            allowBlank: false
        }
    },

    /**
     * Generate the UI submit all button.
     * @returns {*}
     */
    createSubmitAllButton: function () {
        return {
            xtype: "button",
            ref: "../submitAllButton",
            disabled: true,
            width: 100,
            border: false,
            text: dgettext('plugin_contactimporter', 'Import All'),
            anchor: "100%",
            handler: this.importAllContacts,
            scope: this,
            allowBlank: false
        }
    },

    /**
     * Generate the UI cancel button.
     * @returns {*}
     */
    createCancelButton: function () {
        return {
            xtype: "button",
            width: 100,
            border: false,
            text: dgettext('plugin_contactimporter', 'Cancel'),
            anchor: "100%",
            handler: this.close,
            scope: this,
            allowBlank: false
        }
    },

    /**
     * This is called when a file has been seleceted in the file dialog
     * in the {@link Ext.ux.form.FileUploadField} and the dialog is closed
     * @param {Ext.ux.form.FileUploadField} uploadField being added a file to
     */
    onFileSelected: function (uploadField) {
        var form = this.addContactFormPanel.getForm();

        if (form.isValid()) {
            form.submit({
                waitMsg: dgettext('plugin_contactimporter', 'Uploading and parsing contacts...'),
                url: 'plugins/contactimporter/php/upload.php',
                failure: function (file, action) {
                    this.submitButton.disable();
                    this.submitAllButton.disable();
                    Zarafa.common.dialogs.MessageBox.show({
                        title: dgettext('plugin_contactimporter', 'Error'),
                        msg: action.result.error,
                        icon: Zarafa.common.dialogs.MessageBox.ERROR,
                        buttons: Zarafa.common.dialogs.MessageBox.OK
                    });
                },
                success: function (file, action) {
                    uploadField.reset();
                    this.vcffile = action.result.vcf_file;

                    this.parseContacts(this.vcffile);
                },
                scope: this
            });
        }
    },

    /**
     * Start request to server to parse the given vCard file.
     * @param {string} vcfPath
     */
    parseContacts: function (vcfPath) {
        this.loadMask.show();

        // call export function here!
        var responseHandler = new Zarafa.plugins.contactimporter.data.ResponseHandler({
            successCallback: this.handleParsingResult.createDelegate(this)
        });

        container.getRequest().singleRequest(
            'contactmodule',
            'load',
            {
                vcf_filepath: vcfPath
            },
            responseHandler
        );
    },

    /**
     * Callback for the parsing request.
     * @param {Object} response
     */
    handleParsingResult: function (response) {
        this.loadMask.hide();

        if (response["status"] == true) {
            this.submitButton.enable();
            this.submitAllButton.enable();

            this.reloadGridStore(response.parsed);
        } else {
            this.submitButton.disable();
            this.submitAllButton.disable();
            Zarafa.common.dialogs.MessageBox.show({
                title: dgettext('plugin_contactimporter', 'Parser Error'),
                msg: _(response["message"]),
                icon: Zarafa.common.dialogs.MessageBox.ERROR,
                buttons: Zarafa.common.dialogs.MessageBox.OK
            });
        }
    },

    /**
     * Close the UI dialog.
     */
    close: function () {
        this.addContactFormPanel.getForm().reset();
        this.dialog.close()
    },

    /**
     * Create a request to import all selected contacts.
     */
    importCheckedContacts: function () {
        var newRecords = this.contactGrid.selModel.getSelections();
        this.importContacts(newRecords);
    },

    /**
     * Check all contacts and import them.
     */
    importAllContacts: function () {
        //receive Records from grid rows
        this.contactGrid.selModel.selectAll();  // select all entries
        var newRecords = this.contactGrid.selModel.getSelections();
        this.importContacts(newRecords);
    },

    /**
     * This function stores all given events to the contact store
     * @param {array} contacts
     */
    importContacts: function (contacts) {
        //receive existing contact store
        var folderValue = this.addressbookSelector.getValue();

        if (folderValue == undefined) { // no addressbook choosen
            Zarafa.common.dialogs.MessageBox.show({
                title: dgettext('plugin_contactimporter', 'Error'),
                msg: dgettext('plugin_contactimporter', 'You have to choose an addressbook!'),
                icon: Zarafa.common.dialogs.MessageBox.ERROR,
                buttons: Zarafa.common.dialogs.MessageBox.OK
            });
        } else {
            if (this.contactGrid.selModel.getCount() < 1) {
                Zarafa.common.dialogs.MessageBox.show({
                    title: dgettext('plugin_contactimporter', 'Error'),
                    msg: dgettext('plugin_contactimporter', 'You have to choose at least one contact to import!'),
                    icon: Zarafa.common.dialogs.MessageBox.ERROR,
                    buttons: Zarafa.common.dialogs.MessageBox.OK
                });
            } else {
                var contactFolder = Zarafa.plugins.contactimporter.data.Actions.getContactFolderByEntryid(folderValue);

                this.loadMask.show();
                var uids = [];

                //receive Records from grid rows
                Ext.each(contacts, function (newRecord) {
                    uids.push(newRecord.data.record.internal_fields.contact_uid);
                }, this);

                var responseHandler = new Zarafa.plugins.contactimporter.data.ResponseHandler({
                    successCallback: this.importContactsDone.createDelegate(this)
                });

                container.getRequest().singleRequest(
                    'contactmodule',
                    'import',
                    {
                        storeid: contactFolder.store_entryid,
                        folderid: contactFolder.entryid,
                        uids: uids,
                        vcf_filepath: this.vcffile
                    },
                    responseHandler
                );
            }
        }
    },

    /**
     * Callback for the import request.
     * @param {Object} response
     */
    importContactsDone: function (response) {
        this.loadMask.hide();
        this.dialog.close();
        if (response.status == true) {
            // # TRANSLATORS: {0} will be replaced by the number of contacts that were imported
            container.getNotifier().notify('info', dgettext('plugin_contactimporter', 'Imported'), String.format(dgettext('plugin_contactimporter', 'Imported {0} contacts. Please reload your addressbook!'), response.count));
        } else {
            Zarafa.common.dialogs.MessageBox.show({
                title: dgettext('plugin_contactimporter', 'Error'),
                // # TRANSLATORS: {0} will be replaced by the error message
                msg: String.format(dgettext('plugin_contactimporter', 'Import failed: {0}'), response.message),
                icon: Zarafa.common.dialogs.MessageBox.ERROR,
                buttons: Zarafa.common.dialogs.MessageBox.OK
            });
        }
    }
});

Ext.reg('contactimporter.importcontactpanel', Zarafa.plugins.contactimporter.dialogs.ImportPanel);
