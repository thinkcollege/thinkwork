<?php

namespace Drupal\gutenberg_cloud;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class CloudBlockManager.
 */
class CloudBlockManager {

  /**
   * Gutenberg cloud API URL.
   */
  const CLOUD_BLOCK_API_URL = 'https://api.gutenbergcloud.org/blocks';

  /**
   * Gutenberg cloud CDN URL.
   */
  const CLOUD_BLOCK_CDN_URL = 'https://cdn.jsdelivr.net/npm/';

  /**
   * The http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Storage for remote blocks.
   *
   * @var array
   */
  protected $blocks = [];

  /**
   * CloudBlockManager constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Http client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory service.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config) {
    $this->httpClient = $http_client;
    $this->config = $config;
  }

  /**
   * Getter for Gutenberg Cloud Blocks config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Config.
   */
  public function getConfig() {
    return $this->config->get('gutenberg_cloud.blocks');
  }

  /**
   * Getter for API url.
   *
   * @return string
   *   API url.
   */
  public function getApiUrl() {
    return self::CLOUD_BLOCK_API_URL;
  }

  /**
   * Getter for CDN url.
   *
   * @return string
   *   CDN url.
   */
  public function getCdnUrl() {
    return self::CLOUD_BLOCK_CDN_URL;
  }

  /**
   * Load available blocks from Gutenberg API.
   *
   * The blocks are stored in variable, to avoid http calls on each
   * method request.
   *
   * @return array
   *   Array of CloudBlock instances or empty array if no results.
   */
  public function loadAllFromRemote() {
    $blocks = $this->blocks;

    if (!empty($this->blocks)) {
      return $blocks;
    }

    try {
      $request = $this->httpClient->get($this->getApiUrl());
      $blocksToLoad = json_decode($request->getBody())->rows;

      foreach ($blocksToLoad as $block) {
        $cloudBlock = new CloudBlock($block);
        $blocks[$cloudBlock->getName()] = $cloudBlock;
      }
    }
    catch (\Exception $e) {
      $blocks = [];
    }

    return $blocks;
  }

  /**
   * Load single block from cloud.
   *
   * @param \Drupal\gutenberg_cloud\string $name
   *   Block name.
   *
   * @return \Drupal\gutenberg_cloud\CloudBlock|null
   *   Instance of Cloud Block or null.
   */
  public function loadRemote(string $name) {
    $blocks = $this->loadAllFromRemote();
    return $blocks[$name] ?? NULL;
  }

  /**
   * Load installed blocks, saved in the config.
   *
   * @return array
   *   Array of CloudBlock instances or empty array if no results.
   */
  public function loadInstalledBlocks() {
    $blocks = [];
    $localBlocks = $this->getConfig()->get();

    foreach ($localBlocks as $block) {
      $cloudBlock = new CloudBlock($block);
      $blocks[$cloudBlock->getName()] = $cloudBlock;
    }

    return $blocks;
  }

  /**
   * Check if the block is enabled locally.
   *
   * @param \Drupal\gutenberg_cloud\CloudBlock $block
   *   CloudBlock instance.
   *
   * @return bool
   *   True if block has been enabled.
   */
  public function isBlockEnabled(CloudBlock $block) {
    $localBlocks = $this->loadInstalledBlocks();

    return in_array($block->getName(), array_keys($localBlocks));
  }

  /**
   * Compare remote and local version of block.
   *
   * @param \Drupal\gutenberg_cloud\CloudBlock $block
   *   CloudBlock instance.
   *
   * @return bool
   *   True if the local version is older or the same.
   */
  public function hasUpdates(CloudBlock $block) {
    $localBlocks = $this->loadInstalledBlocks();
    $localBlock = $localBlocks[$block->getName()] ?? NULL;

    if (empty($localBlocks)) {
      return FALSE;
    }

    return $block->getVersion() > $localBlock->getVersion();
  }

}
