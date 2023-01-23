#!/usr/bin/env php
<?php

use Drupal\gutenberg\ScanDir;

$block_library_dir = realpath(dirname(__DIR__) . '/vendor/gutenberg/block-library');
$translations_file = __DIR__ . '/../js/core-blocks-translations.js';


require_once __DIR__ . '/utils.inc.php';
require_once get_drupal_root_directory() . '/autoload.php';
require_once __DIR__ . '/../src/ScanDir.php';

$json_files = ScanDir::scan([$block_library_dir], ['json'], TRUE);

$output = "/**\n";
foreach ($json_files as $file_name) {
  $contents = file_get_contents("{$block_library_dir}/{$file_name}", TRUE);
  $block = json_decode($contents);
  $output .= "  Drupal.t(\"{$block->title}\", {}, {context: \"block title\"});\n";
  $output .= "  Drupal.t(\"{$block->description}\", {}, {context: \"block description\"});\n";
}
$output .= "*/\n";

file_put_contents($translations_file, $output);
