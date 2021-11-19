<?php

namespace Drupal\gutenberg;

/**
 * Class ScanDir.
 */
class ScanDir {

  /**
   * The scanned directories.
   *
   * @var array
   */
  static private $directories;

  /**
   * The list of files.
   *
   * @var array
   */
  static private $files;

  /**
   * Whether a filter was included on file extensions.
   *
   * @var bool|array
   */
  static private $extFilter;

  /**
   * Whether recursive mode is enabled.
   *
   * @var bool
   */
  static private $recursive;

  /**
   * Scans directories.
   *
   * @param string|array $root_dirs
   *   The root directory.
   * @param string|array $extension_filters
   *   The extension filter(s).
   * @param bool $recursive
   *   Whether to scan recursively.
   *
   * @return array
   *   The scan results.
   */
  public static function scan($root_dirs, $extension_filters = NULL, $recursive = FALSE) {
    // Initialize defaults.
    self::$recursive = FALSE;
    self::$directories = [];
    self::$files = [];
    self::$extFilter = FALSE;

    if (!is_string($root_dirs) && !is_array($root_dirs)) {
      throw new \LogicException('Must provide a path string or array of path strings');
    }

    // Check if recursive scan | default action: no sub-directories.
    self::$recursive = $recursive;

    // Was a filter on file extensions included? | default action: return all
    // file types.
    if (isset($extension_filters)) {
      if (is_array($extension_filters)) {
        self::$extFilter = array_map('strtolower', $extension_filters);
      }
      elseif (is_string($extension_filters)) {
        self::$extFilter[] = strtolower($extension_filters);
      }
    }

    // Grab path(s)
    if (is_string($root_dirs)) {
      $root_dirs = [$root_dirs];
    }
    self::verifyPaths($root_dirs);

    return array_map(
      function ($entry) use ($root_dirs) {
        $asset = $entry;
        foreach ($root_dirs as $root_dir) {
          // Strip out the root directory prefix.
          $root_dir .= DIRECTORY_SEPARATOR;
          $root_dir_length = strlen($root_dir);
          if (substr($entry, 0, $root_dir_length) === $root_dir) {
            $asset = substr($entry, $root_dir_length);
            break;
          }
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
          // Replace the directory separator with a forward slash when on Windows.
          $asset = str_replace(DIRECTORY_SEPARATOR, '/', $asset);
        }

        return $asset;
      },
      self::$files
    );
  }

  /**
   * Verifies paths.
   *
   * @param array $paths
   *   Paths to scan.
   */
  private static function verifyPaths(array $paths) {
    $path_errors = [];

    foreach ($paths as $path) {
      if (is_dir($path)) {
        self::$directories[] = $path;
        self::findContents($path);
      }
      else {
        $path_errors[] = $path;
      }
    }

    if ($path_errors) {
      throw new \RuntimeException("The following directories do not exists\n" . var_export($path_errors, TRUE));
    }
  }

  /**
   * This is how we scan directories.
   */
  private static function findContents($dir) {
    $result = [];
    $root = scandir($dir);
    foreach ($root as $value) {
      if ($value === '.' || $value === '..') {
        continue;
      }
      if (is_file($dir . DIRECTORY_SEPARATOR . $value)) {
        if (!self::$extFilter || in_array(strtolower(pathinfo($dir . DIRECTORY_SEPARATOR . $value, PATHINFO_EXTENSION)), self::$extFilter)) {
          self::$files[] = $result[] = $dir . DIRECTORY_SEPARATOR . $value;
        }
        continue;
      }
      if (self::$recursive) {
        foreach (self::findContents($dir . DIRECTORY_SEPARATOR . $value) as $entry) {
          self::$files[] = $result[] = $entry;
        }
      }
    }

    // Return required for recursive search.
    return $result;
  }

}
