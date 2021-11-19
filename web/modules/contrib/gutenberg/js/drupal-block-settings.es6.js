/**
 * @file
 * Gutenberg implementation of {@link Drupal.editors} API.
 */

/* eslint func-names: ["error", "never"] */
(function(Drupal, DrupalGutenberg, drupalSettings, wp, $) {

  function addToTree(obj, keys, def) {
    for (var i = 0, length = keys.length; i < length; ++i)
      obj = obj[keys[i]] = i == length - 1 ? def : obj[keys[i]] || {};
  };

  function serializedToNested(elements) {
    const regex = /\[([a-z_]*)\]/gm;

    let tree = {};

    elements.forEach(element => {
      let m;
      const nested = [];
      
      while ((m = regex.exec(element.name)) !== null) {
        if (m.index === regex.lastIndex) {
          regex.lastIndex++;
        }
        
        m.forEach((match, groupIndex) => {
          // @TODO: Excluding 'override' group but not fully aware of the implications.
          groupIndex === 1 && match !== 'override' && nested.push(match);
        });
      }

      addToTree(tree, nested, element.value);
    });

    return tree;
    // return Object.keys(tree).map((key) => [Number(key), tree[key]]);
  }

  function getIdFromEntityFormElement(str) {
    const regex = /.+\s\(([^\)]+)\)/gi;
    const matches = regex.exec(str);
    return matches ? matches[1] : null;
  }

  /**
   * @namespace
   */
   Drupal.behaviors.gutenbergBlockSettings = {
    attach(form) {
      if (form.elements) {
        const btn = Array.from(form.elements).filter(el => el.name === 'op')[0];

        btn.onclick = (ev) => {
          // Check for entity autocomplete fields to retrieve the entity id.
          const elements = document.querySelectorAll(`#${form.id} [data-autocomplete-path]`);
          elements.forEach(el => {
            el.value = getIdFromEntityFormElement(el.value);
          });

          let values = $(form).serializeArray();
          values = values.filter(el => el.name.match(/^settings/i));

          /* Because serializeArray() ignores unset checkboxes: */
          $(form).find(`input[type=checkbox]`).map(function() {
            // If checked, replace array item. If not checked concat to values array.
            if (this.checked) {
              values = values.filter(v => v.name !== this.name);
              values = values.concat({name: this.name, value: 1});
            }
            else {
              values = values.concat({name: this.name, value: 0});
            }
          });

          $('#drupal-modal').dialog('close');
          const { data } = wp;
          const { select, dispatch } = data;
          
          const block = select('core/block-editor').getSelectedBlock();
          const clientId = select('core/block-editor').getSelectedBlockClientId();
          const attrs = { ...block.attributes, settings: serializedToNested(values) };
          dispatch('core/block-editor').updateBlockAttributes(clientId, attrs);

          return false;
        }
      }
    }
   }

})(
  Drupal, DrupalGutenberg, drupalSettings, wp, jQuery,
);