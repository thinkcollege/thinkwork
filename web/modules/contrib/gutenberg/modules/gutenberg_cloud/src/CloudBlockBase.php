<?php

namespace Drupal\gutenberg_cloud;

/**
 * Class CloudBlockBase.
 */
class CloudBlockBase implements CloudBlockInterface {

  /**
   * Block machine name.
   *
   * @var string
   */
  protected $name;

  /**
   * Block version.
   *
   * @var string
   */
  protected $version;

  /**
   * Block label (human readable name).
   *
   * @var string
   */
  protected $label = '';

  /**
   * Block description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Block screenshot.
   *
   * @var null
   */
  protected $screenshot = NULL;

  /**
   * Block js.
   *
   * @var null
   */
  protected $js = NULL;

  /**
   * Block edit css.
   *
   * @var null
   */
  protected $edit_css = NULL;

  /**
   * Block view css.
   *
   * @var null
   */
  protected $view_css = NULL;

  /**
   * CloudBlockBase constructor.
   *
   * @param array $block
   *   Cloud block structure.
   */
  public function __construct(array $block) {
    $this->setConfig($block);
  }

  /**
   * Getter for name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Getter for version.
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * Getter for label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Getter for description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Getter for screenshot.
   */
  public function getScreenshot() {
    return $this->screenshot;
  }

  /**
   * Getter for js.
   */
  public function getJs() {
    return $this->js;
  }

  /**
   * Getter for edit css.
   */
  public function getEditCss() {
    return $this->edit_css;
  }

  /**
   * Getter for view css.
   */
  public function getViewCss() {
    return $this->view_css;
  }

  /**
   * Getter for object properties.
   *
   * @param string $property
   *   Property name.
   *
   * @return mixed|null
   *   Properety value or NULL if property doesn't exists.
   */
  public function get(string $property) {
    return $this->getConfig()[$property] ?? NULL;
  }

  /**
   * Getter for properties related to Cloud Block config.
   *
   * @return array
   *   Array of properties.
   */
  public function getConfig() {
    return [
      'name' => $this->name,
      'version' => $this->version,
      'label' => $this->label,
      'description' => $this->description,
      'screenshot' => $this->screenshot,
      'js' => $this->js,
      'edit_css' => $this->edit_css,
      'view_css' => $this->view_css,
    ];
  }

  /**
   * Sets object properties based on a given array.
   *
   * @param array $config
   *   Associative array.
   */
  protected function setConfig(array $config) {
    foreach ($config as $property => $value) {
      if (property_exists($this, $property)) {
        $this->{$property} = $value;
      }
    }
  }

}
