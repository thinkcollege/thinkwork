<?php

namespace Drupal\gutenberg\Parser;

/**
 * Class BlockParserFrame.
 *
 * This class was ported from the WordPress code.
 *
 * @link https://github.com/WordPress/WordPress for the canonical source repository
 * @copyright Copyright (c) 2011-2020 WordPress - Web publishing software.
 * @license https://github.com/WordPress/WordPress/blob/master/license.txt GNU General Public License.
 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-block-parser.php
 *
 * Holds partial blocks in memory while parsing.
 */
class BlockParserFrame {

  /**
   * Full or partial block.
   *
   * @var BlockParserBlock
   */
  public $block;

  /**
   * Byte offset into document for start of parse token.
   *
   * @var int
   */
  public $tokenStart;

  /**
   * Byte length of entire parse token string.
   *
   * @var int
   */
  public $tokenLength;

  /**
   * Byte offset into document for after parse token ends.
   *
   * (used during reconstruction of stack into parse production)
   *
   * @var int
   */
  public $prevOffset;

  /**
   * Byte offset into document where leading HTML before token starts.
   *
   * @var int
   */
  public $leadingHtmlStart;

  /**
   * Constructor.
   *
   * Will populate object properties from the provided arguments.
   *
   * @param BlockParserBlock $block
   *   Full or partial block.
   * @param int $token_start
   *   Byte offset into document for start of parse token.
   * @param int $token_length
   *   Byte length of entire parse token string.
   * @param int $prev_offset
   *   Byte offset into document for after parse token ends.
   * @param int $leading_html_start
   *   Byte offset into document where leading HTML before token starts.
   */
  public function __construct(BlockParserBlock $block, $token_start, $token_length, $prev_offset = NULL, $leading_html_start = NULL) {
    $this->block = $block;
    $this->tokenStart = $token_start;
    $this->tokenLength = $token_length;
    $this->prevOffset = isset($prev_offset) ? $prev_offset : $token_start + $token_length;
    $this->leadingHtmlStart = $leading_html_start;
  }

}
