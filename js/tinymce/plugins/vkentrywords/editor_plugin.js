/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function() {

	tinymce.create('tinymce.plugins.VkEntryWords', {
		init : function(ed, url) {
			ed.addButton('vkentrywords', { 
				title : 'Ingångsord som länkar in till artikel', 
				image : url + '/img/icon.png', 
				cmd : 'VK_Entrywords'
				/*onclick : function() { 
					ed.focus(); 
					ed.selection.setContent('<span class="teaser-entryword">' + ed.selection.getContent() + '</span>'); 
				} */
			});
			
			// Register the command so that it can be invoked by using the button
			ed.addCommand('VK_Entrywords', function() {
				ed.focus(); 
				var node = ed.selection.getNode();
				if(ed.dom.hasClass(node, 'teaser-entryword')) {
					var newElement = document.createTextNode(node.innerHTML);
					node.parentNode.replaceChild(newElement, node);
				}else{
					ed.selection.setContent('<span class="teaser-entryword">' + ed.selection.getContent() + '</span>');
				}
			});
		},
		getInfo : function() {
			return {
				longname : 'VK Entry Words',
				author : 'Rob',
				authorurl : 'http://www.vk.se',
				infourl : 'http://www.vk.se',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('vkentrywords', tinymce.plugins.VkEntryWords);
})();