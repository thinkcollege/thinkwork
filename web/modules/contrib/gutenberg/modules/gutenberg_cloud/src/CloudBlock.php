<?php

namespace Drupal\gutenberg_cloud;

/**
 * Class CloudBlock.
 */
class CloudBlock extends CloudBlockBase {

  /**
   * Raw block retrieved from API.
   *
   * @var null
   */
  protected $raw = NULL;

  /**
   * CloudBlock constructor.
   *
   * @param mixed $block
   *   Cloud block structure.
   */
  public function __construct($block) {
    if (is_object($block)) {
      $this->mapFromBlock($block);
    }
    else {
      parent::__construct($block);
    }
  }

  /**
   * Getter for raw block.
   *
   * @return object|null
   *   Block object, defaults to null.
   */
  public function getRaw() {
    return $this->raw;
  }

  /**
   * Defines prefix for accessing the assets.
   */
  public function getPrefix() {
    return $this->name . '@' . $this->version . '/';
  }

  /**
   * Get asset URL.
   *
   * @param string $name
   *   Name of the asset.
   * @param string $base_url
   *   Base Url.
   *
   * @return string|null
   *   Url of the asset or NULL if the asset name
   *   was not allowed or the name value was empty.
   */
  public function getAssetUrl($name = '', $base_url = '') {
    $allowed = [
      'screenshot',
      'js',
      'edit_css',
      'view_css',
    ];
    if (empty($name) || !in_array($name, $allowed)) {
      return NULL;
    };

    return $base_url . $this->getPrefix() . $this->get($name);
  }

  /**
   * Map the API block to CloudBlock properties.
   *
   * @param object $block
   *   Cloud Block from API.
   */
  protected function mapFromBlock(\stdClass $block) {
    $structure = [
      'name' => $block->name,
      'version' => $block->version,
      'label' => $block->config->name,
      'description' => $block->package->description,
      'js' => $block->config->js ?? NULL,
      'screenshot' => $block->config->screenshot ?? NULL,
      'edit_css' => $block->config->editor ?? NULL,
      'view_css' => $block->config->css ?? NULL,
      'raw' => $block,
    ];

    $this->setConfig($structure);
  }

}
