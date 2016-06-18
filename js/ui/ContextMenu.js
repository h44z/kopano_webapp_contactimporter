Ext.namespace('Zarafa.plugins.contactimporter.ui');

/**
 * @class Zarafa.plugins.contactimporter.ui.ContextMenu
 * @extends Zarafa.hierarchy.ui.ContextMenu
 * @xtype contactimporter.hierarchycontextmenu
 */
Zarafa.plugins.contactimporter.ui.ContextMenu = Ext.extend(Zarafa.hierarchy.ui.ContextMenu, {

	/**
	 * @constructor
	 * @param {Object} config Configuration object
	 */
	constructor: function (config) {
		config = config || {};

		if (config.contextNode) {
			config.contextTree = config.contextNode.getOwnerTree();
		}

		Zarafa.plugins.contactimporter.ui.ContextMenu.superclass.constructor.call(this, config);

		// add item to menu
		var additionalItems = this.createAdditionalContextMenuItems(config);
		for (var i = 0; i < additionalItems.length; i++) {
			config.items[0].push(additionalItems[i]);
		}

		Zarafa.plugins.contactimporter.ui.ContextMenu.superclass.constructor.call(this, config); // redo ... otherwise menu does not get published
	},

	/**
	 * Create the Action context menu items.
	 * @param {Object} config Configuration object for the {@link Zarafa.plugins.contactimporter.ui.ContextMenu ContextMenu}
	 * @return {Zarafa.core.ui.menu.ConditionalItem[]} The list of Action context menu items
	 * @private
	 *
	 * Note: All handlers are called within the scope of {@link Zarafa.plugins.contactimporter.ui.ContextMenu HierarchyContextMenu}
	 */
	createAdditionalContextMenuItems: function (config) {
		return [{
			xtype: 'menuseparator'
		}, {
			text      : _('Import vCard'),
			iconCls   : 'icon_contactimporter_import',
			handler   : this.onContextItemImport,
			beforeShow: function (item, record) {
				var access = record.get('access') & Zarafa.core.mapi.Access.ACCESS_MODIFY;
				if (!access || (record.isIPMSubTree() && !record.getMAPIStore().isDefaultStore())) {
					item.setDisabled(true);
				} else {
					item.setDisabled(false);
				}
			}
		}, {
			text      : _('Export vCard'),
			iconCls   : 'icon_contactimporter_export',
			handler   : this.onContextItemExport,
			beforeShow: function (item, record) {
				var access = record.get('access') & Zarafa.core.mapi.Access.ACCESS_READ;
				if (!access || (record.isIPMSubTree() && !record.getMAPIStore().isDefaultStore())) {
					item.setDisabled(true);
				} else {
					item.setDisabled(false);
				}
			}
		}];
	},

	/**
	 * Fires on selecting 'Open' menu option from {@link Zarafa.plugins.contactimporter.ui.ContextMenu ContextMenu}
	 * @private
	 */
	onContextItemExport: function () {
		var responseHandler = new Zarafa.plugins.contactimporter.data.ResponseHandler({
			successCallback: this.downloadVCF,
			scope          : this
		});

		// request attachment preperation
		container.getRequest().singleRequest(
			'contactmodule',
			'export',
			{
				storeid: this.records.get("store_entryid"),
				folder : this.records.get("entryid")
			},
			responseHandler
		);
	},

	/**
	 * Fires on selecting 'Open' menu option from {@link Zarafa.plugins.contactimporter.ui.ContextMenu ContextMenu}
	 * @private
	 */
	onContextItemImport: function () {
		var componentType = Zarafa.core.data.SharedComponentType['plugins.contactimporter.dialogs.importcontacts'];
		var config = {
			modal : true,
			folder: this.records.get("entryid")
		};

		Zarafa.core.data.UIFactory.openLayerComponent(componentType, undefined, config);
	},

	/**
	 * Callback for the export request.
	 * @param {Object} response
	 */
	downloadVCF: function (response) {
		if (response.status == false) {
			Zarafa.common.dialogs.MessageBox.show({
				title  : dgettext('plugin_files', 'Warning'),
				msg    : dgettext('plugin_files', response.message),
				icon   : Zarafa.common.dialogs.MessageBox.WARNING,
				buttons: Zarafa.common.dialogs.MessageBox.OK
			});
		} else {
			var downloadFrame = Ext.getBody().createChild({
				tag: 'iframe',
				cls: 'x-hidden'
			});

			var url = document.URL;
			var link = url.substring(0, url.lastIndexOf('/') + 1);

			link += "index.php?sessionid=" + container.getUser().getSessionId() + "&load=custom&name=download_vcf";
			link = Ext.urlAppend(link, "token=" + encodeURIComponent(response.download_token));
			link = Ext.urlAppend(link, "filename=" + encodeURIComponent(response.filename));

			downloadFrame.dom.contentWindow.location = link;
		}
	}
});

Ext.reg('contactimporter.hierarchycontextmenu', Zarafa.plugins.contactimporter.ui.ContextMenu);
