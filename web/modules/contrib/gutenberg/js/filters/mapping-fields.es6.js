(async (wp, Drupal) => {
  const { blockEditor, components, compose, hooks } = wp;
  const { addFilter } = hooks;
  const { createHigherOrderComponent } = compose;
  const { Card, CardBody, CardHeader, PanelBody } = components;
  const { InspectorControls } = blockEditor;

  function hasMappingFields(attributes) {
    return (
      attributes &&
      attributes.mappingFields &&
      Array.isArray(attributes.mappingFields)
    );
  }

  const withInspectorControl = createHigherOrderComponent(
    BlockEdit => props => {
      const { isSelected, attributes } = props;
      const hasMapping = hasMappingFields(attributes);
      if (hasMapping && isSelected) {
        return [
          <BlockEdit {...props} />,
          <InspectorControls>
            {!attributes.lockViewMode && (
              <PanelBody title={Drupal.t('Field mapping')} initialOpen>
                {attributes.mappingFields.map(field => {
                  let content;
                  const property = field.property || 'value';
                  if (field.attribute) {
                    content = Drupal.t(
                      'The block attribute <strong>@attribute</strong> is mapped to the <strong>@field[@property]</strong> field.',
                      {
                        '@attribute': field.attribute,
                        '@field': field.field,
                        '@property': property,
                      },
                    );
                  } else {
                    content = Drupal.t(
                      'The block content is mapped to the <strong>@field[@property]</strong> field.',
                      {
                        '@field': field.field,
                        '@property': property,
                      },
                    );
                  }
                  return (
                    <Card>
                      {field.label && (
                        <CardHeader>
                          <strong>{field.label}</strong>
                        </CardHeader>
                      )}
                      <CardBody>
                        <div
                          className="mapping-fields-summary"
                          // eslint-disable-next-line react/no-danger
                          dangerouslySetInnerHTML={{ __html: content }}
                        />
                      </CardBody>
                    </Card>
                  );
                })}
              </PanelBody>
            )}
          </InspectorControls>,
        ];
      }

      return <BlockEdit {...props} />;
    },
    'withInspectorControl',
  );

  addFilter(
    'blocks.registerBlockType',
    'drupalgutenberg/mapping-fields-attributes',
    settings => {
      settings.attributes = Object.assign(settings.attributes, {
        mappingFields: {
          type: 'array',
        },
      });

      return settings;
    },
  );

  addFilter(
    'editor.BlockEdit',
    'core/editor/mapping-fields-attributes/with-inspector-control',
    withInspectorControl,
  );
})(wp, Drupal);
