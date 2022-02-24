<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Psr\Log\LoggerInterface;
use Drupal\gutenberg\TinyColor;
use Drupal\gutenberg\BlocksLibraryManager;

/**
 * Processes Gutenberg duotone style.
 */
class DuotoneProcessor implements GutenbergBlockProcessorInterface {

  /**
   * TinyColor.
   *
   * @var \Drupal\gutenberg\TinyColor
   */
  protected $tinyColor;

  /**
   * Gutenberg blocks library.
   *
   * @var \Drupal\gutenberg\BlocksLibraryManager
   */
  protected $blocksLibrary;

  /**
   * The Gutenberg logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * DuotoneProcessor constructor.
   *
   * @param \Drupal\gutenberg\TinyColor $tiny_color
   *   The renderer.
   * @param \Drupal\gutenberg\BlocksLibraryManager $blocks_library
   *   Blocks library manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Gutenberg logger interface.
   */
  public function __construct(
    TinyColor $tiny_color,
    BlocksLibraryManager $blocks_library,
    LoggerInterface $logger
  ) {
    $this->tinyColor = $tiny_color;
    $this->blocksLibrary = $blocks_library;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function processBlock(array &$block, &$block_content, RefinableCacheableDependencyInterface $bubbleable_metadata) {
    $filter_preset   = [
      'slug'   => uniqid(),
      'colors' => $block['attrs']['style']['color']['duotone'],
    ];
    $filter_id       = 'wp-duotone-' . $filter_preset['slug'];
    $filter_property = "url('#" . $filter_id . "')";

    $block_definition = $this->blocksLibrary->getBlockDefinition($block['blockName']);

    $selector = '';
    $scope = ".$filter_id";

    if (isset($block_definition['supports']['color']['__experimentalDuotone'])) {
      $duotone_support = $block_definition['supports']['color']['__experimentalDuotone'];
      $selectors = explode(',', $duotone_support);
      $scoped = [];

      foreach ($selectors as $sel) {
        $scoped[] = $scope . ' ' . trim($sel);
      }
      $selector = implode(', ', $scoped);
    }

    $style = "$selector { filter: $filter_property}";
    $svg = $this->getFilterSvg($filter_preset);

    $block_content = "$svg<style>$style</style>$block_content";

    $block_content = preg_replace(
      '/' . preg_quote('class="', '/') . '/',
      'class="' . $filter_id . ' ',
      $block_content,
      1
    );

    $render = [
      '#markup' => $block_content,
    ];

    $bubbleable_metadata->addCacheableDependency(
      CacheableMetadata::createFromRenderArray($render)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported(array $block, $block_content = '') {
    return isset($block['attrs']['style']['color']['duotone']);
  }

  /**
   * Gets a SVG with a filter.
   *
   * @param array $preset
   *   Preset.
   */
  protected function getFilterSvg(array $preset) {
    $duotone_id     = $preset['slug'];
    $duotone_colors = $preset['colors'];
    $filter_id      = 'wp-duotone-' . $duotone_id;

    $duotone_values = [
      'r' => [],
      'g' => [],
      'b' => [],
      'a' => [],
    ];

    foreach ($duotone_colors as $color_str) {
      $color = $this->tinyColor->string_to_rgb($color_str);

      $duotone_values['r'][] = $color['r'] / 255;
      $duotone_values['g'][] = $color['g'] / 255;
      $duotone_values['b'][] = $color['b'] / 255;
      $duotone_values['a'][] = $color['a'];
    }

    ob_start();

    ?>
  
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 0 0"
      width="0"
      height="0"
      focusable="false"
      role="none"
      style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;"
    >
      <defs>
        <filter id="<?php echo ($filter_id); ?>">
          <feColorMatrix
            color-interpolation-filters="sRGB"
            type="matrix"
            values="
              .299 .587 .114 0 0
              .299 .587 .114 0 0
              .299 .587 .114 0 0
              .299 .587 .114 0 0
            "
          />
          <feComponentTransfer color-interpolation-filters="sRGB" >
            <feFuncR type="table" tableValues="<?php echo (implode(' ', $duotone_values['r'])); ?>" />
            <feFuncG type="table" tableValues="<?php echo (implode(' ', $duotone_values['g'])); ?>" />
            <feFuncB type="table" tableValues="<?php echo (implode(' ', $duotone_values['b'])); ?>" />
            <feFuncA type="table" tableValues="<?php echo (implode(' ', $duotone_values['a'])); ?>" />
          </feComponentTransfer>
          <feComposite in2="SourceGraphic" operator="in" />
        </filter>
      </defs>
    </svg>
  
    <?php

    $svg = ob_get_clean();

    return $svg;
  }

}
