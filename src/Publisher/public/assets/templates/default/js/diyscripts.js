/* [ START ] MAPPING PAGE FUNCTION (enhanced, robust) */

function setAjaxSelectionBox(object, id, target_id, url, method, onError) {
  var httpMethod = method || 'POST';
  var qtarget = null;
  var idsplit = String(id || '').split('__node__');
  var $inputSource = jQuery('input#qmod-' + (idsplit[0] || '') + '.' + (idsplit[2] || ''));
  var infoClass = $inputSource.attr('class') || '';

  var roleNode = 'rolePages';
  var prefixNode = { 'module': 'module', 'field_name': 'field_name', 'field_value': 'field_value' };
  if (roleNode) {
    prefixNode = {
      'module': roleNode + '[module]',
      'field_name': roleNode + '[field_name]',
      'field_value': roleNode + '[field_value]'
    };
  }

  jQuery.ajax({
    type: httpMethod,
    url: url,
    data: object.serialize ? object.serialize() : {},
    success: function (d) {
      var $sourcebox = jQuery('select#' + id);
      qtarget = $sourcebox.val();

      var $targetSel = jQuery('select#' + target_id);
      var targetClassAttr = $targetSel.attr('class') || '';

      if (targetClassAttr.indexOf('field_name') !== -1) {
        var infoKey = String(infoClass || '').split(' ').shift() || '';
        var safeInfo = infoKey.split('-').join('.');
        jQuery('input#qmod-' + (idsplit[0] || '') + '.' + infoClass).attr({ 'name': prefixNode.module + '[' + safeInfo + ']' });
        $targetSel.attr({ 'name': prefixNode.field_name + '[' + safeInfo + '][' + (idsplit[0] || '') + '][]' });
      }

      if (targetClassAttr.indexOf('field_value') !== -1) {
        var targetClass = targetClassAttr.split('__');
        var ic = infoClass;
        if (!ic && targetClass.length > 1) ic = targetClass[1].split('-').join('.');
        var safeInfo2 = String(ic || '').split('-').join('.');
        $targetSel.attr({ 'name': prefixNode.field_value + '[' + safeInfo2 + '][' + (idsplit[0] || '') + '][' + qtarget + '][]' });
      }

      loader(target_id, 'show');
      updateSelectChosen('select#' + target_id, true, '');

      var list = [];
      try { list = (typeof d === 'string') ? JSON.parse(d) : d; } catch (e) { list = []; }

      jQuery.each(list, function (index, item) {
        if (item !== '') {
          var optValue;
          if (typeof item === 'string') {
            if (typeof item.replaceAll === 'function') {
              if (item.indexOf('_') !== -1) optValue = ucwords(item.replaceAll('_', ' '));
              else if (item.indexOf('.') !== -1) optValue = ucwords(item.replaceAll('.', ' '));
              else optValue = ucwords(item);
            } else {
              if (item.indexOf('_') !== -1) optValue = ucwords(item.split('_').join(' '));
              else if (item.indexOf('.') !== -1) optValue = ucwords(item.split('.').join(' '));
              else optValue = ucwords(item);
            }
          } else {
            optValue = item;
          }
          jQuery('<option>', { value: String(item), text: String(optValue) }).appendTo('select#' + target_id);
        }
      });

      updateSelectChosen('select#' + target_id, false, '');
    },
    error: function () {
      if (onError) {
        try { alert(onError); } catch (e) {}
      }
    },
    complete: function () {
      loader(target_id, 'fadeOut');
    }
  });
}

function mappingPageTableFieldname(id, target_id, url, target_opt, nodebtn, nodemodel, method, onError) {
  var httpMethod = method || 'POST';
  var node_add = 'role-add-' + target_id;
  var $node_btn = jQuery('#' + nodebtn);
  var $firstRemove = jQuery('span#remove-row' + target_id);
  var nodestring = '__node__';

  $node_btn.hide();
  if (jQuery('#' + id).is(':checked')) {
    $node_btn.fadeIn(1800);
  }

  var classInfo = id + nodestring + nodemodel;
  jQuery('#' + id + '.' + classInfo).off('change.mapping').on('change.mapping', function () {
    if (jQuery(this).is(':checked')) {
      $node_btn.fadeIn(1800);
      var infoID = classInfo;
      setAjaxSelectionBox(jQuery(this), infoID, target_id, url, httpMethod, onError);
    } else {
      var idsplit = jQuery(this).attr('class').split(nodestring);
      jQuery('input#qmod-' + (idsplit[0] || '') + '.' + (idsplit[2] || '')).removeAttr('name');

      loader(target_id, 'show');
      loader(target_id, 'fadeOut');
      updateSelectChosen('select#' + target_id, true, '');

      if (target_opt != null) {
        loader(target_opt, 'show');
        loader(target_opt, 'fadeOut');
        updateSelectChosen('select#' + target_opt, true, '');
      }

      $firstRemove.fadeOut(1000);
      $node_btn.fadeOut(1800);
      jQuery('#reset' + nodebtn).fadeOut(500);
      jQuery('.' + node_add).each(function () {
        var $el = jQuery(this);
        if ($el.chosen) $el.chosen('destroy');
        $el.fadeOut(500, function () { $el.remove(); });
      });
    }
  });
}

function rowButtonRemovalMapRoles(id) {
  jQuery('span#remove-row' + id).off('click.remove').on('click.remove', function () {
    jQuery('tr#row-box-' + id).fadeOut(300, function () { jQuery(this).remove(); });
  });
}

function mappingPageFieldnameValues(id, target_id, url, method, onError) {
  var httpMethod = method || 'POST';
  var $firstRemove = jQuery('span#remove-row' + id);
  jQuery('#' + id).off('change.values').on('change.values', function () {
    if (jQuery(this).val() !== '') {
      setAjaxSelectionBox(jQuery(this), id, target_id, url, httpMethod, onError);
      $firstRemove.fadeIn(1000);
    } else {
      loader(target_id, 'show');
      loader(target_id, 'fadeOut');
      updateSelectChosen('select#' + target_id, true, '');
      $firstRemove.fadeOut(1000);
    }
  });
}

function firstResetRowButton(id, target_id, second_target, url, method, onError, withAction) {
  var httpMethod = method || 'POST';
  var doAction = (typeof withAction === 'undefined') ? true : !!withAction;
  var $firstRemove = jQuery('span#remove-row' + target_id);

  if (doAction) {
    $firstRemove.off('click.reset').on('click.reset', function () {
      setAjaxSelectionBox(jQuery('#' + id), id, target_id, url.replace('field_name', 'table_name'), httpMethod, onError);
      mappingPageFieldnameValues(target_id, second_target, url, httpMethod, onError);
      updateSelectChosen('select#' + second_target, true, '');
      jQuery(this).fadeOut(1000);
    });
  } else {
    setAjaxSelectionBox(jQuery('#' + id), id, target_id, url.replace('field_name', 'table_name'), httpMethod, onError);
    mappingPageFieldnameValues(target_id, second_target, url, httpMethod, onError);
    updateSelectChosen('select#' + second_target, true, '');
    $firstRemove.fadeOut();
  }
}

function mappingPageButtonManipulation(node_btn, id, target_id, second_target, url, method, onError) {
  var httpMethod = method || 'POST';
  var node_add = 'role-add-' + target_id;
  var $baserowbox = jQuery('tr#row-box-' + target_id);
  var $tablesource = $baserowbox.parent('tbody').parent('table');

  var $firstRemove = jQuery('span#remove-row' + target_id);

  jQuery('#reset' + node_btn).hide();
  jQuery('#plusn' + node_btn).off('click.plus').on('click.plus', function () {
    jQuery('span.inputloader').removeAttr('style').hide();

    var styleVal = $firstRemove.attr('style') || '';
    if (styleVal.trim()) {
      $firstRemove.attr({ 'style': '' }).fadeIn();
    }

    var random_target_id = target_id + canvastack_random();
    var random_second_target = second_target + canvastack_random();
    var node_row = 'remove-row' + random_target_id;
    var nextcloneid = 'row-box-' + random_target_id;
    var $clonerowbox = $baserowbox.clone().attr({ 'id': nextcloneid, 'class': $baserowbox.attr('class') + ' ' + node_add });

    $clonerowbox.find('td').each(function () {
      var $td = jQuery(this);
      var tdClass = $td.attr('class') || '';

      if (tdClass.indexOf('field-name-box') !== -1) {
        $td.children('div.chosen-container').remove();
        var $sel = $td.children('select');
        $sel.attr({ 'id': random_target_id }).prop('selectedIndex', -1);
        if ($sel.chosen) $sel.chosen();
      }

      if (tdClass.indexOf('field-value-box') !== -1) {
        $td.children('div.chosen-container').remove();
        var $sel2 = $td.children('select');
        $sel2.attr({ 'id': random_second_target, 'name': '' }).find('option').remove().end();
        if ($sel2.chosen) $sel2.chosen();
        $td.children('span#remove-row' + target_id)
          .removeAttr('id').attr({ 'id': node_row })
          .find('.fa').attr({ 'class': 'fa fa-minus-circle danger' });
      }
    });

    $clonerowbox.appendTo($tablesource);
    mappingPageFieldnameValues(random_target_id, random_second_target, url, httpMethod, onError);

    if ($clonerowbox.length >= 1) {
      $firstRemove.fadeIn();
      jQuery('#reset' + node_btn).fadeIn();
    } else {
      jQuery('#reset' + node_btn).fadeOut();
    }

    jQuery('span#' + node_row).off('click.removeRow').on('click.removeRow', function () {
      jQuery('tr#row-box-' + random_target_id).fadeOut(300, function () { jQuery(this).remove(); });
    });
  });

  $tablesource.each(function () {
    var tr = jQuery(this).children('tbody').children('tr').length;
    if (tr > 1) {
      jQuery('#reset' + node_btn).fadeIn();
    }
  });

  jQuery('#reset' + node_btn).off('click.resetAll').on('click.resetAll', function () {
    jQuery('.' + node_add).each(function () {
      var $el = jQuery(this);
      if ($el.chosen) $el.chosen('destroy');
      $el.fadeOut(500, function () { $el.remove(); });
    });
    jQuery('#reset' + node_btn).fadeOut(500);
    firstResetRowButton(id, target_id, second_target, url, httpMethod, onError, false);
  });

  firstResetRowButton(id, target_id, second_target, url, httpMethod, onError);
}
/* [ CLOSED ] MAPPING PAGE FUNCTION */
