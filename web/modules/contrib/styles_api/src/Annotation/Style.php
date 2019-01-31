<?php

/**
 * @file
 * Contains Drupal\styles_api\Annotation\Style.
 */

namespace Drupal\styles_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Layout annotation object.
 *
 * Layouts are used to define a list of regions and then output render arrays
 * in each of the regions, usually using a template.
 *
 * Plugin namespace: Plugin\Style
 *
 * @see \Drupal\styles_api\Plugin\Style\StyleInterface
 * @see \Drupal\styles_api\Plugin\Style\StyleBase
 * @see \Drupal\styles_api\Plugin\Style\StylePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Style extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The layout type.
   *
   * Available options:
   *  - block: Layout for the whole page.
   *  - region: Layout for the main page response.
   *  - element: A partial layout that is typically used for sub-regions.
   *
   * @var string
   */
  public $type = 'block';

  /**
   * The human-readable name.
   *
   * @war \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An optional description for advanced layouts.
   *
   * Sometimes layouts are so complex that the name is insufficient to describe
   * a layout such that a visually impaired administrator could layout a page
   * for a non-visually impaired audience. If specified, it will provide a
   * description that is used for accessibility purposes.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The human-readable category.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * The theme hook used to render this layout.
   *
   * If specified, it's assumed that the module or theme registering this layout
   * will also register the theme hook with hook_theme() itself. This is
   * mutually exclusive with 'template' - you can't specify both.
   *
   * @var string optional
   *
   * @see hook_theme()
   */
  public $theme;

  /**
   * The template file to render this layout (relative to the 'path' given).
   *
   * If specified, then the styles_api module will register the template with
   * hook_theme() and the module or theme registering this layout does not need
   * to do it. This is mutually exclusive with 'theme' - you can't specify both.
   *
   * @var string optional
   *
   * @see hook_theme()
   */
  public $template;

  /**
   * Base path (relative to current module) to all resources (like the icon).
   *
   * @var string optional
   */
  public $path;

  /**
   * The path to the preview image (relative to the base path).
   *
   * @var string optional
   */
  public $icon;

}
