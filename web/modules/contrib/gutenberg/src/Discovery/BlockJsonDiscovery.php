<?php

namespace Drupal\gutenberg\Discovery;

use Drupal\Component\Discovery\DiscoverableInterface;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Json;

/**
 * Provides discovery for JSON files within a given set of directories.
 */
class BlockJsonDiscovery implements DiscoverableInterface {

  /**
   * An array of directories to scan, keyed by the provider.
   *
   * @var array
   */
  protected $directories = [];

  /**
   * Constructs a JsonDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider.
   */
  public function __construct(array $directories) {
    $this->directories = $directories;
  }

  /**
   * {@inheritdoc}
   */
  public function findAll() {
    $all = [];

    $providers = $this->findFiles();
    $provider_by_files = [];

    foreach ($providers as $provider => $files) {
      $provider_by_files += array_flip($files);
    }

    $file_cache = FileCacheFactory::get('block_json_discovery_cache');

    // Try to load from the file cache first.
    foreach ($providers as $provider => $files) {
      foreach ($file_cache->getMultiple($files) as $file => $data) {
        $all[$provider_by_files[$file]] = $data;
        unset($provider_by_files[$file]);
      }
    }

    // If there are files left that were not returned from the cache, load and
    // parse them now. This list was flipped above and is keyed by filename.
    if ($provider_by_files) {
      foreach ($provider_by_files as $file => $key) {
        // If a file is empty or its contents are commented out,
        // return an empty.
        // array instead of NULL for type consistency.
        $all[$key] = $this->decode($file);
        $file_cache->set($file, $all[$key]);
      }
    }

    return $all;
  }

  /**
   * Decode a JSON file.
   *
   * @param string $file
   *   Json file path.
   *
   * @return array
   */
  protected function decode($file) {
    try {
      return Json::decode(file_get_contents($file)) ?: [];
    }
    catch (InvalidDataTypeException $e) {
      throw new InvalidDataTypeException($file . ': ' . $e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Returns an array of file paths, keyed by provider.
   *
   * @return array
   */
  protected function findFiles() {
    $fileSystem = \Drupal::service('file_system');
    $files = [];
    foreach ($this->directories as $provider => $directory) {
      $dirs = $fileSystem->scanDirectory($directory, '/^block\.json$/i', []);

      foreach ($dirs as $path => $file) {
        $files[$provider][] = $path;
      }
    }
    return $files;
  }

}
