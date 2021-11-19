<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Psr\Log\LoggerInterface;

/**
 * Processes Gutenberg reusable blocks.
 */
class ReusableBlockProcessor implements GutenbergBlockProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\RendererInterface instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Gutenberg logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ReusableBlockProcessor constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Psr\Log\LoggerInterface $logger
   *   Gutenberg logger interface.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function processBlock(array &$block, &$block_content, RefinableCacheableDependencyInterface $bubbleable_metadata) {
    $block_attributes = $block['attrs'];

    if (empty($block_attributes['ref'])) {
      // This should not happen, and typically means there's a bug somewhere.
      $this->logger->error('Missing reusable block reference ID.');
      return;
    }

    $bid = $block_attributes['ref'];
    $block_entity = BlockContent::load($bid);

    if ($block_entity) {
      $render = $this
        ->entityTypeManager
        ->getViewBuilder('block_content')
        ->view($block_entity, 'reusable_block');

      $block_content = $this->renderer->render($render);

      $bubbleable_metadata->addCacheableDependency(
        CacheableMetadata::createFromRenderArray($render)
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported(array $block, $block_content = '') {
    return $block['blockName'] === 'core/block';
  }

}
