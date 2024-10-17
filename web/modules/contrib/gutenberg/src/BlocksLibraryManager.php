<?php

namespace Drupal\gutenberg;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Psr\Log\LoggerInterface;
use Drupal\gutenberg\Discovery\BlockJsonDiscovery;

/**
 * Provides the default .gutenberg.yml library plugin manager.
 */
class BlocksLibraryManager extends DefaultPluginManager {

  /**
   * App root.
   *
   * @var string
   */
  protected $root;

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
   * Static cache of Gutenberg core blocks definitions.
   *
   * @var array
   */
  protected $blocks;

  /**
   * Constructs a new GutenbergManager object.
   *
   * @param string $root
   *   App root.
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
    $root,
    ModuleHandlerInterface $module_handler,
    ThemeHandlerInterface $theme_handler,
    ThemeInitializationInterface $theme_initialization,
    CacheBackendInterface $cache_backend,
    LoggerInterface $logger
  ) {
    $this->root = $root;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->themeInitialization = $theme_initialization;
    $this->logger = $logger;
    $this->alterInfo('gutenberg_blocks_library_info');
    $this->setCacheBackend($cache_backend, 'gutenberg-blocks');
  }

  /**
   * {@inheritdoc}
   *
   * @todo For now, we get only the core block definitions.
   * We could extend it to all modules and themes but
   * perhaps the best solution would be to have a block
   * registration mechanism.
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $dirs = ['gutenberg' => $this->root . '/' . $this->moduleHandler->getModule('gutenberg')->getPath()];
      // To find all modules and themes:
      // $dirs += $this->moduleHandler->getModuleDirectories() +
      // $this->themeHandler->getThemeDirectories();
      $this->discovery = new BlockJsonDiscovery(
        $dirs
      );
    }
    return $this->discovery;
  }

  /**
   * Get block definition by name.
   *
   * @param string $name
   *   Block name.
   *
   * @return object|null
   *   Definition object.
   */
  public function getBlockDefinition($name) {
    $cid = $this->cacheKey . ':' . $name;

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $definitions = $this->getDiscovery()->findAll();
    foreach ($definitions as $definition) {
      if ($definition['name'] === $name) {
        $this->cacheBackend->set(
          $cid,
          $definition,
          Cache::PERMANENT,
          ['gutenberg', 'block']
        );

        return $definition;
      }
    }
    return NULL;
  }

}
