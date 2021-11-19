<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for configurable processors.
 *
 * @package Drupal\gutenberg\BlockProcessor
 */
abstract class ConfigurableProcessorBase implements GutenbergBlockProcessorInterface, GutenbergConfigurableBlockProcessorInterface {

  use StringTranslationTrait;

  /**
   * The current Gutenberg filter settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function provideSettings(array $form, FormStateInterface $form_state) {
    return [];
  }

}
