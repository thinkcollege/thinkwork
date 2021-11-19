<?php

namespace Drupal\gutenberg;

use Drupal\Core\Url;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;

/**
 * Class GutenbergMediaLibraryUiBuilder.
 *
 * This is temporary fix for the issue that causes problem with file upload
 * on initial media library dialog render.
 *
 * @package Drupal\gutenberg
 */
class GutenbergMediaLibraryUiBuilder extends MediaLibraryUiBuilder {

  /**
   * {@inheritDoc}
   */
  protected function buildMediaTypeMenu(MediaLibraryState $state) {
    // Add the menu for each type if we have more than 1 media type enabled for
    // the field.
    $allowed_type_ids = $state->getAllowedTypeIds();

    // @todo: this is temporary fix that allows for rendering at least one tab
    // to make it clickable on frontend side by Gutenberg.
    if (!$allowed_type_ids) {
      return [];
    }

    // @todo: Add a class to the li element.
    // https://www.drupal.org/project/drupal/issues/3029227
    $menu = [
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => [
        'class' => ['media-library-menu', 'js-media-library-menu'],
      ],
    ];

    $allowed_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple($allowed_type_ids);

    $selected_type_id = $state->getSelectedTypeId();
    foreach ($allowed_types as $allowed_type_id => $allowed_type) {
      $link_state = MediaLibraryState::create($state->getOpenerId(), $state->getAllowedTypeIds(), $allowed_type_id, $state->getAvailableSlots(), $state->getOpenerParameters());
      // Add the 'media_library_content' parameter so the response will contain
      // only the updated content for the tab.
      // @see self::buildUi()
      $link_state->set('media_library_content', 1);

      $title = $allowed_type->label();
      $display_title = [
        '#markup' => $this->t('<span class="visually-hidden">Show </span>@title<span class="visually-hidden"> media</span>', ['@title' => $title]),
      ];
      if ($allowed_type_id === $selected_type_id) {
        $display_title = [
          '#markup' => $this->t('<span class="visually-hidden">Show </span>@title<span class="visually-hidden"> media</span><span class="active-tab visually-hidden"> (selected)</span>', ['@title' => $title]),
        ];
      }

      $menu['#links']['media-library-menu-' . $allowed_type_id] = [
        'title' => $display_title,
        'url' => Url::fromRoute('media_library.ui', [], [
          'query' => $link_state->all(),
        ]),
        'attributes' => [
          'class' => ['media-library-menu__link'],
          'role' => 'button',
          'data-title' => $title,
        ],
      ];
    }

    // Set the active menu item.
    $menu['#links']['media-library-menu-' . $selected_type_id]['attributes']['class'][] = 'active';

    return $menu;
  }

}
