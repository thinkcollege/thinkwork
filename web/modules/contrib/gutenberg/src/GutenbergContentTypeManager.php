<?php

namespace Drupal\gutenberg;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Manager for Gutenberg content types.
 *
 * @package Drupal\gutenberg
 */
class GutenbergContentTypeManager {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new GutenbergEntityTypeManager.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Check if content type supports Gutenberg.
   *
   * @param string|null $content_type
   *   The content type.
   *
   * @return bool|null
   *   True or false or null
   */
  public function isContentTypeSupported($content_type = NULL) {
    $config = $this->configFactory->getEditable('gutenberg.settings');
    $gutenberg_enabled = $config->get($content_type . '_enable_full');

    return $gutenberg_enabled;
  }

  /**
   * {@inheritdoc}
   *
   * @return mixed[]
   *   The fields.
   */
  public function getGutenbergCompatibleFields(string $content_type) {
    $compatible_fields = [];
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $content_type);
    foreach ($field_definitions as $name => $definition) {
      if ($definition && !$definition->isComputed()) {
        $storage_definition = $definition->getFieldStorageDefinition();
        $supported_types = ['text', 'text_long', 'text_with_summary'];
        if (
          $storage_definition &&
          $storage_definition->getCardinality() === 1 &&
          in_array($storage_definition->getType(), $supported_types)
        ) {
          $compatible_fields[$name] = $definition->getLabel();
        }
      }
    }
    return $compatible_fields;
  }

  /**
   * {@inheritdoc}
   *
   * @return string|null
   *   Content type or null.
   */
  public function getGutenbergNodeTypeFromRoute(RouteMatchInterface $route_match) {
    /** @var \Drupal\node\Entity\Node|null $node */
    $node = $route_match->getParameter('node');
    /** @var \Drupal\node\Entity\NodeType|null $node_type */
    $node_type = $route_match->getParameter('node_type');

    // Do we have a node defined on the route?
    if ($node) {
      // Then just return its type.
      return $node->getType();
    }

    // Do we have a content type defined on the route?
    if ($node_type) {
      // Then just return it.
      return $node_type->get('type');
    }

    // Otherwise, invoke all hooks to resolve the content type.
    $hook = 'gutenberg_node_type_route';
    $this->moduleHandler->invokeAll($hook, [$route_match]);

    // No content type found.
    return NULL;
  }

}
