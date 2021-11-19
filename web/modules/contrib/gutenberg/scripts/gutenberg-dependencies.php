#!/usr/bin/env php
<?php

/**
 * @file
 * Gets Gutenberg dependencies.
 */

use Symfony\Component\Yaml\Yaml;
use Drupal\gutenberg\ScanDir;

$_GUTENBERG_HELP = <<<EOL

Generates Gutenberg's info.yml library dependencies.

EOL;
require_once __DIR__ . '/_cli_include.inc.php';

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

require_once get_drupal_root_directory() . '/autoload.php';
// Could require bootstrap but maybe it's a "overkill"...?
require_once __DIR__ . '/../src/ScanDir.php';

$gutenberg_vendor_dir = realpath(dirname(__DIR__) . '/vendor/gutenberg');
$gutenberg_libraries_file = __DIR__ . '/../gutenberg.libraries.yml';

$whitelisted_libraries = [
  // Global/3rd party libraries.
  'react', 'react-dom', 'lodash', 'moment', 'sprintf', 'regenerator-runtime', 'polyfill', 'g-media-attributes',
  // Drupal Gutenberg libraries.
  'admin', 'bartik', 'seven', 'claro', 'filters', 'drupal-blocks', 'blocks-edit', 'blocks-view', 'init', 'edit-node', 'drupal-url',
  'drupal-api-fetch', 'drupal-data', 'drupal-i18n', 'special-media-selection',
];
$ignore_dirs = ['edit-site', 'edit-navigation', 'edit-widgets'];
$original_yaml = Yaml::parse(file_get_contents($gutenberg_libraries_file));
$yaml = [];
// Keep only the whitelisted libraries, anything else will be generated and picked up from the Gutenberg dependency.
// This is required when switching between Gutenberg JS versions.
foreach ($whitelisted_libraries as $whitelisted_library) {
  if (isset($original_yaml[$whitelisted_library])) {
    $yaml[$whitelisted_library] = $original_yaml[$whitelisted_library];
  }
}
$directories = scandir($gutenberg_vendor_dir);
$directories = array_diff($directories, $ignore_dirs);

$packages = [];

foreach ($directories as $directory) {
  if ($directory !== NULL && isset($directory[0]) && $directory[0] !== '.') {
    $packages[] = $directory;
  }
}

foreach ($packages as $package) {
  unset($yaml[$package]);

  $package_settings = require $gutenberg_vendor_dir . DIRECTORY_SEPARATOR . $package . DIRECTORY_SEPARATOR . 'index.asset.php';
  $deps = $package_settings['dependencies'];
  $version = $package_settings['version'];

  $asset_prefix = "vendor/gutenberg/$package/";
  $js_files = ScanDir::scan($gutenberg_vendor_dir . DIRECTORY_SEPARATOR . $package, 'js');
  $css_files = ScanDir::scan($gutenberg_vendor_dir . DIRECTORY_SEPARATOR . $package, 'css');

  $yaml[$package] = [];
  // $yaml[$package]['version'] = "\'{$version}\'";
  $yaml[$package]['js'] = [];
  foreach ($js_files as $file) {
    $yaml[$package]['js'][$asset_prefix . $file] = [];
  }

  $yaml[$package]['css'] = ['theme' => []];
  foreach ($css_files as $file) {
    if (!strpos($file, '-rtl')) {
      $yaml[$package]['css']['theme'][$asset_prefix . $file] = [];
    }
  }

  foreach ($deps as $dep) {
    $dep = str_replace('wp-', '', $dep);
    $yaml[$package]['dependencies'][] = 'gutenberg/' . $dep;
  }
}

// Customize editor package sources.
if (isset($yaml['editor'])) {
  unset($yaml['editor']['css']['theme']['vendor/gutenberg/editor/editor-styles.css']);
}

// Customize i18n package sources.
if (isset($yaml['i18n'])) {
  $yaml['i18n']['js'] = [
    'js/i18n.js' => [],
    'js/drupal-gutenberg-translations.js' => [],
  ];
  $yaml['i18n']['dependencies'][] = 'gutenberg/sprintf';
}

file_put_contents($gutenberg_libraries_file, Yaml::dump($yaml, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
