/* -- BEGIN LICENSE BLOCK ----------------------------------
 *
 * This file is part of translater, a plugin for Dotclear 2.
 * 
 * Copyright (c) 2009-2013 Jean-Christian Denis and contributors
 * contact@jcdenis.fr
 * 
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * -- END LICENSE BLOCK ------------------------------------*/

;if(window.jQuery) (function($){
	$.fn.translater = function(options){
		var opts = $.extend({}, $.fn.translater.defaults, options);
		return this.each(function(){

			var img = '<img src="index.php?pf=translater/inc/img/field.png" alt="" />';
			var tog = '<img src="index.php?pf=translater/inc/img/toggle.png" alt="" />';
			var line = this;
			var msgid = $(line).children('.translatermsgid');
			var msgfile = $(line).children('.translatermsgfile');
			var msgstr = $(line).children('.translatermsgstr');
			var target = $(line).children('.translatertarget');

			$('.strlist').hide();

			var img_go = $('<a class="addfield" title="'+opts.title_go+'">'+img+'</a>').css('cursor','pointer');
			$(msgid).prepend(' ').prepend(img_go);
			$(img_go).click(function(){
				var txt = $(msgid).text();
				$(img_go).css('cursor','wait');
				$.get(opts.url,{f:opts.func,langFrom:opts.from,langTo:opts.to,langTool:opts.tool,langStr:txt},function(data){
					data=$(data);
					if(data.find('rsp').attr('status')=='ok' && $(data).find('proposal').attr('str_to')){
						var resp = $(data).find('proposal').attr('str_to');
						if (confirm(opts.title+'\n'+resp)){
							addText(target,resp);
							$(img_go).css('cursor','pointer');
						}
						else{
							$(img_go).css('cursor','pointer');
						}
					}else{
						alert(opts.failed);
						$(img_go).css('cursor','pointer');
					}
				});
			});

			$(msgstr).children('.subtranslatermsgstr').each(function(){
				var img_str = $('<a class="togglelist" title="detail">'+tog+'</a>').css('cursor','pointer');
				$(this).children('strong').each(function(){
					var txt = $(this).text();
					var img_add = $('<a class="addfield" title="'+opts.title_add+'">'+img+'</a>').css('cursor','pointer');
					$(this).prepend(' ').prepend(img_add);
					$(img_add).click(function(){addText(target,txt);});

					$(this).append(' ').append(img_str);
					var strlist=$(this).siblings('.strlist');
					$(strlist).click(function(){$(strlist).toggle();});
					$(img_str).click(function(){$(strlist).toggle();});
				});
			});

			var img_file = $('<a class="togglelist" title="detail">'+tog+'</a>').css('cursor','pointer');
			$(msgfile).children('strong').each(function(){
				$(this).append(' ').append(img_file);
				var strlist=$(this).siblings('.strlist');
				$(strlist).click(function(){$(strlist).toggle();});
				$(img_file).click(function(){$(strlist).toggle();});
			});
		});
	};
	function addText(target,text){
		$(target).children(':text').val(text);
	}

	$.fn.translater.defaults = {
		url: '',
		func: '',
		from: 'en',
		to: 'fr',
		tool: 'google',
		failed: 'Failed to translate this',
		title: 'Copy translation to field:',
		title_go: 'Find translation',
		title_add: 'Add this translation'
	};
})(jQuery);