<?php

namespace Drupal\gutenberg;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Theme\MissingThemeDependencyException;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides the default .gutenberg.yml library plugin manager.
 */
class GutenbergLibraryManager extends DefaultPluginManager implements GutenbergLibraryManagerInterface {

  /**
   * Provides default values for all gutenberg plugins.
   *
   * @var array
   */
  protected $defaults = [
    'libraries-edit' => [],
    'libraries-view' => [],
    'dynamic-blocks' => [],
  ];

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The Gutenberg logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Static cache of theme Gutenberg plugin definitions.
   *
   * @var array
   */
  protected $activeThemeDefinitions;

  /**
   * Static cache of the merged active theme Gutenberg plugin definition.
   *
   * @var array
   */
  protected $activeThemeMergedDefinition;

  /**
   * Static cache of Gutenberg plugin definitions keyed by the extension type.
   *
   * @var array
   */
  protected $definitionsByExtension;

  /* @noinspection MagicMethodsValidityInspection */
  /* @noinspection PhpMissingParentConstructorInspection */

  /**
   * Constructs a new GutenbergManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Psr\Log\LoggerInterface $logger
   *   Gutenberg logger interface.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ThemeHandlerInterface $theme_handler,
    ThemeInitializationInterface $theme_initialization,
    CacheBackendInterface $cache_backend,
    LoggerInterface $logger
  ) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->themeInitialization = $theme_initialization;
    $this->logger = $logger;
    $this->alterInfo('gutenberg_info');
    $this->setCacheBackend($cache_backend, 'gutenberg', ['gutenberg']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery(
        'gutenberg',
        $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories()
      );
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->findAll();

    foreach ($definitions as $plugin_id => &$definition) {
      $definitions[$plugin_id] = $definition + [
        'id' => $plugin_id,
        'provider' => $plugin_id,
      ];
      $this->processDefinition($definition, $plugin_id);
    }
    unset($definition);

    $this->alterDefinitions($definitions);

    // If this plugin was provided by a module/theme that does not exist,
    // remove the plugin definition.
    foreach ($definitions as $plugin_id => $definition) {
      $plugin_id = $this->extractProviderFromDefinition($definition);
      if ($plugin_id && !in_array($plugin_id, ['core', 'component']) && !$this->providerExists($plugin_id)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (empty($definition['provider'])) {
      throw new InvalidPluginDefinitionException(
        sprintf('Gutenberg plugin property (%s) definition "provider" is required.', $plugin_id)
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    $this->definitionsByExtension = NULL;
    $this->activeThemeDefinitions = NULL;
    $this->activeThemeMergedDefinition = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleDefinitions() {
    return $this->getDefinitionsByExtension()['module'];
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeDefinitions() {
    return $this->getDefinitionsByExtension()['theme'];
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeDefinitions() {
    if (isset($this->activeThemeDefinitions)) {
      return $this->activeThemeDefinitions;
    }

    $cid = $this->cacheKey . ':active_themes';

    if ($cache = $this->cacheBackend->get($cid)) {
      return $this->activeThemeDefinitions = $cache->data;
    }

    $theme_name = $this->themeHandler->getDefault();
    $theme_definitions = [];
    try {
      $active_theme = $this->themeInitialization->getActiveThemeByName($theme_name);
      $definitions = $this->getDefinitionsByExtension();

      // Note: Reversing the order so that base themes are first.
      $themes = array_reverse(
        array_merge([$active_theme->getName()], array_keys($active_theme->getBaseThemeExtensions()))
      );

      foreach ($themes as $theme) {
        if (isset($definitions['theme'][$theme])) {
          $theme_definitions[$theme] = $definitions['theme'][$theme];
        }
      }
    }
    catch (MissingThemeDependencyException $e) {
      $this->logger->error($e->getMessage());
    }

    $this->cacheBackend->set(
      $cid,
      $theme_definitions,
      Cache::PERMANENT,
      ['gutenberg']
    );
    $this->activeThemeDefinitions = $theme_definitions;

    return $this->activeThemeDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeMergedDefinition() {
    if (isset($this->activeThemeMergedDefinition)) {
      return $this->activeThemeMergedDefinition;
    }

    $cid = $this->cacheKey . ':active_theme';

    if ($cache = $this->cacheBackend->get($cid)) {
      return $this->activeThemeMergedDefinition = $cache->data;
    }

    // Specify the default definition.
    $definition = $this->defaults;

    foreach ($this->getActiveThemeDefinitions() as $array) {
      foreach ($array as $key => $value) {
        if ($key === 'id' || $key === 'provider') {
          // Ignore irrelevant properties.
          continue;
        }

        if (isset($definition[$key]) && is_array($definition[$key]) && is_array($value)) {
          // Merge arrays.
          $definition[$key] = array_merge($definition[$key], $value);
        }
        // Otherwise, use the latter value, overriding any previous value.
        else {
          $definition[$key] = $value;
        }
      }
    }

    $this->activeThemeMergedDefinition = $definition;
    $this->cacheBackend->set($cid, $definition, Cache::PERMANENT, ['gutenberg']);

    return $this->activeThemeMergedDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByExtension() {
    if (isset($this->definitionsByExtension)) {
      return $this->definitionsByExtension;
    }

    $cid = $this->cacheKey . ':by_extension';

    if ($cache = $this->cacheBackend->get($cid)) {
      return $this->definitionsByExtension = $cache->data;
    }

    $definitions_by_extension = [
      'theme' => [],
      'module' => [],
    ];
    foreach ($this->getDefinitions() as $plugin_id => $plugin_definition) {
      if ($this->themeHandler->themeExists($plugin_id)) {
        $definitions_by_extension['theme'][$plugin_id] = $plugin_definition;
      }
      elseif ($this->moduleHandler->moduleExists($plugin_id)) {
        $definitions_by_extension['module'][$plugin_id] = $plugin_definition;
      }
    }

    $this->cacheBackend->set($cid, $definitions_by_extension, Cache::PERMANENT, ['gutenberg']);
    $this->definitionsByExtension = $definitions_by_extension;

    return $this->definitionsByExtension;
  }

}
