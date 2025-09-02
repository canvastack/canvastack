/* Utility & AJAX helpers (safe/robust) */

function ucwords(str, force) {
  var s = (force ? String(str || '').toLowerCase() : String(str || ''));
  return s.replace(/\b([a-zA-Z])/g, function (m) { return m.toUpperCase(); });
}

function canvastack_random(length) {
  var len = typeof length === 'number' ? length : 8;
  var result = '';
  var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  var charactersLength = characters.length;
  for (var i = 0; i < len; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }
  return result;
}

function canvastack_array_to_object(array) {
  try {
    return Object.assign({}, array);
  } catch (e) {
    var obj = {};
    if (Array.isArray(array)) {
      for (var i = 0; i < array.length; i++) obj[i] = array[i];
    }
    return obj;
  }
}

function updateSelectChosen(target, reset, optstring) {
  var chosenTarget = jQuery(target);
  var doReset = (typeof reset === 'undefined') ? true : !!reset;
  var opt = (typeof optstring === 'undefined') ? 'Select an Option' : optstring;
  if (doReset) chosenTarget.find('option').remove().end();
  if (opt !== false) {
    chosenTarget.append(jQuery('<option>', { value: '', text: String(opt) }));
  }
  if (chosenTarget.trigger) chosenTarget.trigger('chosen:updated');
}

function loader(target_id, view) {
  var _view = view || 'hide';
  var _loaderTarget = '#' + target_id;
  var _loaderID = 'cdyInpLdr' + target_id;

  if (_view === 'remove') {
    jQuery('span.inputloader').remove();
  } else if (_view === 'fadeOut') {
    jQuery('span.inputloader').fadeOut(1800, function () { jQuery(this).remove(); });
  } else if (_view === 'show') {
    jQuery(_loaderTarget).before('<span class="inputloader loader show" id="' + _loaderID + '"></span>');
  } else {
    // default: hide or unknown → noop to avoid duplicate loaders
  }
}

// Normalize label for display
function canvastack_normalize_label(label) {
  var s = String(label || '');
  if (typeof s.replaceAll === 'function') {
    if (s.indexOf('_') !== -1) return ucwords(s.replaceAll('_', ' '));
    if (s.indexOf('.') !== -1) return ucwords(s.replaceAll('.', ' '));
    return ucwords(s);
  } else {
    if (s.indexOf('_') !== -1) return ucwords(s.split('_').join(' '));
    if (s.indexOf('.') !== -1) return ucwords(s.split('.').join(' '));
    return ucwords(s);
  }
}

function ajaxSelectionProcess(object, id, target_id, url, data, method, onError) {
  var methodType = method || 'POST';
  var chosenTargetSel = 'select#' + target_id;
  var dataInfo = {};

  try {
    if (typeof data === 'string') {
      dataInfo = JSON.parse(data);
    } else if (typeof data === 'object' && data !== null) {
      dataInfo = data;
    }
  } catch (e) {
    dataInfo = {};
  }

  var qs = [];
  if (typeof dataInfo.labels !== 'undefined') qs.push('l=' + encodeURIComponent(dataInfo.labels));
  if (typeof dataInfo.values !== 'undefined') qs.push('v=' + encodeURIComponent(dataInfo.values));
  if (typeof dataInfo.selected !== 'undefined') qs.push('s=' + encodeURIComponent(dataInfo.selected));
  if (typeof dataInfo.query !== 'undefined') qs.push(canvastack_random() + '=' + encodeURIComponent(dataInfo.query));

  var urls = url + (qs.length ? (url.indexOf('?') === -1 ? '?' : '&') + qs.join('&') : '');

  var selected = null;

  jQuery.ajax({
    type: methodType,
    url: urls,
    data: object.serialize ? object.serialize() : {},
    success: function (d) {
      var result = {};
      try { result = (typeof d === 'string') ? JSON.parse(d) : d; } catch (e) { result = {}; }
      selected = result.selected;

      loader(target_id, 'show');
      updateSelectChosen(chosenTargetSel, true, '');

      if (result && result.data) {
        jQuery.each(result.data, function (value, label) {
          if (value !== '') {
            var optionLabel = canvastack_normalize_label(label);
            jQuery('<option>', {
              value: String(value),
              text: optionLabel,
              selected: (selected === value)
            }).appendTo(chosenTargetSel);
          }
        });
      }
      updateSelectChosen(chosenTargetSel, false, false);
    },
    error: function (xhr) {
      if (onError) {
        try { alert(onError); } catch (e) {}
      }
      if (window.console) {
        console.error('ajaxSelectionProcess error', xhr && xhr.responseText);
      }
    },
    complete: function () {
      loader(target_id, 'fadeOut');
    }
  });
}

function ajaxSelectionBox(id, target_id, url, data, method, onError) {
  var $object = jQuery('select#' + id);
  if ($object.length && $object.val() !== '') {
    ajaxSelectionProcess($object, id, target_id, url, data, method, onError);
  }
  // Prevent multiple bindings
  $object.off('change.canvastack').on('change.canvastack', function () {
    ajaxSelectionProcess($object, id, target_id, url, data, method, onError);
  });
}
