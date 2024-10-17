<?php

namespace Drupal\gutenberg\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Render\Renderer;
use Drupal\gutenberg\BlocksRendererHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for our blocks routes.
 */
class BlocksController extends ControllerBase {

  /**
   * Drupal\Core\Block\BlockManagerInterface instance.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Drupal\Core\Render\Renderer instance.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Drupal\gutenberg\BlocksRendererHelper instance.
   *
   * @var \Drupal\gutenberg\BlocksRendererHelper
   */
  protected $blocksRenderer;

  /**
   * BlocksController constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   Context repository service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Render service.
   * @param \Drupal\gutenberg\BlocksRendererHelper $blocks_renderer
   *   Blocks renderer helper service.
   */
  public function __construct(
    BlockManagerInterface $block_manager,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    ContextRepositoryInterface $context_repository,
    Renderer $renderer,
    BlocksRendererHelper $blocks_renderer
  ) {
    $this->blockManager = $block_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->contextRepository = $context_repository;
    $this->renderer = $renderer;
    $this->blocksRenderer = $blocks_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('context.repository'),
      $container->get('renderer'),
      $container->get('gutenberg.blocks_renderer'),
    );
  }

  /**
   * Returns JSON representing the loaded blocks.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $content_type
   *   The content type to fetch settings from.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function loadByType(Request $request, $content_type) {
    $config = $this->configFactory->getEditable('gutenberg.settings');
    $allowed_drupal_blocks = $config->get($content_type . '_allowed_drupal_blocks');
    $this->moduleHandler()->alter('allowed_drupal_blocks', $allowed_drupal_blocks, $content_type);

    // Get blocks definition.
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    $definitions = $this->blockManager->getSortedDefinitions($definitions);
    $groups = $this->blockManager->getGroupedDefinitions($definitions);
    foreach ($groups as $key => $blocks) {
      $group_reference = preg_replace('@[^a-z0-9-]+@', '_', strtolower($key));
      $groups['drupalblock/all_' . $group_reference] = $blocks;
      unset($groups[$key]);
    }

    $return = [];
    foreach ($allowed_drupal_blocks as $key => $value) {
      if ($value) {
        if (preg_match('/^drupalblock\/all/', $value)) {
          // Getting all blocks from group.
          foreach ($groups[$value] as $key_block => $definition) {
            $return[$key_block] = $definition;
          }
        }
        elseif (empty($definitions[$value])) {
          $return[$value] = NULL;
        }
        else {
          $return[$key] = $definitions[$key];
        }
      }
    }
    return new JsonResponse($return);
  }

  /**
   * Returns JSON representing the loaded blocks.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $plugin_id
   *   Plugin ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function loadById(Request $request, $plugin_id) {
    $request_content = $request->getContent();

    $config = [];
    if (!empty($request_content)) {
      $config = json_decode($request_content, TRUE);
    }

    $plugin_block = $this->blocksRenderer->getBlockFromPluginId($plugin_id, $config);

    $content = '';

    if ($plugin_block) {
      $access_result = $this->blocksRenderer->getBlockAccess($plugin_block);
      if ($access_result->isForbidden()) {
        // You might need to add some cache tags/contexts.
        return new JsonResponse([
          'access' => FALSE,
          'html' => $this->t('Unable to render block. Check block settings or permissions.'),
        ]);
      }

      $content = $this->blocksRenderer->getRenderFromBlockPlugin($plugin_block);
    }

    // If the block is a view with contexts defined, it may
    // not render on the editor because of, for example, the
    // node path. Let's just write some warning if no content.
    if ($content === '') {
      $content = $this->t('Unable to render the content possibly due to path restrictions.');
    }

    return new JsonResponse(['access' => TRUE, 'html' => $content]);
  }

}
