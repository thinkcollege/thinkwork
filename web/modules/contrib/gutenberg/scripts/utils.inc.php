<?php
/**
 * Gets the Drupal root directory.
 *
 * @return string
 *   The root directory.
 */
function get_drupal_root_directory() {
  $dirs = explode(DIRECTORY_SEPARATOR, __DIR__);

  $root_dir = [];
  foreach ($dirs as $key => $value) {
    if ($value === 'modules') {
      return implode(DIRECTORY_SEPARATOR, $root_dir);
    }
    $root_dir[] = $value;
  }
  throw new \RuntimeException('Could not find the Drupal root.');
}
