Gutenberg 8.x-2.x-dev, xxxx-xx-xx
--------------------------------------------------
- Update to Gutenberg 8.4.0.
- Move library loading from `gutenberg_preprocess_node` to `GutenbergFilter` so that it works on the field level.
- Issue #3152053: Use Block parser for the `mappingFields` integration.
  Add optional processing options to the definition:
    * `text_format` - The text format to use for text fields.
    * `allowed_tags` - Array of HTML tags that should not be stripped.
    * `no_strip` - specifies that `strip_tags` should not be applied on the value.
- Remove the `MappingFieldsFilter` configuration since that feature should always be enabled. Remove `mappingField` attribute support.
- Issue #3104989: Set minimum PHP support to 7.0 rather than 7.1
- API: Use the WordPress Gutenberg block parser so that blocks are easier to work with.
- API: Add `gutenberg_block_processor` tag services which act upon the blocks in a single-pass (ordered by priority).
- API: Integrate the `drupalmedia` block with the dynamic render block API so that it can be extended by other themes/modules.
- API: Use a `GutenbergLibraryManager` Plugin Manager to manage all the `.gutenberg.yml` definitions to improve performance.
- Inherit Gutenberg theme library definitions from their base themes.
- Update `gutenberg.schema.yml` to include the configuration schemas.
- Add dynamic block functionality as well as a demo of the API in the `example_block` module.
- Replace the `gutenberg-palette.html.twig` template with a procedural version - removing the extra rendering overhead (also minified the output).
  - Add a `theme-includes-colors` property for theme `.gutenberg.yml` definitions to indicate that the theme already supplies its own palette styles - removing the need for head styles.
- Removed `\Drupal\gutenberg\BlocksRendererHelper::isAccessForbidden` in favour of `\Drupal\gutenberg\BlocksRendererHelper::getBlockAccess` so that the cache tags can be referenced.
- `GutenbergFilter` now replaces the previous Gutenberg filters (database update is required):
    - `BlockFilter`
    - `CommentDelimiterFilter`
    - `FieldMappingFilter`
    - `MappingFieldsFilter`
    - `MediaEntityBlockFilter`
    - `OEmbedFilter`
    - `ReusableBlockFilter`
- Add a text formatter for rendering a text through Gutenberg. This allows Reusable blocks to render dynamic blocks.
  - It removes the default Drupal field wrappers by default to avoid conflicting element styles.
- oEmbed processor now uses the Media module's resolver if available, then falls back to the internal implementation.
- Flexible theme attributes support. [#3089943](https://www.drupal.org/project/gutenberg/issues/3089943)
- Add RTL editor support.
- Replace `drupalSettings.path.baseUrl` calls with `Drupal.url()` calls to support sites with url prefixes.
- `gutenberg.routing.yml`: Enforce the JSON response `_format` and update deprecated `_method` property.
- Add support for custom media types. [#3107837](https://www.drupal.org/project/gutenberg/issues/3107837)
- Media controller:
    - Throw appropriate http response codes when relevant.
    - `MediaController::dialog`: Fix bug relating to explode an array instead of a string when the `types` query is empty.
- Reusable Blocks controller:
    - Address null pointer exception when a block with empty content or title is saved.
    - Ensure that only reusable block entities are loaded and modified.
- Search controller: Add ability to limit results per page (defaults to 20).
- `BlocksRendererHelper`:
    - Apply context to context aware blocks.
    - Log plugin exceptions when they occur.
- Add scripts for simplifying updating/switching the Gutenberg JS versions.
