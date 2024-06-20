<?php

namespace Drupal\gutenberg\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\gutenberg\BlockProcessor\GutenbergConfigurableBlockProcessorInterface;
use Drupal\gutenberg\BlockProcessor\GutenbergBlockProcessorManager;
use Drupal\gutenberg\GutenbergLibraryManagerInterface;
use Drupal\gutenberg\Parser\BlockParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for Gutenberg blocks.
 *
 * @Filter(
 *   id = "gutenberg",
 *   title = @Translation("Gutenberg"),
 *   description = @Translation("Compulsory filter in order to work with Gutenberg formats."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *    "processor_settings" = {},
 *   }
 * )
 */
class GutenbergFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The Gutenberg block processor manager.
   *
   * @var \Drupal\gutenberg\BlockProcessor\GutenbergBlockProcessorManager
   */
  protected $blockProcessorManager;

  /**
   * The Gutenberg library manager.
   *
   * @var \Drupal\gutenberg\GutenbergLibraryManagerInterface
   */
  protected $libraryManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a GutenbergFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\gutenberg\BlockProcessor\GutenbergBlockProcessorManager $block_processor_manager
   *   The block processor manager instance.
   * @param \Drupal\gutenberg\GutenbergLibraryManagerInterface $library_manager
   *   The library manager instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GutenbergBlockProcessorManager $block_processor_manager,
    GutenbergLibraryManagerInterface $library_manager,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockProcessorManager = $block_processor_manager;
    $this->libraryManager = $library_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('gutenberg.block_processor_manager'),
      $container->get('plugin.manager.gutenberg.library'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see https://github.com/WordPress/gutenberg/blob/master/post-content.php
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    $this->setProviderSettings();

    // Use a bubbleable metadata.
    $bubbleable_metadata = new BubbleableMetadata();

    $block_parser = new BlockParser();
    $blocks = $block_parser->parse($text);

    $output = '';
    foreach ($blocks as $block) {
      $output .= $this->renderBlock($block, $bubbleable_metadata);
    }

    $result->setProcessedText($output);

    // Add the module/theme libraries.
    $this->addAttachments($result);

    // Add the processed blocks bubbleable metadata.
    $result->addCacheableDependency($bubbleable_metadata);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $processors_settings = [];

    $this->setProviderSettings();

    $processors = $this->blockProcessorManager->getSortedProcessors();
    foreach ($processors as $processor) {
      if ($processor instanceof GutenbergConfigurableBlockProcessorInterface) {
        $processors_settings += $processor->provideSettings($form, $form_state);
      }
    }

    if ($processors_settings) {
      $form['processor_settings'] = $processors_settings;

      return $form;
    }

    // Empty array to signify that there is no configuration.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    $processors = $this->blockProcessorManager->getSortedProcessors();
    foreach ($processors as $processor) {
      if ($processor instanceof GutenbergConfigurableBlockProcessorInterface) {
        $default_configuration['settings']['processor_settings'] += $processor->defaultConfiguration();
      }
    }

    return $default_configuration;
  }

  /**
   * Render a Gutenberg block.
   *
   * @param array $block
   *   The Gutenberg block.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return string
   *   The block content.
   */
  protected function renderBlock(array $block, RefinableCacheableDependencyInterface $bubbleable_metadata) {
    $index = 0;
    $block_content = '';

    foreach ($block['innerContent'] as $chunk) {
      if (is_string($chunk)) {
        $block_content .= $chunk;
      }
      else {
        $block_content .= $this->renderBlock($block['innerBlocks'][$index++], $bubbleable_metadata);
      }
    }

    $processors = $this->blockProcessorManager->getSortedProcessors();
    foreach ($processors as $processor) {
      if ($processor->isSupported($block, $block_content)) {
        $result = $processor->processBlock($block, $block_content, $bubbleable_metadata);
        if ($result === FALSE) {
          // Stop further processing of the block.
          break;
        }
      }
    }

    // Restore the inner container for the group block.
    if ($block['blockName'] === 'core/group') {
      $block_content = $this->restoreGroupInnerContainer($block, $block_content);
    }

    return $block_content;
  }

  /**
   * Restore the Inner Container for the Group block.
   *
   * @param string $block_content
   *   The Gutenberg block content.
   *
   * @return string
   *   The block content with the Inner Container.
   */
  protected function restoreGroupInnerContainer(array $block, string $block_content): string {
    $tag_name = $block['attrs']['tagName'] ?? 'div';

    $inner_container_format = '/(^\s*<%1$s\b[^>]*wp-block-group(\s|")[^>]*>)(\s*<div\b[^>]*wp-block-group__inner-container(\s|")[^>]*>)((.|\S|\s)*)/U';
    $group_with_inner_container_regex = sprintf($inner_container_format,
      preg_quote($tag_name, '/'));

    $is_inner_container = preg_match($group_with_inner_container_regex, $block_content) === 1;
    $is_not_default = isset($block['attrs']['layout']['type']) && $block['attrs']['layout']['type'] !== 'default';
    if ($is_inner_container || $is_not_default) {
      return $block_content;
    }

    $format = '/(^\s*<%1$s\b[^>]*wp-block-group[^>]*>)(.*)(<\/%1$s>\s*$)/ms';
    $replace_regex = sprintf($format, preg_quote($tag_name, '/'));

    return preg_replace_callback(
      $replace_regex,
      static function($matches) {
        return $matches[1] . '<div class="wp-block-group__inner-container">' . $matches[2] . '</div>' . $matches[3];
      },
      $block_content
    );
  }

  /**
   * Attach Gutenberg frontend libraries to the result.
   *
   * @param \Drupal\filter\FilterProcessResult $result
   *   The resulting markup.
   */
  protected function addAttachments(FilterProcessResult $result) {
    $module_definitions = $this->libraryManager->getModuleDefinitions();
    $attachments = [];

    foreach ($module_definitions as $module_definition) {
      foreach ($module_definition['libraries-view'] as $library) {
        $attachments['library'][] = $library;
      }
    }

    $theme_definition = $this->libraryManager->getActiveThemeMergedDefinition();
    foreach ($theme_definition['libraries-view'] as $library) {
      $attachments['library'][] = $library;
    }

    $default_theme = $this->configFactory->get('system.theme')->get('default');
    if ($default_theme === 'bartik') {
      $attachments['library'][] = 'gutenberg/bartik';
    }

    if ($default_theme === 'olivero') {
      $attachments['library'][] = 'gutenberg/olivero';
    }

    if ($attachments) {
      // Add the frontend attachments.
      $result->addAttachments($attachments);
    }
  }

  /**
   * Set the current provider settings.
   */
  protected function setProviderSettings() {
    $processors = $this->blockProcessorManager->getSortedProcessors();
    foreach ($processors as $processor) {
      if ($processor instanceof GutenbergConfigurableBlockProcessorInterface) {
        $processor->setSettings($this->settings['processor_settings'] + $processor->defaultConfiguration());
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Displays and renders Gutenberg HTML markup. The Gutenberg HTML attribute comments are automatically stripped.');
    }

    return $this->t('Displays and renders Gutenberg HTML markup.');
  }

}
