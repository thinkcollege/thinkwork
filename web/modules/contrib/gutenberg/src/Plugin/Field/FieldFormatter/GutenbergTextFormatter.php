<?php

namespace Drupal\gutenberg\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;

/**
 * Plugin implementation of the 'gutenberg_text' formatter.
 *
 * @FieldFormatter(
 *   id = "gutenberg_text",
 *   label = @Translation("Gutenberg"),
 *   description = @Translation("Render text through a Gutenberg text format."),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class GutenbergTextFormatter extends TextDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => 'gutenberg',
      'content_only' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    $formats = filter_formats($user);

    $options = [];
    foreach ($formats as $format) {
      $dependencies = $format->getDependencies();
      if (isset($dependencies['module']) &&
        in_array('gutenberg', $dependencies['module'], TRUE)) {
        // Include formats which rely on the gutenberg module.
        $options[$format->id()] = $format->label();
      }
    }

    $element = parent::settingsForm($form, $form_state);

    $element['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Gutenberg format'),
      '#options' => $options,
      '#default_value' => $this->getSetting('format'),
      '#required' => TRUE,
    ];

    $element['content_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Content only'),
      '#description' => $this->t('Render the field content without the field wrapper.'),
      '#default_value' => $this->getSetting('content_only'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Gutenberg format: @format.', ['@format' => $this->getSetting('format')]);

    if ($this->getSetting('content_only')) {
      $summary[] = $this->t('Render without field wrappers.');
    }
    else {
      $summary[] = $this->t('Render with field wrappers.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'processed_text',
        '#text' => $item->value,
        // Always render through the gutenberg format.
        '#format' => $this->getSetting('format'),
        '#langcode' => $item->getLangcode(),
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);

    if (isset($elements['#theme']) && $this->getSetting('content_only')) {
      // Render using the Gutenberg text field theme.
      $elements['#theme'] = 'field_gutenberg_text';
    }

    return $elements;
  }

}
