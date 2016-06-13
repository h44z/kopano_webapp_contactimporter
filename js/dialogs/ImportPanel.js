/**
 * ImportPanel.js zarafa contact to vcf im/exporter
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
 * ImportPanel
 *
 * The main Panel of the contactimporter plugin.
 */
Ext.namespace("Zarafa.plugins.contactimporter.dialogs"); 

/**
 * @class Zarafa.plugins.contactimporter.dialogs.ImportPanel
 * @extends Ext.form.FormPanel
 */
Zarafa.plugins.contactimporter.dialogs.ImportPanel = Ext.extend(Ext.Panel, {

	/* path to vcf file on server... */
	vcffile: null,
	
	/* The store for the selection grid */
	store: null,

	/**
	 * @constructor
	 * @param {object} config
	 */
	constructor : function (config) {
		config = config || {};
		var self = this;
		
		if(!Ext.isEmpty(config.filename)) {
			this.vcffile = config.filename;
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
			xtype     : 'contactimporter.importpanel',
			ref		  : "importcontactpanel",
			layout    : {
				type  : 'form',
				align : 'stretch'
			},
			anchor	  : '100%',
			bodyStyle : 'background-color: inherit;',
			defaults  : {
				border      : true,
				bodyStyle   : 'background-color: inherit; padding: 3px 0px 3px 0px; border-style: none none solid none;'
			},
			items : [
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
					this.loadMask = new Ext.LoadMask(this.getEl(), {msg:'Loading...'});
					
					if(this.vcffile != null) { // if we have got the filename from an attachment
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
	initForm : function () {
		return {
			xtype: 'form',
			ref: 'addContactFormPanel',
			layout : 'column',
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
	reloadGridStore: function(contactdata) {
		var parsedData = [];
				
		if(contactdata) {
			parsedData = new Array(contactdata.contacts.length);
			var i = 0;
			for(i = 0; i < contactdata.contacts.length; i++) {
				
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
	createGrid : function() {
		return {
			xtype: 'grid',
			ref: 'contactGrid',
			columnWidth: 1.0,
			store: this.store,
			width: '100%',
			height: 300,
			title: 'Select contacts to import',
			frame: false,
			viewConfig:{
				forceFit:true
			},
			colModel: new Ext.grid.ColumnModel({
				defaults: {
					width: 300,
					sortable: true
				},
				columns: [
					{id: 'Displayname', header: 'Displayname', width: 350, sortable: true, dataIndex: 'display_name'},
					{header: 'Firstname', width: 200, sortable: true, dataIndex: 'given_name'},
					{header: 'Lastname', width: 200, sortable: true, dataIndex: 'surname'},
					{header: 'Company', sortable: true, dataIndex: 'company_name'}
				]
			}),
			sm: new Ext.grid.RowSelectionModel({multiSelect:true})
		}
	},
	
	createSelectBox: function() {
		var defaultFolder = container.getHierarchyStore().getDefaultFolder('contact'); // @type: Zarafa.hierarchy.data.MAPIFolderRecord
		var subFolders = defaultFolder.getChildren();
		var myStore = [];
		
		/* add all local contact folders */
		var i = 0;
		myStore.push([defaultFolder.getDefaultFolderKey(), defaultFolder.getDisplayName()]);
		for(i = 0; i < subFolders.length; i++) {
			/* Store all subfolders */
			myStore.push([subFolders[i].getDisplayName(), subFolders[i].getDisplayName(), false]); // 3rd field = isPublicfolder
		}
		
		/* add all shared contact folders */
		var pubStore = container.getHierarchyStore().getPublicStore();
		
		if(typeof pubStore !== "undefined") {
			try {
				var pubFolder = pubStore.getDefaultFolder("publicfolders");
				var pubSubFolders = pubFolder.getChildren();
				for(i = 0; i < pubSubFolders.length; i++) {
					if(pubSubFolders[i].isContainerClass("IPF.Contact")){
						myStore.push([pubSubFolders[i].getDisplayName(), pubSubFolders[i].getDisplayName() + " [Shared]", true]); // 3rd field = isPublicfolder
					}
				}
			} catch (e) {
				console.log("Error opening the shared folder...");
				console.log(e);
			}
		}
		
		return {
			xtype: "selectbox",
			ref: 'addressbookSelector', 
			editable: false,
			name: "choosen_addressbook",
			value: container.getSettingsModel().get("zarafa/v1/plugins/contactimporter/default_addressbook"),
			width: 100,
			fieldLabel: "Select an addressbook",
			store: myStore,
			mode: 'local',
			labelSeperator: ":",
			border: false,
			anchor: "100%",
			scope: this,
			allowBlank: false
		}
	},
	
	createUploadField: function() {
		return {
			xtype: "fileuploadfield",
			ref: 'contactfileuploadfield',
			columnWidth: 1.0,
			id: 'form-file',    
			name: 'vcfdata',
			emptyText: 'Select an .vcf addressbook',
			border: false,
			anchor: "100%",
			scope: this,
			allowBlank: false,
			listeners: {
				'fileselected': this.onFileSelected,
				scope: this
			}
		}
	},
	
	createSubmitButton: function() {
		return {
			xtype: "button",
			ref: "../submitButton",
			disabled: true,
			width: 100,
			border: false,
			text: _("Import"),
			anchor: "100%",
			handler: this.importCheckedContacts,
			scope: this,
			allowBlank: false
		}
	},
	
	createSubmitAllButton: function() {
		return {
			xtype: "button",
			ref: "../submitAllButton",
			disabled: true,
			width: 100,
			border: false,
			text: _("Import All"),
			anchor: "100%",
			handler: this.importAllContacts,
			scope: this,
			allowBlank: false
		}
	},
	
	createCancelButton: function() {
		return {
			xtype: "button",
			width: 100,
			border: false,
			text: _("Cancel"),
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
	onFileSelected : function(uploadField) {
		var form = this.addContactFormPanel.getForm();

		if (form.isValid()) {
			form.submit({
				waitMsg: 'Uploading and parsing contacts...',
				url: 'plugins/contactimporter/php/upload.php',
				failure: function(file, action) {
					this.submitButton.disable();
					this.submitAllButton.disable();
					Zarafa.common.dialogs.MessageBox.show({
						title   : _('Error'),
						msg     : _(action.result.error),
						icon    : Zarafa.common.dialogs.MessageBox.ERROR,
						buttons : Zarafa.common.dialogs.MessageBox.OK
					});
				},
				success: function(file, action){
					uploadField.reset();
					this.vcffile = action.result.vcf_file;
					
					this.parseContacts(this.vcffile);
				},
				scope : this
			});
		}
	},
	
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
	
	handleParsingResult: function(response) {
		this.loadMask.hide();
		
		if(response["status"] == true) {
			this.submitButton.enable();
			this.submitAllButton.enable();
			
			this.reloadGridStore(response.parsed);
		} else {
			this.submitButton.disable();
			this.submitAllButton.disable();
			Zarafa.common.dialogs.MessageBox.show({
				title   : _('Parser Error'),
				msg     : _(response["message"]),
				icon    : Zarafa.common.dialogs.MessageBox.ERROR,
				buttons : Zarafa.common.dialogs.MessageBox.OK
			});
		}
	},

	close: function () {
		this.addContactFormPanel.getForm().reset();
		this.dialog.close()
	},

	importCheckedContacts: function () {
		var newRecords = this.contactGrid.selModel.getSelections();
		this.importContacts(newRecords);
    },

	importAllContacts: function () {
		//receive Records from grid rows
		this.contactGrid.selModel.selectAll();  // select all entries
		var newRecords = this.contactGrid.selModel.getSelections();
		this.importContacts(newRecords);
    },
	
	/** 
	 * This function stores all given events to the appointmentstore 
	 * @param events
	 */
	importContacts: function (contacts) {
		//receive existing contact store
		var folderValue = this.addressbookSelector.getValue();

		if(folderValue == undefined) { // no addressbook choosen
			Zarafa.common.dialogs.MessageBox.show({
				title   : _('Error'),
				msg     : _('You have to choose an addressbook!'),
				icon    : Zarafa.common.dialogs.MessageBox.ERROR,
				buttons : Zarafa.common.dialogs.MessageBox.OK
			});
		} else {
			var addressbookexist = true;
			if(this.contactGrid.selModel.getCount() < 1) {
				Zarafa.common.dialogs.MessageBox.show({
					title   : _('Error'),
					msg     : _('You have to choose at least one contact to import!'),
					icon    : Zarafa.common.dialogs.MessageBox.ERROR,
					buttons : Zarafa.common.dialogs.MessageBox.OK
				});
			} else {
				var contactStore = new Zarafa.contact.ContactStore();
				var contactFolder =  container.getHierarchyStore().getDefaultFolder('contact');
				var pubStore = container.getHierarchyStore().getPublicStore();
				var pubFolder = pubStore.getDefaultFolder("publicfolders");
				var pubSubFolders = pubFolder.getChildren();
			
				if(folderValue != "contact") {
					var subFolders = contactFolder.getChildren();
					var i = 0;
					for(i = 0; i < pubSubFolders.length; i++) {
						if(pubSubFolders[i].isContainerClass("IPF.Contact")){
							subFolders.push(pubSubFolders[i]);
						}
					}
					for(i=0;i<subFolders.length;i++) {
						// look up right folder 
						// TODO: improve!!
						if(subFolders[i].getDisplayName() == folderValue) {
							contactFolder = subFolders[i];
							break;
						}
					}
					
					if(contactFolder.isDefaultFolder()) {
						Zarafa.common.dialogs.MessageBox.show({
							title   : _('Error'),
							msg     : _('Selected addressbook does not exist!'),
							icon    : Zarafa.common.dialogs.MessageBox.ERROR,
							buttons : Zarafa.common.dialogs.MessageBox.OK
						});
						addressbookexist = false;
					}
				}

				if(addressbookexist) {
					this.loadMask.show();
					var uids = [];
					var store_entryid = "";
					
					//receive Records from grid rows
					Ext.each(contacts, function(newRecord) {
						uids.push(newRecord.data.record.internal_fields.contact_uid);						
					}, this);
					store_entryid = contactFolder.get('store_entryid');
					
					var responseHandler = new Zarafa.plugins.contactimporter.data.ResponseHandler({
						successCallback: this.importContactsDone.createDelegate(this)
					});
					
					container.getRequest().singleRequest(
						'contactmodule',
						'import',
						{
							storeid: contactFolder.get("store_entryid"),
							folderid: contactFolder.get("entryid"),
							uids: uids,
							vcf_filepath: this.vcffile
						},
						responseHandler
					);
					
				}
			}
		}
	},
	
	importContactsDone : function (response) {
		this.loadMask.hide();
		this.dialog.close();
		if(response.status == true) {
			container.getNotifier().notify('info', 'Imported', 'Imported ' + response.count + ' contacts. Please reload your addressbook!');
		} else {
			Zarafa.common.dialogs.MessageBox.show({
				title   : _('Error'),
				msg     : _('Import failed: ') + response.message,
				icon    : Zarafa.common.dialogs.MessageBox.ERROR,
				buttons : Zarafa.common.dialogs.MessageBox.OK
			});
		}
	}
});

Ext.reg('contactimporter.importcontactpanel', Zarafa.plugins.contactimporter.dialogs.ImportPanel);
