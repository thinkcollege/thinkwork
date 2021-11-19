<?php

namespace Drupal\gutenberg_cloud;

/**
 * Interface for cloud block classes.
 */
interface CloudBlockInterface {

  /**
   * Getter for name.
   */
  public function getName();

  /**
   * Getter for version.
   */
  public function getVersion();

  /**
   * Getter for label.
   */
  public function getLabel();

  /**
   * Getter for description.
   */
  public function getDescription();

  /**
   * Getter for screenshot.
   */
  public function getScreenshot();

  /**
   * Getter for js.
   */
  public function getJs();

  /**
   * Getter for edit css.
   */
  public function getEditCss();

  /**
   * Getter for view css.
   */
  public function getViewCss();

  /**
   * Getter for object properties.
   *
   * @param string $property
   *   Property name.
   *
   * @return mixed|null
   *   Properety value or NULL if property doesn't exists.
   */
  public function get(string $property);

  /**
   * Getter for properties related to Cloud Block config.
   *
   * @return array
   *   Array of properties.
   */
  public function getConfig();

}
