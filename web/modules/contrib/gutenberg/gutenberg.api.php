<?php

/**
 * @file
 * Documentation for Gutenberg module APIs.
 */

use Drupal\Core\Entity\Query\Sql\Query;
use Symfony\Component\HttpFoundation\Request;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations to a gutenberg's media (file entity) search query.
 *
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The request.
 * @param string $type
 *   MIME type search string.
 * @param string $search
 *   Filename search string.
 * @param \Drupal\Core\Entity\Query\Sql\Query $query
 *   Entity query object.
 */
function hook_gutenberg_media_search_query_alter(Request $request, string $type, string $search, Query $query) {
  if ($type === 'image') {
    $query->condition('uri', 'public://avatars/%', 'NOT LIKE');
  }

  // Load at most 100 media entities at a time.
  $query->range(0, 100);
}

/**
 * DEPRECATED.
 *
 * You can use Drupal libraries. Check gutenberg.libraries.yml for an example.
 * Modify the list of CSS and JS files for blocks.
 *
 * @param array $js_files_edit
 *   An array of all js files to be included on the editor.
 * @param array $css_files_edit
 *   An array of all css files to be included on the editor.
 * @param array $css_files_view
 *   An array of all css files to be included on the node view.
 */
function hook_gutenberg_blocks_alter(array &$js_files_edit, array &$css_files_edit, array &$css_files_view) {
  $js_files_edit[] = '/path/to/js/files';
  $css_files_edit[] = '/path/to/css/files';
  $css_files_view[] = '/path/to/css/files';
}

/**
 * Alter render array of Gutenberg Media Library dialog.
 *
 * @param array $build_ui
 *   Build array of media library dialog.
 *
 * @see \Drupal\gutenberg\GutenbergMediaLibraryUiBuilder
 */
function hook_gutenberg_media_library_view_alter(array &$build_ui) {
  // @todo: provide some example.
}

/**
 * Perform alterations on gutenberg definitions.
 *
 * @param array $info
 *   Array of information on gutenberg definitions exposed by gutenberg
 *   module/themes.
 *
 * @see \Drupal\gutenberg\GutenbergLibraryManager
 *
 * @ingroup gutenberg_api
 */
function hook_gutenberg_info_alter(array &$info) {
  if (isset($info['example_block'])) {
    // Remove a specific example_block's front-end library definition.
    if (($key = array_search('example_block/block-view', $info['example_block']['libraries-view'], TRUE)) !== FALSE) {
      unset($info['example_block']['libraries-view'][$key]);
    }
  }
}

/**
 * Alter the result of \Drupal\gutenberg\BlockProcessor\DynamicRenderProcessor.
 *
 * This hook is called after the block has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * block content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the block rather than
 * the structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement hook_preprocess_HOOK() for
 * gutenberg-block.html.twig. See drupal_render() documentation or the
 * @link themeable Default theme implementations topic @endlink for details.
 *
 * @param array &$build
 *   A renderable array of the block.
 * @param string $block_content
 *   The block's inner HTML.
 *
 * @see hook_gutenberg_block_view_BASE_BLOCK_ID_alter()
 * @see \Drupal\gutenberg\BlockProcessor\DynamicRenderProcessor::processBlock()
 *
 * @ingroup gutenberg_api
 */
function hook_gutenberg_block_view_alter(array &$build, &$block_content) {
  // Add generic pre_render hook for all dynamic blocks.
  $build['#pre_render'][] = 'hook_gutenberg_pre_render';
}

/**
 * Provide a block plugin specific gutenberg_block_view alteration.
 *
 * In this hook name, BASE_BLOCK_ID refers to the Gutenberg block's name/ID.
 * For example, 'my-plugin/block-name' will have a BASE_BLOCK_ID of
 * 'my_plugin_block_name'.
 *
 * @param array $build
 *   A renderable array of the block.
 * @param string $block_content
 *   The block's inner HTML.
 *
 * @see hook_gutenberg_block_view_alter()
 *
 * @ingroup gutenberg_api
 */
function hook_gutenberg_block_view_BASE_BLOCK_ID_alter(array &$build, &$block_content) {
  // Add block specific pre_render hook.
  $build['#pre_render'][] = 'hook_gutenberg_BASE_BLOCK_ID_pre_render';
}

/**
 * @} End of "addtogroup hooks".
 */
