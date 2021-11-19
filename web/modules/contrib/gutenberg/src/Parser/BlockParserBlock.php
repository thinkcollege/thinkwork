<?php

namespace Drupal\gutenberg\Parser;

/**
 * Class BlockParserBlock.
 *
 * This class was ported from the WordPress code.
 *
 * @link https://github.com/WordPress/WordPress for the canonical source repository
 * @copyright Copyright (c) 2011-2020 WordPress - Web publishing software.
 * @license https://github.com/WordPress/WordPress/blob/master/license.txt GNU General Public License.
 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-block-parser.php
 *
 * Holds the block structure in memory.
 *
 * @since 3.8.0
 */
class BlockParserBlock {

  /**
   * Name of block.
   *
   * @var string
   * @since 3.8.0
   * @example "core/paragraph"
   */
  public $blockName;

  /**
   * Optional set of attributes from block comment delimiters.
   *
   * @var array|null
   * @example array( 'columns' => 3 )
   *
   * @since 3.8.0
   */
  public $attrs;

  /**
   * List of inner blocks (of this same class)
   *
   * @var BlockParserBlock[]
   * @since 3.8.0
   */
  public $innerBlocks;

  /**
   * HTML from inside block comment delimiters after removing inner blocks.
   *
   * @var string
   * @since 3.8.0
   * @example "...Just <!-- wp:test /--> testing..." -> "Just testing..."
   */
  public $innerHTML;

  /**
   * List of string fragments and null markers where inner blocks were found.
   *
   * @var array
   * @since 4.2.0
   * @example array(
   *   'innerHTML'    => 'BeforeInnerAfter',
   *   'innerBlocks'  => array( block, block ),
   *   'innerContent' => array( 'Before', null, 'Inner', null, 'After' ),
   * )
   */
  public $innerContent;

  /**
   * Constructor.
   *
   * Will populate object properties from the provided arguments.
   *
   * @param string $name
   *   Name of block.
   * @param array $attrs
   *   Optional set of attributes from block comment delimiters.
   * @param array $inner_blocks
   *   List of inner blocks (of this same class).
   * @param string $inner_html
   *   Resultant HTML from inside block comment delimiters after
   *    removing inner blocks.
   * @param array $inner_content
   *   List of string fragments and null markers where inner blocks were found.
   *
   * @since 3.8.0
   */
  public function __construct($name, array $attrs, array $inner_blocks, $inner_html, array $inner_content) {
    $this->blockName = $name;
    $this->attrs = $attrs;
    $this->innerBlocks = $inner_blocks;
    $this->innerHTML = $inner_html;
    $this->innerContent = $inner_content;
  }

}
