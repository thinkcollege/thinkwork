<?php

namespace Drupal\fontyourface\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter handler which allows to search based on font providers.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("fontyourface_font_pid")
 */
class FontYourFaceProviderFilter extends StringFilter {

  /**
   * Default HOOK constant.
   */
  const HOOK = 'fontyourface_api';


  /**
   * Exposed filter options.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * Provide simple equality operator.
   */
  public function operators() {
    return [
      '=' => [
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opEqual',
        'values' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $options = ['All' => '- Any -'];
    $this->moduleHandler->invokeAllWith(self::HOOK, function (callable $hook, string $module) use (&$options) {
      $module_info = $hook();
      $options[$module] = $module_info['name'];
    });

    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Provider'),
      '#options' => $options,
      '#default_value' => $this->value,
    ];

    if ($form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();
      if (!isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }
    }
  }

}
