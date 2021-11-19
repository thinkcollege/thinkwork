<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\gutenberg\GutenbergLibraryManagerInterface;

/**
 * Processes blocks which can be rendered dynamically server-side.
 */
class DynamicRenderProcessor implements GutenbergBlockProcessorInterface {

  /**
   * The Gutenberg library manager.
   *
   * @var \Drupal\gutenberg\GutenbergLibraryManagerInterface
   */
  protected $libraryManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Array of dynamic block names.
   *
   * @var array
   */
  protected $dynamicBlocks;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * DynamicRenderProcessor constructor.
   *
   * @param \Drupal\gutenberg\GutenbergLibraryManagerInterface $library_manager
   *   The Gutenberg library manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    GutenbergLibraryManagerInterface $library_manager,
    RendererInterface $renderer,
    ModuleHandlerInterface $module_handler
  ) {
    $this->libraryManager = $library_manager;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function processBlock(array &$block, &$block_content, RefinableCacheableDependencyInterface $bubbleable_metadata) {
    $build = [
      '#theme' => 'gutenberg_block',
      '#block_name' => $block['blockName'],
      '#block_attributes' => $block['attrs'],
      '#block_content' => [
        // TODO @codebymikey: Review whether this might be susceptible to XSS.
        // I don't think it should.
        '#markup' => Markup::create($block_content),
      ],
      '#pre_render' => [],
    ];

    // If an alter hook wants to modify the block contents, it can append
    // several #pre_render hooks, or appropriate #cache tags.
    $block_name = str_replace('-', '_', $block['blockName']);
    $block_parts = explode('/', $block_name);
    $hooks = [
      'gutenberg_block_view',
    ];

    $base_hook = 'gutenberg_block_view__';
    $hooks[] = $base_hook . $block_parts[0];
    if (count($block_parts) === 2) {
      // namespace/blockname format.
      $hooks[] = $base_hook . $block_parts[0] . '__' . $block_parts[1];
    }

    $this->moduleHandler->alter($hooks, $build, $block_content);

    $block_content = $this->renderer->render($build);

    $bubbleable_metadata->addCacheableDependency(
      CacheableMetadata::createFromRenderArray($build)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported(array $block, $block_content = '') {
    return isset($this->getDynamicBlockNames()[$block['blockName']]);
  }

  /**
   * List of dynamic Gutenberg blocks.
   *
   * @return array
   *   List of of dynamic block names.
   */
  protected function getDynamicBlockNames() {
    if ($this->dynamicBlocks === NULL) {
      $this->dynamicBlocks = [];
      foreach ($this->libraryManager->getDefinitions() as $definition) {
        foreach ($definition['dynamic-blocks'] as $block_name => $block_theme_definition) {
          $this->dynamicBlocks[$block_name] = $block_name;
        }
      }
    }

    return $this->dynamicBlocks;
  }

}
