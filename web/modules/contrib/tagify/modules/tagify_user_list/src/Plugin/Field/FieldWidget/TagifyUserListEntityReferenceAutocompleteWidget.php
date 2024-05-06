<?php

namespace Drupal\tagify_user_list\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\tagify\Plugin\Field\FieldWidget\TagifyEntityReferenceAutocompleteWidget;

/**
 * Plugin implementation Tagify user list entity reference autocomplete widget.
 *
 * @FieldWidget(
 *   id = "tagify_user_list_entity_reference_autocomplete_widget",
 *   label = @Translation("Tagify User List"),
 *   description = @Translation("An autocomplete text field with tagify support for user entity."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class TagifyUserListEntityReferenceAutocompleteWidget extends TagifyEntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'match_operator' => 'STARTS_WITH',
      'match_limit' => 10,
      'suggestions_dropdown' => 1,
      'placeholder' => '',
      'show_info_label' => 1,
      'info_label' => '[user:mail]',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['match_operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => $this->getMatchOperatorOptions(),
      '#description' => $this->t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
    ];
    $element['match_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results'),
      '#default_value' => $this->getSetting('match_limit'),
      '#min' => 0,
      '#description' => $this->t('The number of suggestions that will be listed. Use <em>0</em> to remove the limit.'),
    ];
    $element['suggestions_dropdown'] = [
      '#type' => 'radios',
      '#title' => $this->t('Suggestions dropdown'),
      '#default_value' => $this->getSetting('suggestions_dropdown'),
      '#options' => $this->getSuggestionsDropdownOptions(),
      '#description' => $this->t('Select the method used to show suggestions dropdown.'),
    ];
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['show_info_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include info label'),
      '#default_value' => $this->getSetting('show_info_label'),
      '#description' => $this->t('Show extra information below the entity label.'),
    ];
    $element['info_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Info label content'),
      '#default_value' => $this->getSetting('info_label'),
      '#description' => $this->t('The information that will be shown. You can use tokens to make this dynamic.'),
      '#states' => [
        'visible' => [
          sprintf(':input[name="fields[%s][settings_edit_form][settings][show_info_label]"]', $this->fieldDefinition->getName()) => ['checked' => TRUE],
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $token_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
      // Convert 'taxonomy_term' target type into 'term' in order to make it
      // work as a token type.
      if ($token_type === 'taxonomy_term') {
        $token_type = 'term';
      }
      $element['info_label_tokens'] = [
        '#type' => 'item',
        '#theme' => 'token_tree_link',
        '#token_types' => [$token_type],
        '#show_restricted' => TRUE,
        '#global_types' => FALSE,
        '#recursion_limit' => 3,
        '#states' => [
          'visible' => [
            sprintf(':input[name="fields[%s][settings_edit_form][settings][show_info_label]"]', $this->fieldDefinition->getName()) => ['checked' => TRUE],
          ],
        ],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $operators = $this->getMatchOperatorOptions();
    $summary[] = $this->t('Autocomplete matching: @match_operator', ['@match_operator' => $operators[$this->getSetting('match_operator')]]);
    $size = $this->getSetting('match_limit') ?: $this->t('unlimited');
    $summary[] = $this->t('Autocomplete suggestion list size: @size', ['@size' => $size]);
    $suggestions_dropdown = $this->getSuggestionsDropdownOptions();
    $summary[] = $this->t('Autocomplete suggestions dropdown: @suggestions_dropdown', ['@suggestions_dropdown' => $suggestions_dropdown[$this->getSetting('suggestions_dropdown')]]);
    if ($this->getSetting('show_info_label')) {
      $summary[] = $this->t('Show extra information below the entity label');
    }
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = $this->t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Append the match operation to the selection settings.
    $selection_settings = $this->getFieldSetting('handler_settings') + [
      'match_operator' => $this->getSetting('match_operator'),
      'match_limit' => $this->getSetting('match_limit'),
      'suggestions_dropdown' => $this->getSetting('suggestions_dropdown'),
      'placeholder' => $this->getSetting('placeholder'),
    ];
    if ($this->getSetting('show_info_label')) {
      $selection_settings['info_label'] = $this->getSetting('info_label');
    }
    $target_type = $this->getFieldSetting('target_type');
    $selection_handler = $this->getFieldSetting('handler');
    $data = serialize($selection_settings) . $target_type . $selection_handler;
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = $this->keyValueFactory->get('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    // User field definition doesn't have fieldStorage defined.
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
    // Handle field cardinality in the Tagify side.
    $limited = !$cardinality ? 'tagify--limited' : '';
    // Handle field autocreate option.
    $autocreate = $this->getSelectionHandlerSetting('auto_create') ? 'tagify--autocreate' : '';
    $tags_identifier = $items->getName();

    $element += [
      '#type' => 'entity_autocomplete_tagify_user_list',
      '#default_value' => $items->referencedEntities() ?? NULL,
      '#autocreate' => $this->getSelectionHandlerSetting('auto_create'),
      '#target_type' => $target_type,
      '#selection_handler' => $selection_handler,
      '#selection_settings_key' => $selection_settings_key,
      '#max_items' => $this->getSetting('match_limit'),
      '#suggestions_dropdown' => $this->getSetting('suggestions_dropdown'),
      '#attributes' => [
        'class' => [$limited, $autocreate, $tags_identifier],
      ],
      '#placeholder' => $this->getSetting('placeholder'),
      '#match_operator' => $this->getSetting('match_operator'),
      '#cardinality' => $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getCardinality(),
    ];

    if ($this->getSetting('show_info_label')) {
      $element['#info_label'] = $this->getSetting('info_label');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    // Don't allow to user entity reference fields.
    return $target_type === 'user';
  }

}
