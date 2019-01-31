<?php

namespace Drupal\glossify_node\Plugin\Filter;

use Drupal\glossify\GlossifyBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\node\Entity\NodeType;

/**
 * Filter to find and process found taxonomy terms in the fields value.
 *
 * @Filter(
 *   id = "glossify_node",
 *   title = @Translation("Tooltips with nodes"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   settings = {
 *     "glossify_node_case_sensitivity" = TRUE,
 *     "glossify_node_first_only" = FALSE,
 *     "glossify_node_type" = "tooltips",
 *     "glossify_node_bundles" = NULL,
 *     "glossify_node_urlpattern" = "/node/[id]",
 *   },
 *   weight = -10
 * )
 */
class NodeTooltip extends GlossifyBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $ntype_options = [];

    // Get all node types.
    $node_types = NodeType::loadMultiple();
    foreach ($node_types as $id => $node_type) {
      $ntype_options[$id] = $node_type->get('name');
    }

    $form['glossify_node_case_sensitivity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive'),
      '#description' => $this->t('Whether or not the match is case sensitive.'),
      '#default_value' => $this->settings['glossify_node_case_sensitivity'],
    ];
    $form['glossify_node_first_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('First match only'),
      '#description' => $this->t('Match and link only the first occurance per field.'),
      '#default_value' => $this->settings['glossify_node_first_only'],
    ];
    $form['glossify_node_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#required' => TRUE,
      '#options' => [
        'tooltips' => $this->t('Tooltips'),
        'links' => $this->t('Links'),
        'tooltips_links' => $this->t('Tooltips and links'),
      ],
      '#description' => $this->t('How to show matches in content. Description as HTML5 tooltip (abbr element), link to description or both.'),
      '#default_value' => $this->settings['glossify_node_type'],
    ];
    $form['glossify_node_bundles'] = [
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#element_validate' => [
        [
          get_class($this),
          'validateNodeBundles',
        ],
      ],
      '#title' => $this->t('Node types'),
      '#description' => $this->t('Select the node types you want to use titles from to link to their node page.'),
      '#options' => $ntype_options,
      '#default_value' => explode(';', $this->settings['glossify_node_bundles']),
    ];
    $form['glossify_node_urlpattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL pattern'),
      '#description' => $this->t('Url pattern, used for linking matched words. Accepts "[id]" as token. Example: "/node/[id]"'),
      '#default_value' => $this->settings['glossify_node_urlpattern'],
    ];
    return $form;
  }

  /**
   * Validation callback for glossify_node_bundles.
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
  public static function validateNodeBundles(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValues();
    // Make taxonomy_vocabs required if the filter is enabled.
    if (!empty($values['filters']['glossify_node']['status'])) {
      $field_values = array_filter($values['filters']['glossify_node']['settings']['glossify_node_bundles']);
      if (empty($field_values)) {
        $form_state->setError($element, t('%field is required.', ['%field' => $element['#title']]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    // Get node types.
    $node_types = explode(';', $this->settings['glossify_node_bundles']);

    if (count($node_types)) {
      $terms = [];

      // Get node data.
      $query = \Drupal::database()->select('node_field_data', 'nfd');
      $query->addfield('nfd', 'nid', 'id');
      $query->addfield('nfd', 'title', 'name');
      $query->addfield('nfd', 'title', 'name_norm');
      $query->addField('nb', 'body_value', 'tip');
      $query->join('node__body', 'nb', 'nb.entity_id = nfd.nid');
      $query->condition('nfd.type', $node_types, 'IN');
      $query->condition('nfd.status', 1);
      $query->orderBy('name_norm', 'DESC');
      $results = $query->execute()->fetchAllAssoc('name_norm');

      // Build terms array.
      foreach ($results as $result) {
        // Make name_norm lowercase, it seems not possible in PDO query?
        if (!$this->settings['glossify_node_case_sensitivity']) {
          $result->name_norm = strtolower($result->name_norm);
        }
        $terms[$result->name_norm] = $result;
      }

      // Process text.
      if (count($terms) > 0) {
        $text = $this->parseTooltipMatch(
          $text,
          $terms,
          $this->settings['glossify_node_case_sensitivity'],
          $this->settings['glossify_node_first_only'],
          $this->settings['glossify_node_type'],
          $this->settings['glossify_node_urlpattern']
        );
      }
    }
    return new FilterProcessResult($text);
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
      if (is_array($configuration['settings']['glossify_node_bundles'])) {
        $glossify_node_bundles = array_filter($configuration['settings']['glossify_node_bundles']);
        $configuration['settings']['glossify_node_bundles'] = implode($glossify_node_bundles, ';');
      }
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

}
