/**
 * ResponseHandler.js zarafa contact im/exporter
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
 * ResponseHandler
 *
 * This class handles all responses from the php backend
 */
Ext.namespace('Zarafa.plugins.contactimporter.data');

/**
 * @class Zarafa.plugins.contactimporter.data.ResponseHandler
 * @extends Zarafa.plugins.contactimporter.data.AbstractResponseHandler
 *
 * Calendar specific response handler.
 */
Zarafa.plugins.contactimporter.data.ResponseHandler = Ext.extend(Zarafa.core.data.AbstractResponseHandler, {
	/**
	 * @cfg {Function} successCallback The function which
	 * will be called after success request.
	 */
	successCallback : null,
	
	/**
	 * Call the successCallback callback function.
	 * @param {Object} response Object contained the response data.
	 */
	doExport : function(response) {
		this.successCallback(response);
	},
	
	/**
	 * Call the successCallback callback function.
	 * @param {Object} response Object contained the response data.
	 */
	doList : function(response) {
		this.successCallback(response);
	},
	
	/**
	 * Call the successCallback callback function.
	 * @param {Object} response Object contained the response data.
	 */
	doImport : function(response) {
		this.successCallback(response);
	},
	
	/**
	 * Call the successCallback callback function.
	 * @param {Object} response Object contained the response data.
	 */
	doAttachmentpath : function(response) {
		this.successCallback(response);
	},
	
	/**
	 * Call the successCallback callback function.
	 * @param {Object} response Object contained the response data.
	 */
	doAddattachment : function(response) {
		this.successCallback(response);
	},
	
	/**
	 * In case exception happened on server, server will return
	 * exception response with the code of exception.
	 * @param {Object} response Object contained the response data.
	 */
	doError: function(response)	{
		alert("error response code: " + response.error.info.code);
	}
});

Ext.reg('contactimporter.contactresponsehandler', Zarafa.plugins.contactimporter.data.ResponseHandler);