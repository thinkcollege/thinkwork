<?php

namespace Drupal\tagify_user_list;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;

/**
 * Matcher class to get autocompletion results for entity reference.
 */
class TagifyUserListEntityAutocompleteMatcher implements TagifyUserListEntityAutocompleteMatcherInterface {

  /**
   * The entity reference selection handler plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TagifyEntityAutocompleteMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The entity reference selection handler plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->selectionManager = $selection_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets matched labels based on a given search string.
   *
   * @param string $target_type
   *   The ID of the target entity type.
   * @param string $selection_handler
   *   The plugin ID of the entity reference selection handler.
   * @param array $selection_settings
   *   An array of settings that will be passed to the selection handler.
   * @param string $string
   *   (optional) The label of the entity to query by.
   * @param array $selected
   *   Am array of selected values.
   *
   * @return array
   *   An array of matched entity labels, in the format required by the AJAX
   *   autocomplete API (e.g. array('value' => $value, 'label' => $label)).
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the current user doesn't have access to the specified entity.
   *
   * @see \Drupal\system\Controller\EntityAutocompleteController
   */
  public function getUserMatches($target_type, $selection_handler, array $selection_settings, $string = '', array $selected = []) {
    $matches = [];

    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $match_limit = isset($selection_settings['match_limit']) ? (int) $selection_settings['match_limit'] : 10;
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $match_limit + count($selected));

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $bundle => $values) {

        foreach ($values as $entity_id => $label) {

          // Filter out already selected items.
          if (in_array($entity_id, $selected)) {
            continue;
          }

          $matches[$entity_id] = $this->buildTagifyUserListItem($entity_id, $label, $bundle);
        }
      }

      if ($match_limit > 0) {
        $matches = array_slice($matches, 0, $match_limit, TRUE);
      }

      $this->moduleHandler->alter('tagify_user_list_autocomplete_matches', $matches, $options);
    }

    return array_values($matches);
  }

  /**
   * Builds the array that represents the entity in the tagify autocomplete.
   *
   * @return array
   *   The tagify item array. Associative array with the following keys:
   *   - 'entity_id':
   *     The referenced entity ID.
   *   - 'label':
   *     The text to be shown in hte autocomplete and tagify, IE: "My label"
   *   - 'type':
   *     The type of the entity being represented., IE: tags
   *   - 'attributes':
   *     A key-value array of extra properties sent directly to tagify, IE:
   *     ['--tag-bg' => '#FABADA']
   */
  protected function buildTagifyUserListItem($entity_id, $label, $bundle): array {
    $entity = $this->entityTypeManager->getStorage($bundle)->load($entity_id);
    // Get image path.
    $image_url = '';
    if ($entity
      && $entity->hasField('user_picture')
      && !$entity->get('user_picture')->isEmpty()
    ) {
      $user_image = $entity->get('user_picture')->entity;
      $image_style = 'thumbnail';
      $style = ImageStyle::load($image_style);
      $image_url = $style
        ? $style->buildUrl($user_image->getFileUri())
        : '';
    }
    $tagify_user_list_path = \Drupal::service('extension.list.module')->getPath('tagify_user_list');
    $url_options = [
      'absolute' => TRUE,
      'language' => \Drupal::languageManager()->getCurrentLanguage(),
    ];
    $site_url = Url::fromRoute('<front>', [], $url_options)->toString();

    return [
      'entity_id' => $entity_id,
      'label' => Html::decodeEntities($label),
      'type' => $bundle,
      'avatar' => $image_url ?: $site_url . $tagify_user_list_path . '/images/no-user.svg',
      'email' => is_null($entity->getEmail()) ? '' : $entity->getEmail(),
    ];
  }

}
