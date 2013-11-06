/**
 * plugin.contactimporter.js zarafa contactimporter
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
	initPlugin : function()	{
		Zarafa.plugins.contactimporter.ImportPlugin.superclass.initPlugin.apply(this, arguments);
		
		/* our panel */
		Zarafa.core.data.SharedComponentType.addProperty('plugins.contactimporter.dialogs.importcontacts');
		
		/* directly import received vcfs */
		this.registerInsertionPoint('common.contextmenu.attachment.actions', this.createAttachmentImportButton);
		/* add import button to south navigation */
		this.registerInsertionPoint("navigation.south", this.createImportButton, this);
	},
	
    /**
     * Creates the button
     *
     * @return {Object} Configuration object for a {@link Ext.Button button}
     * 
     */
	createImportButton: function () {
		var button = {
			xtype				: 'button',
			id		  			: "importcontactsbutton",
			text				: _('Import Contacts'),
			iconCls				: 'icon_contactimporter_button',
			navigationContext	: container.getContextByName('contact'),
			handler				: this.onImportButtonClick,
			scope				: this
		};
		
		if(container.getSettingsModel().get("zarafa/v1/plugins/contactimporter/enable_export")) {
			button.text = _('Import/Export Contacts');
		}
		
		return  button;
	},
	
	/**
	 * Insert import button in all attachment suggestions
	 
	 * @return {Object} Configuration object for a {@link Ext.Button button}
	 */
	createAttachmentImportButton : function(include, btn) {
		return {
			text 		: _('Import Contacts'),
			handler 	: this.getAttachmentFileName.createDelegate(this, [btn, this.gotAttachmentFileName]),
			scope		: this,
			iconCls		: 'icon_contactimporter_button',
			beforeShow 	: function(item, record) {
				var extension = record.data.name.split('.').pop().toLowerCase();
				
				if(record.data.filetype  == "text/vcard" || extension == "vcf" || extension == "vcard") {
					item.setDisabled(false);
				} else {
					item.setDisabled(true);
				}
			}
		};
	},
	
	/**
	 * Callback for getAttachmentFileName
	 */
	gotAttachmentFileName: function(response) {
		if(response.status == true) {
			Zarafa.core.data.UIFactory.openLayerComponent(Zarafa.core.data.SharedComponentType['plugins.contactimporter.dialogs.importcontacts'], undefined, {
				manager : Ext.WindowMgr,
				filename : response.tmpname
			});
		} else {
			Zarafa.common.dialogs.MessageBox.show({
				title   : _('Error'),
				msg     : _(response["message"]),
				icon    : Zarafa.common.dialogs.MessageBox.ERROR,
				buttons : Zarafa.common.dialogs.MessageBox.OK
			});
		}
	},

	/**
	 * Clickhandler for the button
	 */
	getAttachmentFileName: function (btn, callback) {
		Zarafa.common.dialogs.MessageBox.show({
			title: 'Please wait',
			msg: 'Loading attachment...',
			progressText: 'Initializing...',
			width:300,
			progress:true,
			closable:false
		});

		// progress bar... ;)
		var f = function(v){
			return function(){
				if(v == 100){
					Zarafa.common.dialogs.MessageBox.hide();
				}else{
					Zarafa.common.dialogs.MessageBox.updateProgress(v/100, Math.round(v)+'% loaded');
				}
		   };
		};
		
		for(var i = 1; i < 101; i++){
			setTimeout(f(i), 20*i);
		}
		
		/* store the attachment to a temporary folder and prepare it for uploading */
		var attachmentRecord = btn.records;
		var attachmentStore = attachmentRecord.store;
		
		var store = attachmentStore.getParentRecord().get('store_entryid');
		var entryid = attachmentStore.getAttachmentParentRecordEntryId();
		var attachNum = new Array(1);
		if (attachmentRecord.get('attach_num') != -1)
			attachNum[0] = attachmentRecord.get('attach_num');
		else
			attachNum[0] = attachmentRecord.get('tmpname');
		var dialog_attachments = attachmentStore.getId();
		var filename = attachmentRecord.data.name;
		
		var responseHandler = new Zarafa.plugins.contactimporter.data.ResponseHandler({
			successCallback: callback
		});
		
		// request attachment preperation
		container.getRequest().singleRequest(
			'contactmodule',
			'importattachment',
			{
				entryid : entryid,
				store: store,
				attachNum: attachNum,
				dialog_attachments: dialog_attachments,
				filename: filename
			},
			responseHandler
		);
	},
	
	/**
	 * Clickhandler for the button
	 */
	onImportButtonClick: function () {
		Ext.getCmp("importcontactsbutton").disable();
		Zarafa.core.data.UIFactory.openLayerComponent(Zarafa.core.data.SharedComponentType['plugins.contactimporter.dialogs.importcontacts'], undefined, {
			manager : Ext.WindowMgr
		});
	},
		
	/**
	 * Bid for the type of shared component
	 * and the given record.
	 * This will bid on calendar.dialogs.importcontacts
	 * @param {Zarafa.core.data.SharedComponentType} type Type of component a context can bid for.
	 * @param {Ext.data.Record} record Optionally passed record.
	 * @return {Number} The bid for the shared component
	 */
	bidSharedComponent : function(type, record) {
		var bid = -1;
		switch(type) {
			case Zarafa.core.data.SharedComponentType['plugins.contactimporter.dialogs.importcontacts']:
				bid = 2;
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
	getSharedComponent : function(type, record) {
		var component;
		switch(type) {
			case Zarafa.core.data.SharedComponentType['plugins.contactimporter.dialogs.importcontacts']:
				component = Zarafa.plugins.contactimporter.dialogs.ImportContentPanel;
				break;
		}

		return component;
	}
});


/*############################################################################################################################
 * STARTUP 
 *############################################################################################################################*/
Zarafa.onReady(function() {
	if(container.getSettingsModel().get("zarafa/v1/plugins/contactimporter/enable") === true) {
		container.registerPlugin(new Zarafa.core.PluginMetaData({
			name : 'contactimporter',
			displayName : _('Contactimporter Plugin'),
			about : Zarafa.plugins.contactimporter.ABOUT,
			allowUserDisable : true,
			pluginConstructor : Zarafa.plugins.contactimporter.ImportPlugin
		}));
	}
});
