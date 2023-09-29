<?php

namespace Drupal\fontyourface\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fontyourface\Entity\Font;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to define the fonts.
 *
 * @package Drupal\fontyourface\Form
 *
 * @ingroup fontyourface
 */
class FontSettingsForm extends ConfigFormBase {

  /*
   * Default hook constants api.
   */
  const HOOK_API = 'fontyourface_api';
  const HOOK_IMPORT = 'fontyourface_api';


  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ModuleHandlerInterface $module_handler) {
    $this->themeHandler = $theme_handler;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fontyourface.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'Font_settings';
  }

  /**
   * Defines the settings form for Font entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fontyourface.settings');
    $form['Font_settings']['#markup'] = 'Settings form for @font-your-face. Support modules can use this form for settings or to import fonts.';
    $form['load_all_enabled_fonts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load all enabled fonts'),
      '#default_value' => (int) $config->get('load_all_enabled_fonts'),
      '#description' => $this->t('This will load all fonts that have been enabled regardless of theme. Warning: this may add considerable download weight to your pages depending on the number of enabled fonts'),
    ];
    $themes = [];
    foreach ($this->themeHandler->listInfo() as $name => $theme) {
      if ($theme->status === 1) {
        $themes[$name] = $theme->info['name'];
      }
    }
    $form['load_on_themes'] = [
      '#type' => 'select',
      '#title' => $this->t('Load fonts only on selected themes'),
      '#options' => $themes,
      '#default_value' => $config->get('load_on_themes'),
      '#description' => $this->t('Select only the themes on which you need to enable all fonts. Leave blank to load it on all themes.'),
      '#states' => [
        'visible' => [
          ':input[name="load_all_enabled_fonts"]' => ['checked' => TRUE],
        ],
      ],
      '#multiple' => TRUE,
    ];
    $form['imports'] = [
      '#type' => 'fieldset',
      '#title' => 'Import',
      '#collapsible' => FALSE,
    ];
    // Set the module weight. There is some general Drupal funk around module
    // weights.
    module_set_weight('fontyourface', 1);
    $this->moduleHandler->invokeAllWith(self::HOOK_API, function (callable $hook, string $module) {
      module_set_weight($module, 10);
    });
    $this->moduleHandler->invokeAllWith(self::HOOK_IMPORT, function (callable $hook, string $module) use (&$form) {
      $form['imports']['import_' . $module] = [
        '#type' => 'submit',
        '#value' => $this->t('Import from @module', ['@module' => $module]),
        '#attributes' => [
          'style' => 'margin: 10px;',
        ],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }
    );

    $form['imports']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import all fonts'),
      '#weight' => 10,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $op = (string) $values['op'];

    $batch = [
      'title' => $this->t('Importing...'),
      'operations' => [],
      'finished' => '\Drupal\fontyourface\Form\FontSettingsForm::importFinished',
    ];
    $this->moduleHandler->invokeAllWith(self::HOOK_IMPORT, function (callable $hook, string $module) use ($op, &$batch) {
      if ($op == $this->t('Import all fonts') || $op == $this->t('Import from @module', ['@module' => $module])) {
        $batch['operations'][] = [
          '\Drupal\fontyourface\Form\FontSettingsForm::importFromProvider',
          [
            $module,
          ],
        ];
      }
    });
    if (!empty($batch['operations'])) {
      batch_set($batch);
    }

    if ($op == $this->t('Save configuration')) {
      $this->config('fontyourface.settings')
        ->set('load_all_enabled_fonts', $values['load_all_enabled_fonts'])
        ->set('load_on_themes', $values['load_on_themes'])
        ->save();
      parent::submitForm($form, $form_state);
    }

    // Resave enabled fonts.
    $fonts = Font::loadActivatedFonts();
    foreach ($fonts as $font) {
      $font->activate();
    }
  }

  /**
   * Imports fonts from provider. Batch operation handler.
   *
   * @param string $module
   *   Module name that is providing fonts.
   * @param array $context
   *   Context batch array.
   */
  public static function importFromProvider($module, array &$context) {
    $context['message'] = new TranslatableMarkup('Importing from @module', ['@module' => $module]);
    $module_handler = \Drupal::moduleHandler();
    $new_context = $module_handler->invoke($module, 'fontyourface_import', [$context]);
    if (!empty($new_context)) {
      $context = $new_context;
    }
  }

  /**
   * Imports fonts from provider. Batch completion handler.
   *
   * @param bool $success
   *   Boolean if operations were successful.
   * @param array $results
   *   Results of batch operations.
   * @param array $operations
   *   List of batch operations run.
   */
  public static function importFinished($success, array $results, array $operations) {
    \Drupal::messenger()->addMessage(new TranslatableMarkup('Finished importing fonts.'));
  }

}
