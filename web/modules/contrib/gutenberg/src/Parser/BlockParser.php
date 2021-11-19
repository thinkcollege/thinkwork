<?php

namespace Drupal\gutenberg\Parser;

/**
 * Class BlockParser.
 *
 * This class was ported from the WordPress code.
 *
 * @link https://github.com/WordPress/WordPress for the canonical source repository
 * @copyright Copyright (c) 2011-2020 WordPress - Web publishing software.
 * @license https://github.com/WordPress/WordPress/blob/master/license.txt GNU General Public License.
 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-block-parser.php
 *
 * Parses a document and constructs a list of parsed block objects
 *
 * @since 3.8.0
 * @since 4.0.0 returns arrays not objects, all attributes are arrays
 */
class BlockParser {

  /**
   * Input document being parsed.
   *
   * @var string
   * @since 3.8.0
   * @example "Pre-text\n<!-- wp:paragraph -->This is inside a block!<!-- /wp:paragraph -->"
   */
  public $document;

  /**
   * Tracks parsing progress through document.
   *
   * @var int
   * @since 3.8.0
   */
  public $offset;

  /**
   * List of parsed blocks.
   *
   * @var BlockParserBlock[]
   * @since 3.8.0
   */
  public $output;

  /**
   * Stack of partially-parsed structures in memory during parse.
   *
   * @var BlockParserFrame[]
   * @since 3.8.0
   */
  public $stack;

  /**
   * Empty associative array, here due to PHP quirks.
   *
   * @var array
   *    Empty associative array
   * @since 4.4.0
   */
  public $emptyAttrs;

  /**
   * Callback to determine whether the block should be returned.
   *
   * @var callable
   */
  protected $filterCallable = NULL;

  /**
   * Parses a document and returns a list of block structures.
   *
   * When encountering an invalid parse will return a best-effort
   * parse. In contrast to the specification parser this does not
   * return an error on invalid inputs.
   *
   * @param string $document
   *   Input document being parsed.
   * @param callable|null $filter_callable
   *   A callback function to filter blocks with.
   *   It should return FALSE if the block should be skipped.
   *
   * @return array
   *   The list of Gutenberg blocks.
   *
   * @since 3.8.0
   */
  public function parse($document, callable $filter_callable = NULL) {
    $this->document = $document;
    $this->filterCallable = $filter_callable;
    $this->offset = 0;
    $this->output = [];
    $this->stack = [];
    $this->emptyAttrs = json_decode('{}', TRUE);

    do {
      // Twiddle our thumbs.
    } while ($this->proceed());

    return $this->output;
  }

  /**
   * Processes the next token from the input document.
   *
   * This is the "next step" function that essentially
   * takes a token as its input and decides what to do
   * with that token before descending deeper into a
   * nested block tree or continuing along the document
   * or breaking out of a level of nesting.
   *
   * @return bool
   *   Whether to proceed eating more tokens.
   *
   * @since 3.8.0
   * @internal
   */
  public function proceed() {
    $next_token = $this->nextToken();
    list($token_type, $block_name, $attrs, $start_offset, $token_length) = $next_token;
    $stack_depth = count($this->stack);

    // We may have some HTML soup before the next block.
    $leading_html_start = $start_offset > $this->offset ? $this->offset : NULL;

    switch ($token_type) {
      case 'no-more-tokens':
        // If not in a block then flush output.
        if (0 === $stack_depth) {
          $this->addFreeform();
          return FALSE;
        }

        /*
         * Otherwise we have a problem
         * This is an error
         *
         * we have options
         * - treat it all as freeform text
         * - assume an implicit closer (easiest when not nesting)
         */

        // For the easy case we'll assume an implicit closer.
        if (1 === $stack_depth) {
          $this->addBlockFromStack();
          return FALSE;
        }

        /*
         * for the nested case where it's more difficult we'll
         * have to assume that multiple closers are missing
         * and so we'll collapse the whole stack piecewise
         */
        while (0 < count($this->stack)) {
          $this->addBlockFromStack();
        }
        return FALSE;

      case 'void-block':
        /*
         * easy case is if we stumbled upon a void block
         * in the top-level of the document
         */
        if (0 === $stack_depth) {
          if (isset($leading_html_start)) {
            $this->addToOutput((array) $this->freeform(
              substr(
                $this->document,
                $leading_html_start,
                $start_offset - $leading_html_start
              )
            ));
          }

          $this->addToOutput((array) new BlockParserBlock($block_name, $attrs, [], '', []));
          $this->offset = $start_offset + $token_length;
          return TRUE;
        }

        // Otherwise we found an inner block.
        $this->addInnerBlock(
          new BlockParserBlock($block_name, $attrs, [], '', []),
          $start_offset,
          $token_length
        );
        $this->offset = $start_offset + $token_length;
        return TRUE;

      case 'block-opener':
        // Track all newly-opened blocks on the stack.
        $this->stack[] = new BlockParserFrame(
          new BlockParserBlock($block_name, $attrs, [], '', []),
          $start_offset,
          $token_length,
          $start_offset + $token_length,
          $leading_html_start
        );
        $this->offset = $start_offset + $token_length;
        return TRUE;

      case 'block-closer':
        /*
         * if we're missing an opener we're in trouble
         * This is an error
         */
        if (0 === $stack_depth) {
          /*
           * we have options
           * - assume an implicit opener
           * - assume _this_ is the opener
           * - give up and close out the document
           */
          $this->addFreeform();
          return FALSE;
        }

        // If we're not nesting then this is easy - close the block.
        if (1 === $stack_depth) {
          $this->addBlockFromStack($start_offset);
          $this->offset = $start_offset + $token_length;
          return TRUE;
        }

        /*
         * otherwise we're nested and we have to close out the current
         * block and add it as a new innerBlock to the parent
         */
        $stack_top = array_pop($this->stack);
        $html = substr($this->document, $stack_top->prevOffset, $start_offset - $stack_top->prevOffset);
        $stack_top->block->innerHTML .= $html;
        $stack_top->block->innerContent[] = $html;
        $stack_top->prevOffset = $start_offset + $token_length;

        $this->addInnerBlock(
          $stack_top->block,
          $stack_top->tokenStart,
          $stack_top->tokenLength,
          $start_offset + $token_length
        );
        $this->offset = $start_offset + $token_length;
        return TRUE;

      default:
        // This is an error.
        $this->addFreeform();
        return FALSE;
    }
  }

  /**
   * Scans the document from where we last left off.
   *
   * And finds the next valid token to parse if it exists.
   *
   * @return array
   *   The type of the find: kind of find, block information, attributes.
   *
   * @since 3.8.0
   * @since 4.6.1 fixed a bug in attribute parsing which caused catastrophic backtracking on invalid block comments
   * @internal
   */
  public function nextToken() {
    $matches = NULL;

    /*
     * aye the magic
     * we're using a single RegExp to tokenize the block comment delimiters
     * we're also using a trick here because the only difference between a
     * block opener and a block closer is the leading `/` before `wp:` (and
     * a closer has no attributes). we can trap them both and process the
     * match back in PHP to see which one it was.
     */
    $has_match = preg_match(
      '/<!--\s+(?P<closer>\/)?wp:(?P<namespace>[a-z][a-z0-9_-]*\/)?(?P<name>[a-z][a-z0-9_-]*)\s+(?P<attrs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<void>\/)?-->/s',
      $this->document,
      $matches,
      PREG_OFFSET_CAPTURE,
      $this->offset
    );

    // If we get here we probably have catastrophic backtracking or
    // out-of-memory in the PCRE.
    if (FALSE === $has_match) {
      return ['no-more-tokens', NULL, NULL, NULL, NULL];
    }

    // We have no more tokens.
    if (0 === $has_match) {
      return ['no-more-tokens', NULL, NULL, NULL, NULL];
    }

    list($match, $started_at) = $matches[0];

    $length = strlen($match);
    $is_closer = isset($matches['closer']) && -1 !== $matches['closer'][1];
    $is_void = isset($matches['void']) && -1 !== $matches['void'][1];
    $namespace = $matches['namespace'];
    $namespace = (isset($namespace) && -1 !== $namespace[1]) ? $namespace[0] : 'core/';
    $name = $namespace . $matches['name'][0];
    $has_attrs = isset($matches['attrs']) && -1 !== $matches['attrs'][1];

    /*
     * Fun fact! It's not trivial in PHP to create "an empty associative array"
     * since all arrays are associative arrays. If we use `array()` we get a
     * JSON `[]`
     */
    $attrs = $has_attrs
      ? json_decode($matches['attrs'][0], /* as-associative */ TRUE)
      : $this->emptyAttrs;

    /*
     * This state isn't allowed
     * This is an error
     */
    if ($is_closer && ($is_void || $has_attrs)) {
      // We can ignore them since they don't hurt anything.
    }

    if ($is_void) {
      return ['void-block', $name, $attrs, $started_at, $length];
    }

    if ($is_closer) {
      return ['block-closer', $name, NULL, $started_at, $length];
    }

    return ['block-opener', $name, $attrs, $started_at, $length];
  }

  /**
   * Returns a new block object for freeform HTML.
   *
   * @param string $innerHTML
   *   HTML content of block.
   *
   * @return BlockParserBlock
   *   Freeform block object.
   *
   * @internal
   * @since 3.9.0
   */
  public function freeform($innerHTML) {
    return new BlockParserBlock(NULL, $this->emptyAttrs, [], $innerHTML, [$innerHTML]);
  }

  /**
   * Pushes a length of text from the input document to the output list.
   *
   * As a freeform block.
   *
   * @param int|null $length
   *   How many bytes of document text to output.
   *
   * @since 3.8.0
   * @internal
   */
  public function addFreeform($length = NULL) {
    $length = $length ? $length : strlen($this->document) - $this->offset;

    if (0 === $length) {
      return;
    }

    $this->addToOutput((array) $this->freeform(substr($this->document, $this->offset, $length)));
  }

  /**
   * Given a block structure from memory pushes a new block to the output list.
   *
   * @param BlockParserBlock $block
   *   The block to add to the output.
   * @param int $token_start
   *   Byte offset into the document where the first token for the block starts.
   * @param int $token_length
   *   Byte length of entire block from start of opening token to end of closing
   *   token.
   * @param int|null $last_offset
   *   Last byte offset into document if continuing form earlier output.
   *
   * @internal
   * @since 3.8.0
   */
  public function addInnerBlock(BlockParserBlock $block, $token_start, $token_length, $last_offset = NULL) {
    $parent = $this->stack[count($this->stack) - 1];
    $parent->block->innerBlocks[] = (array) $block;
    $html = substr($this->document, $parent->prevOffset, $token_start - $parent->prevOffset);

    if (!empty($html)) {
      $parent->block->innerHTML .= $html;
      $parent->block->innerContent[] = $html;
    }

    $parent->block->innerContent[] = NULL;
    $parent->prevOffset = $last_offset ? $last_offset : $token_start + $token_length;
  }

  /**
   * Pushes the top block from the parsing stack to the output list.
   *
   * @param int|null $end_offset
   *   Byte offset into document for where we should stop sending
   *   text output as HTML.
   *
   * @since 3.8.0
   * @internal
   */
  public function addBlockFromStack($end_offset = NULL) {
    $stack_top = array_pop($this->stack);
    $prev_offset = $stack_top->prevOffset;

    $html = isset($end_offset)
      ? substr($this->document, $prev_offset, $end_offset - $prev_offset)
      : substr($this->document, $prev_offset);

    if (!empty($html)) {
      $stack_top->block->innerHTML .= $html;
      $stack_top->block->innerContent[] = $html;
    }

    if (isset($stack_top->leadingHtmlStart)) {
      $this->addToOutput((array) $this->freeform(
        substr(
          $this->document,
          $stack_top->leadingHtmlStart,
          $stack_top->tokenStart - $stack_top->leadingHtmlStart
        )
      ));
    }

    $this->addToOutput((array) $stack_top->block);
  }

  /**
   * Add a block to the list of outputs.
   *
   * @param array $block
   *   The block to output.
   */
  protected function addToOutput(array $block) {
    if (isset($this->filterCallable) && is_callable($this->filterCallable)) {
      if (!call_user_func($this->filterCallable, $block)) {
        // Not interested in the block.
        return;
      }
    }
    $this->output[] = $block;
  }

}
