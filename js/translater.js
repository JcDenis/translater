/*global $, dotclear */
'use strict';
$(function () {
  $('#module-translations-form .checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#module-translations-form td input[type=checkbox]', '#module-translations-form #do-action');
  });
  $('#module-translations-form td input[type=checkbox]').enableShiftClick();
  dotclear.condSubmit('#module-translations-form td input[type=checkbox]', '#module-translations-form #do-action');

  $('#module-backups-form .checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#module-backups-form td input[type=checkbox]', '#module-backups-form #do-action');
  });
  $('#module-backups-form td input[type=checkbox]').enableShiftClick();
  dotclear.condSubmit('#module-backups-form td input[type=checkbox]', '#module-backups-form #do-action');

  $('#lang-edit-form .checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#lang-edit-form td input[type=checkbox]', undefined);
  });
  $('#lang-edit-form td input[type=checkbox]').enableShiftClick();

  const dctranslater = dotclear.getData('translater');
  $('.translaterline').each(function(){

      var line = this;
      var msgfile = $(line).children('.translatermsgfile');
      var msgstr = $(line).children('.translatermsgstr');
      var target = $(line).children('.translatertarget');
      var img = '<img src="'+dctranslater.image_field+'" alt="" />';
      var tog = '<img src="'+dctranslater.image_toggle+'" alt="" />';

      $('.strlist').hide();

      $(msgstr).children('.subtranslater').each(function(){
        var img_str = $('<a class="togglelist" title="detail">'+tog+'</a>').css('cursor','pointer');
        $(this).children('strong').each(function(){
          var txt = $(this).text();
          var img_add = $('<a class="addfield" title="'+dctranslater.title_add_detail+'">'+img+'</a>').css('cursor','pointer');
          $(this).prepend(' ').prepend(img_add);
          $(img_add).click(function(){$(target).children(':text').val(txt)});

          $(this).append(' ').append(img_str);
          var strlist=$(this).siblings('.strlist');
          $(strlist).click(function(){$(strlist).toggle();});
          $(img_str).click(function(){$(strlist).toggle();});
        });
      });

      $(msgfile).children('.subtranslater').each(function(){
      var img_file = $('<a class="togglelist" title="'+dctranslater.title_add_detail+'">'+tog+'</a>').css('cursor','pointer');
      $(this).children('strong').each(function(){
        $(this).append(' ').append(img_file);
        var strlist=$(this).siblings('.strlist');
        $(strlist).click(function(){$(strlist).toggle();});
        $(img_file).click(function(){$(strlist).toggle();});
      });
    });
    });
});