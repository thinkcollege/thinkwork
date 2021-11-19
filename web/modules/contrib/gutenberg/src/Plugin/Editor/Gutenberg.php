<?php

namespace Drupal\gutenberg\Plugin\Editor;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\gutenberg\GutenbergPluginManager;
use Drupal\gutenberg\Controller\UtilsController;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\editor\Plugin\EditorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Gutenberg-based text editor for Drupal.
 *
 * @Editor(
 *   id = "gutenberg",
 *   label = @Translation("Gutenberg"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = FALSE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea"
 *   }
 * )
 */
class Gutenberg extends EditorBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler to invoke hooks on.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Gutenberg plugin manager.
   *
   * @var \Drupal\gutenberg\GutenbergPluginManager
   */
  protected $gutenbergPluginManager;

  /**
   * Drupal\Core\Render\RendererInterface instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a Gutenberg object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\gutenberg\GutenbergPluginManager $gutenberg_plugin_manager
   *   The Gutenberg plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GutenbergPluginManager $gutenberg_plugin_manager, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->gutenbergPluginManager = $gutenberg_plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.gutenberg.plugin'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'plugins' => ['language' => ['language_list' => 'un']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\editor\Entity\Editor $editor */
    $editor = $form_state->get('editor');
    // Gutenberg plugin settings, if any.
    $form['plugin_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Gutenberg plugin settings'),
      '#attributes' => [
        'id' => 'gutenberg-plugin-settings',
      ],
    ];
    $this->gutenbergPluginManager->injectPluginSettingsForm($form, $form_state, $editor);
    if (count(Element::children($form['plugins'])) === 0) {
      unset($form['plugins']);
      unset($form['plugin_settings']);
    }

    return $form;
  }

  /**
   * Returns a list of language codes supported by CKEditor.
   *
   * @return array
   *   An associative array keyed by language codes.
   */
  public function getLangcodes() {
    return ['en' => 'en'];
  }

  /**
   * Get javascript settings.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   */
  public function getJsSettings(Editor $editor) {
    $config = \Drupal::service('config.factory')->getEditable('gutenberg.settings');

    $node = \Drupal::routeMatch()->getParameter('node');

    if (!$node) {
      $route_match = \Drupal::service('current_route_match');
      if (!$route_match->getParameter('node_type')) {
        return;
      }
      $node_type = $route_match->getParameter('node_type')->get('type');
    }
    else {
      $node_type = $node->type->getString();
    }

    $blocks_settings = UtilsController::getBlocksSettings();

    $settings = [
      'contentType' => $node_type,
      'allowedBlocks' => $config->get($node_type . '_allowed_blocks'),
      'blackList' => $blocks_settings['blacklist'],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    $libraries = [
      'gutenberg/edit-node',
      // Media attributes overrides must come after all
      // Gutenberg initialization.
      'gutenberg/g-media-attributes',
      'gutenberg/blocks-edit',
      'gutenberg/drupal-blocks',
    ];

    return $libraries;
  }

  /**
   * Builds the "toolbar" configuration part of the CKEditor JS settings.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return array
   *   An array containing the "toolbar" configuration.
   *
   * @see getJsSettings()
   */
  public function buildToolbarJsSetting(Editor $editor) {
    $toolbar = [];

    return $toolbar;
  }

  /**
   * Builds the "contentsCss" configuration part of the CKEditor JS settings.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return array
   *   An array containing the "contentsCss" configuration.
   *
   * @see getJsSettings()
   */
  public function buildContentsCssJsSetting(Editor $editor) {
    return [];
  }

}
