<?php

/**
 * @file
 * Script for generating Gutenberg vendor assets.
 */

$_GUTENBERG_HELP = <<<EOL

Generates Gutenberg vendor scripts.

  Usage:

      {$GLOBALS['argv'][0]} [--force]

  --force   Ensure that the remote tar is re-downloaded even if it was
            previously downloaded.

EOL;
require_once __DIR__ . '/_cli_include.inc.php';

$force_overwrite = !empty($_CLI_OPTIONS['force']);

const JS_NORMAL = 0;
const JS_MIN = 1;
const JS_FOLDER = 2;

$vendor_definitions = [
  'lodash' => [
    'prod' => 'lodash.min.js',
    'dev' => 'lodash.js',
    // Internally 1.8.3.
    'version' => '4.17.11',
    'type' => JS_MIN,
  ],
  'moment' => [
    'version' => '2.22.2',
    'prod' => 'min/moment.min.js',
    'dev' => 'moment.js',
    'type' => JS_MIN,
  ],
  'react' => [
    'version' => '16.9.0',
    'prod' => 'umd/react.production.min.js',
    'dev' => 'umd/react.development.js',
    'type' => JS_MIN,
  ],
  'react-dom' => [
    'version' => '16.9.0',
    'prod' => 'umd/react-dom.production.min.js',
    'dev' => 'umd/react-dom.development.js',
    'type' => JS_MIN,
  ],
  'regenerator-runtime' => [
    'version' => '0.11.1',
    'prod' => 'runtime.js',
    'dev' => 'runtime.js',
    'type' => JS_NORMAL,
  ],
  'sprintf' => [
    'npm' => 'sprintf-js',
    'version' => '1.1.2',
    'prod' => 'dist/sprintf.min.js',
    'remove_sourcemap' => TRUE,
    'dev' => 'src/sprintf.js',
    'type' => JS_MIN,
  ],
  'g-media-attributes' => [
    'npm' => '@frontkom/g-media-attributes',
    'version' => '1.0.2',
    'prod' => 'build/index.js',
    // No dev source.
    'dev' => 'build/index.js',
    'type' => JS_FOLDER,
  ],
];

$temp_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.gutenberg-temp' . DIRECTORY_SEPARATOR . 'drupal-vendors';
$dest_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;

if (!is_dir($temp_dir)) {
  mkdir($temp_dir, 0777, TRUE);
}

$dev_file = FALSE;
$exit_code = 0;

foreach ($vendor_definitions as $vendor => $definition) {
  $npm_package = $definition['npm'] ?? $vendor;
  $npm_version = $definition['version'];
  $remove_sourcemap = $definition['remove_sourcemap'] ?? FALSE;
  $vendor_name = "$npm_package@$npm_version";
  $package = escapeshellarg($vendor_name);

  echo "Processing $vendor...\n";

  $vendor_dest = $temp_dir . DIRECTORY_SEPARATOR . "$vendor@$npm_version";
  $success = FALSE;
  if (!$force_overwrite && is_dir($vendor_dest)) {
    $success = TRUE;
  }
  else {
    if ($dist_url = trim(shell_exec("npm show $package dist.tarball"))) {
      echo "  > Downloading $dist_url\n";
      // https://www.php.net/manual/en/context.http.php#refsect1-context.http-options
      $context = stream_context_create([
        'http' => [
          'method' => 'GET',
          'header' => [
            'Accept: */*',
            'Accept-Encoding: gzip, deflate, br',
            'Cache-Control: no-cache',
          ],
          'timeout' => 5,
          'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36',
        ],
      ]);

      if (copy($dist_url, "$vendor_dest.tgz", $context)) {
        $archive = new \PharData("$vendor_dest.tgz");
        $archive->extractTo($vendor_dest, NULL, TRUE);
        $success = TRUE;
      }
    }
  }
  if ($success) {
    $src_file = $vendor_dest . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . $definition[($dev_file ? 'dev' : 'prod')];
    switch ($definition['type']) {
      case JS_FOLDER:
        $dest_file = $dest_dir . "$vendor/index.js";
        break;

      case JS_NORMAL:
        $dest_file = $dest_dir . "$vendor.js";
        break;

      default:
        $dest_file = $dest_dir . "$vendor.min.js";
        break;
    }
    if ($remove_sourcemap && !$dev_file) {
      // Attempt to remove the sourcemap.
      $fp = fopen($src_file, 'r');
      $out = fopen($dest_file, 'w+');
      while ($line = fgets($fp)) {
        if (substr($line, 0, 21) !== '//# sourceMappingURL=') {
          fwrite($out, $line);
        }
      }
      fclose($fp);
      fclose($out);
    }
    else {
      // Copy file.
      copy($src_file, $dest_file);
    }
  }
  else {
    fwrite(STDERR, "Failed to download $package package!\n");
    $exit_code = 1;
  }

}

exit($exit_code);
