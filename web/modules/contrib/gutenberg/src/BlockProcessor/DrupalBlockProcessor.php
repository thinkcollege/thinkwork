<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\gutenberg\BlocksRendererHelper;

/**
 * Processes Drupal blocks than can be embedded.
 */
class DrupalBlockProcessor implements GutenbergBlockProcessorInterface {

  use StringTranslationTrait;

  /**
   * Drupal\gutenberg\BlocksRendererHelper instance.
   *
   * @var \Drupal\gutenberg\BlocksRendererHelper
   */
  protected $blocksRenderer;

  /**
   * Drupal\Core\Render\RendererInterface instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * DynamicRenderProcessor constructor.
   *
   * @param \Drupal\gutenberg\BlocksRendererHelper $blocks_renderer
   *   The block renderer.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    BlocksRendererHelper $blocks_renderer,
    RendererInterface $renderer
  ) {
    $this->blocksRenderer = $blocks_renderer;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function processBlock(array &$block, &$block_content, RefinableCacheableDependencyInterface $bubbleable_metadata) {
    $block_attributes = $block['attrs'];

    /* TODO: Check if the block is currently in the "allowed" list?.
     *  Don't think this is possible in the backend as the content has no
     *  direct reference to the parent entity. Might have to be handled in the
     *  Gutenberg editor on load.
     */
    $plugin_id = $block_attributes['blockId'];

    $config = $block_attributes['settings'] ?? [];

    $plugin_block = $this->blocksRenderer->getBlockFromPluginId($plugin_id, $config);

    if ($plugin_block) {
      $access_result = $this->blocksRenderer->getBlockAccess($plugin_block);
      $bubbleable_metadata->addCacheableDependency($access_result);

      if ($access_result->isForbidden()) {
        /*
         * Add as a comment in the HTML.
         * Fixme: Is this a good idea (as we're exposing backend info to the
         *  frontend)? I'm leaning towards removing it.
         */
        $render_content = [
          '#prefix' => Markup::create('<!-- '),
          '#markup' => $this->t('Block access denied: @plugin_id', ['@plugin_id' => $plugin_id]),
          '#suffix' => Markup::create(' -->'),
        ];
      }
      else {
        $render_content = $this->blocksRenderer->getRenderFromBlockPlugin($plugin_block, FALSE);
      }

      $render = [
        'content' => $render_content,
      ];
      // Render the css class if available.
      if (!empty($block_attributes['className'])) {
        $render['#prefix'] = sprintf('<div class="%s">', Html::escape($block_attributes['className']));
        $render['#suffix'] = '</div>';
      }

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
    return substr($block['blockName'], 0, 12) === 'drupalblock/';
  }

}
