<?php

namespace Drupal\glossify_taxonomy\Plugin\Filter;

use Drupal\glossify\GlossifyBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Filter to find and process found taxonomy terms in the fields value.
 *
 * @Filter(
 *   id = "glossify_taxonomy",
 *   title = @Translation("Glossify: Tooltips with taxonomy"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "glossify_taxonomy_case_sensitivity" = TRUE,
 *     "glossify_taxonomy_first_only" = TRUE,
 *     "glossify_taxonomy_type" = "tooltips",
 *     "glossify_taxonomy_tooltip_truncate" = FALSE,
 *     "glossify_taxonomy_vocabs" = NULL,
 *     "glossify_taxonomy_urlpattern" = "/taxonomy/term/[id]",
 *   },
 *   weight = -10
 * )
 */
class TaxonomyTooltip extends GlossifyBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $vocab_options = [];
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $vocab) {
      $vocab_options[$vocab->id()] = $vocab->get('name');
    }

    $form['glossify_taxonomy_case_sensitivity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive'),
      '#description' => $this->t('Whether or not the match is case sensitive.'),
      '#default_value' => $this->settings['glossify_taxonomy_case_sensitivity'],
    ];
    $form['glossify_taxonomy_first_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('First match only'),
      '#description' => $this->t('Match and link only the first occurance per field.'),
      '#default_value' => $this->settings['glossify_taxonomy_first_only'],
    ];
    $form['glossify_taxonomy_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#required' => TRUE,
      '#options' => [
        'tooltips' => $this->t('Tooltips'),
        'links' => $this->t('Links'),
        'tooltips_links' => $this->t('Tooltips and links'),
      ],
      '#description' => $this->t('How to show matches in content. Description as HTML5 tooltip (abbr element), link to description or both.'),
      '#default_value' => $this->settings['glossify_taxonomy_type'],
    ];
    $form['glossify_taxonomy_tooltip_truncate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Truncate tooltip'),
      '#description' => $this->t('Whether to truncate tooltip after 300 characters.'),
      '#default_value' => $this->settings['glossify_taxonomy_tooltip_truncate'],
      '#states' => [
        'visible' => [
          ':input[name="filters[glossify_taxonomy][settings][glossify_taxonomy_type]"]' => [
            ['value' => 'tooltips'],
            'or',
            ['value' => 'tooltips_links'],
          ],
        ],
      ],
    ];
    $form['glossify_taxonomy_vocabs'] = [
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#element_validate' => [
        [
          get_class($this),
          'validateTaxonomyVocabs',
        ],
      ],
      '#title' => $this->t('Taxonomy vocabularies'),
      '#description' => $this->t('Select the source taxonomy vocabularies you want to use term names from to link their term page.'),
      '#options' => $vocab_options,
      '#default_value' => explode(';', $this->settings['glossify_taxonomy_vocabs'] ?? ''),
    ];
    $form['glossify_taxonomy_urlpattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL pattern'),
      '#description' => $this->t('Url pattern, used for linking matched words. Accepts "[id]" as token. Example: "/taxonomy/term/[id]"'),
      '#default_value' => $this->settings['glossify_taxonomy_urlpattern'],
    ];

    return $form;
  }

  /**
   * Validation callback for glossify_taxonomy_vocabs.
   *
   * Make the field required if the filter is enabled.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateTaxonomyVocabs(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValues();
    // Make taxonomy_vocabs required if the filter is enabled.
    if (!empty($values['filters']['glossify_taxonomy']['status'])) {
      $field_values = array_filter($values['filters']['glossify_taxonomy']['settings']['glossify_taxonomy_vocabs']);
      if (empty($field_values)) {
        $element['#required'] = TRUE;
        $form_state->setError($element, t('%field is required.', ['%field' => $element['#title']]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $cacheTags = [];

    // Get vocabularies.
    $vocabs = explode(';', $this->settings['glossify_taxonomy_vocabs']);

    // Let other modules override $vocabs.
    \Drupal::moduleHandler()->alter('glossify_taxonomy_vocabs', $vocabs);

    if (count($vocabs)) {
      $terms = [];

      // Get taxonomy term data.
      $query = \Drupal::database()->select('taxonomy_term_field_data', 'tfd');
      $query->addfield('tfd', 'tid', 'id');
      $query->addfield('tfd', 'name');
      $query->addfield('tfd', 'name', 'name_norm');
      $query->addField('tfd', 'description__value', 'tip');
      $query->condition('tfd.vid', $vocabs, 'IN');
      $query->condition('tfd.status', 1);
      $query->condition('tfd.langcode', $langcode);
      $query->orderBy('name_norm', 'DESC');

      $results = $query->execute()->fetchAllAssoc('name_norm');
      // Build terms array.
      foreach ($results as $result) {
        // Make name_norm lowercase, it seems not possible in PDO query?
        if (!$this->settings['glossify_taxonomy_case_sensitivity']) {
          $result->name_norm = mb_strtolower($result->name_norm);
        }
        $terms[$result->name_norm] = $result;
      }

      // Process text.
      if (count($terms) > 0) {
        $text = $this->parseTooltipMatch(
          $text,
          $terms,
          $this->settings['glossify_taxonomy_case_sensitivity'],
          $this->settings['glossify_taxonomy_first_only'],
          $this->settings['glossify_taxonomy_type'],
          $this->settings['glossify_taxonomy_tooltip_truncate'],
          $this->settings['glossify_taxonomy_urlpattern'],
          $langcode
        );
      }
    }

    // Prepare result.
    $result = new FilterProcessResult($text);

    // Add cache tag dependency.
    foreach ($vocabs as $vid) {
      $cacheTags[] = 'taxonomy_term_list:' . $vid;
    }
    $result->setCacheTags($cacheTags);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['status'])) {
      $this->status = (bool) $configuration['status'];
    }
    if (isset($configuration['weight'])) {
      $this->weight = (int) $configuration['weight'];
    }
    if (isset($configuration['settings'])) {
      // Workaround for not accepting arrays in config schema.
      if (is_array($configuration['settings']['glossify_taxonomy_vocabs'])) {
        $glossify_taxonomy_vocabs = array_filter($configuration['settings']['glossify_taxonomy_vocabs']);
        $configuration['settings']['glossify_taxonomy_vocabs'] = implode(';', $glossify_taxonomy_vocabs);
      }
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

}
