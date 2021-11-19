<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for configurable Gutenberg block processors.
 */
interface GutenbergConfigurableBlockProcessorInterface {

  /**
   * Returns a block processor's settings array.
   *
   * It shouldn't modify the $form array.
   *
   * @param array $form
   *   A minimally prepopulated form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the (entire) configuration form.
   *
   * @return array
   *   The $form array with additional form elements for the settings of this
   *   processor. The form values should match $this->defaultConfiguration().
   */
  public function provideSettings(array $form, FormStateInterface $form_state);

  /**
   * Provide the current filter settings.
   *
   * @param array $settings
   *   The current settings.
   */
  public function setSettings(array $settings);

  /**
   * Gets default configuration for this processor.
   *
   * @return array
   *   The default configuration.
   */
  public function defaultConfiguration();

}
