<?php

namespace Drupal\subpathauto\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures Subpathauto.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('subpathauto.settings');

    $form['depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum depth of sub-paths to alias'),
      '#options' => array_merge([0 => $this->t('Disabled')], range(1, MenuTreeStorage::MAX_DEPTH - 1)),
      '#default_value' => $config->get('depth'),
      '#description' => $this->t('Increasing this value may decrease performance.'),
    ];

    $is_redirect_installed = $this->moduleHandler->moduleExists('redirect');
    $form['redirect_support'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Support for redirects'),
      '#default_value' => $is_redirect_installed ? $config->get('redirect_support') : FALSE,
      '#disabled' => !$is_redirect_installed,
      '#description' => $is_redirect_installed ?
        $this->t('If checked, redirects will be taken into account when resolving sub aliases.'):
        $this->t('If checked, redirects will be taken into account when resolving sub aliases. The redirect module should be installed for this setting.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('subpathauto.settings')
      ->set('depth', $values['depth'])
      ->set('redirect_support', $values['redirect_support'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subpathauto_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['subpathauto.settings'];
  }

}
