/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/'use strict';

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

(function (Drupal, DrupalGutenberg, drupalSettings, wp, $) {

  function addToTree(obj, keys, def) {
    for (var i = 0, length = keys.length; i < length; ++i) {
      obj = obj[keys[i]] = i == length - 1 ? def : obj[keys[i]] || {};
    }
  };

  function serializedToNested(elements) {
    var regex = /\[([a-z_]*)\]/gm;

    var tree = {};

    elements.forEach(function (element) {
      var m = void 0;
      var nested = [];

      while ((m = regex.exec(element.name)) !== null) {
        if (m.index === regex.lastIndex) {
          regex.lastIndex++;
        }

        m.forEach(function (match, groupIndex) {
          groupIndex === 1 && match !== 'override' && nested.push(match);
        });
      }

      addToTree(tree, nested, element.value);
    });

    return tree;
  }

  function getIdFromEntityFormElement(str) {
    var regex = /.+\s\(([^\)]+)\)/gi;
    var matches = regex.exec(str);
    return matches ? matches[1] : null;
  }

  Drupal.behaviors.gutenbergBlockSettings = {
    attach: function attach(form) {
      if (form.elements) {
        var btn = Array.from(form.elements).filter(function (el) {
          return el.name === 'op';
        })[0];

        btn.onclick = function (ev) {
          var elements = document.querySelectorAll('#' + form.id + ' [data-autocomplete-path]');
          elements.forEach(function (el) {
            el.value = getIdFromEntityFormElement(el.value);
          });

          var values = $(form).serializeArray();
          values = values.filter(function (el) {
            return el.name.match(/^settings/i);
          });

          $(form).find('input[type=checkbox]').map(function () {
            var _this = this;

            if (this.checked) {
              values = values.filter(function (v) {
                return v.name !== _this.name;
              });
              values = values.concat({ name: this.name, value: 1 });
            } else {
              values = values.concat({ name: this.name, value: 0 });
            }
          });

          $('#drupal-modal').dialog('close');
          var data = wp.data;
          var select = data.select,
              dispatch = data.dispatch;


          var block = select('core/block-editor').getSelectedBlock();
          var clientId = select('core/block-editor').getSelectedBlockClientId();
          var attrs = _extends({}, block.attributes, { settings: serializedToNested(values) });
          dispatch('core/block-editor').updateBlockAttributes(clientId, attrs);

          return false;
        };
      }
    }
  };
})(Drupal, DrupalGutenberg, drupalSettings, wp, jQuery);