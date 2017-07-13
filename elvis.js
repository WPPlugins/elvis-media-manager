(
	function()
	{
		tinymce.PluginManager.requireLangPack('elvis');

		tinymce.create("tinymce.plugins.Elvis" ,
		{
			init:function(ed, url) {
				ed.addCommand('openElvisBrowser', function () {
					var windowSettings = {
						file : url + '/browse.html',
						width : -200,
						height : -200,
						inline : 1
					};
					
					if (typeof window.innerWidth != 'undefined') {
						// more standards compliant browsers (mozilla/netscape/opera/IE7)
						windowSettings.width += window.innerWidth,
						windowSettings.height += window.innerHeight
					}
					else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0) {
						// IE6 in standards compliant mode
						windowSettings.width += document.documentElement.clientWidth,
						windowSettings.height += document.documentElement.clientHeight
					} else {
						// older versions of IE
						windowSettings.width += document.getElementsByTagName('body')[0].clientWidth,
						windowSettings.height += document.getElementsByTagName('body')[0].clientHeight
					}
					
					ed.windowManager.open(windowSettings, {
						plugin_url : url, // Plugin absolute URL
						editor : tinymce.activeEditor // Custom argument
					});
				});

				ed.addButton('elvisInsertMedia', {
					title : 'elvis.insertMedia',
					cmd : 'openElvisBrowser',
					image : url + '/img/elvis.png'
				});
			},
			createControl : function(n, cm){
				return null;
			},
			getInfo : function(){
				return {
					longname: 'Elvis Wordpress plugin',
					author: 'woodwing.com',
					authorurl: 'http://www.woodwing.com',
					infourl: 'http://www.elvisdam.com',
					version: "1.0"
				};
			}
		});

		tinymce.PluginManager.add("elvis",tinymce.plugins.Elvis)
	}
)();