<?php

namespace Drupal\gutenberg;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BlocksRendererHelper.
 */
class BlocksRendererHelper {

  /**
   * Drupal\Core\Render\RendererInterface instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Drupal\Core\Block\BlockManagerInterface instance.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The context handler service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The Gutenberg logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * BlocksRendererHelper constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The plugin context handler.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Psr\Log\LoggerInterface $logger
   *   Gutenberg logger interface.
   */
  public function __construct(
    RendererInterface $renderer,
    BlockManagerInterface $block_manager,
    AccountProxyInterface $current_user,
    ContextRepositoryInterface $context_repository,
    ContextHandlerInterface $context_handler,
    TitleResolverInterface $title_resolver,
    LoggerInterface $logger
  ) {
    $this->renderer = $renderer;
    $this->blockManager = $block_manager;
    $this->currentUser = $current_user;
    $this->contextRepository = $context_repository;
    $this->contextHandler = $context_handler;
    $this->titleResolver = $title_resolver;
    $this->logger = $logger;
  }

  /**
   * Create instance of block plugin from given ID and config.
   *
   * @param string $id
   *   Block Plugin ID.
   * @param array $config
   *   Block configuration.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface|null
   *   Block Plugin instance or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  public function getBlockFromPluginId($id, array $config = []) {
    try {
      $block_instance = $this->blockManager->createInstance($id, $config);

      // Apply runtime contexts.
      if ($block_instance instanceof ContextAwarePluginInterface) {
        $contexts = $this->contextRepository->getRuntimeContexts(
          $block_instance->getContextMapping()
        );
        $this->contextHandler->applyContextMapping(
          $block_instance, $contexts
        );
      }

      return $block_instance;
    }
    catch (PluginException $e) {
      $this->logger->error($e->getMessage());

      return NULL;
    }
  }

  /**
   * Return render array for given block plugin.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $plugin_block
   *   Block Plugin instance.
   * @param bool $render_markup
   *   Render the response as markup.
   *
   * @return array|\Drupal\Component\Render\MarkupInterface
   *   Array containing render array, or empty.
   */
  public function getRenderFromBlockPlugin(BlockPluginInterface $plugin_block, $render_markup = TRUE) {
    $render = [
      '#theme' => 'block',
      '#attributes' => [],
      '#contextual_links' => [],
      '#configuration' => $plugin_block->getConfiguration(),
      '#plugin_id' => $plugin_block->getPluginId(),
      '#base_plugin_id' => $plugin_block->getBaseId(),
      '#derivative_plugin_id' => $plugin_block->getDerivativeId(),
    ];

    // Handle title blocks specially.
    $is_title_block = $plugin_block instanceof TitleBlockPluginInterface;
    if ($is_title_block) {
      $request = \Drupal::request();
      $route_match = \Drupal::routeMatch();
      $title = $this->titleResolver->getTitle($request, $route_match->getRouteObject());
      $plugin_block->setTitle($title);
    }

    // Build the block content.
    $content = $plugin_block->build();

    $this->addPropertiesToRender($render, $content);
    $this->addCacheTagsToRender($render, $content);

    $render['content'] = $content;

    if ($is_title_block) {
      // Add the title block cache context.
      $build['content']['#cache']['contexts'][] = 'url';
    }

    if ($render_markup) {
      return $this->renderer->renderRoot($render);
    }

    return $render;
  }

  /**
   * Check the access of the block.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $plugin_block
   *   Block Plugin instance.
   * @param bool $return_as_object
   *   Whether to return as an object or not.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function getBlockAccess(BlockPluginInterface $plugin_block, $return_as_object = TRUE) {
    return $plugin_block->access($this->currentUser, $return_as_object);
  }

  /**
   * Add attributes and contextual links to render array.
   *
   * @param array $render
   *   Render array.
   * @param array $content
   *   Block build content.
   */
  protected function addPropertiesToRender(array &$render, array &$content) {
    // Bubble block attributes up if possible.
    // This allows modules like Quickedit to function.
    // See \Drupal\block\BlockViewBuilder::preRender() for reference.
    if ($content !== NULL && !Element::isEmpty($content)) {
      foreach (['#attributes', '#contextual_links'] as $property) {
        if (isset($content[$property])) {
          $render[$property] += $content[$property];
          unset($content[$property]);
        }
      }
    }
  }

  /**
   * Add default cache tags for empty block.
   *
   * @param array $render
   *   Render array.
   * @param array $content
   *   Block build content.
   */
  protected function addCacheTagsToRender(array &$render, array $content) {
    // If the block is empty, instead of trying to render the block
    // correctly return just #cache, so that the render system knows the
    // reasons (cache contexts & tags) why this block is empty.
    if (Element::isEmpty($content)) {
      $cacheableMetadata = CacheableMetadata::createFromObject($render);
      $cacheableMetadata->applyTo($render);
      if (isset($content['#cache'])) {
        $render['#cache'] += $content['#cache'];
      }
    }
  }

}
