<?php

namespace Drupal\gutenberg\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
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
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Render service.
   * @param \Drupal\gutenberg\BlocksRendererHelper $blocks_renderer
   *   Blocks renderer helper service.
   */
  public function __construct(
    BlockManagerInterface $block_manager,
    ConfigFactoryInterface $config_factory,
    Renderer $renderer,
    BlocksRendererHelper $blocks_renderer) {
    $this->blockManager = $block_manager;
    $this->configFactory = $config_factory;
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
      $container->get('renderer'),
      $container->get('gutenberg.blocks_renderer')
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
    $blockManager = \Drupal::service('plugin.manager.block');
    $contextRepository = \Drupal::service('context.repository');
    $config = \Drupal::service('config.factory')->getEditable('gutenberg.settings');
    $config_values = $config->get($content_type . '_allowed_drupal_blocks');

    // Get blocks definition.
    $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());
    $definitions = $blockManager->getSortedDefinitions($definitions);

    $return = [];
    foreach ($config_values as $key => $value) {
      if ($value) {
        $return[$key] = $definitions[$key];
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
        return new JsonResponse(['access' => FALSE, 'html' => $this->t('Unable to render block. Check block settings or permissions.')]);
      }

      $content = $this->blocksRenderer->getRenderFromBlockPlugin($plugin_block);

      // Create a block entity.
      // $entity = $this->entityTypeManager()->getStorage('block')->create(['plugin' => $plugin_id, 'theme' => NULL]);
      // $form = $this->entityFormBuilder()->getForm($entity);
      // Render block settings.
      // $config = $this->renderer->render($form['settings']);
    }

    // If the block is a view with contexts defined, it may
    // not render on the editor because of, for example, the
    // node path. Let's just write some warning if no content.
    if ($content === '') {
      $content = $this->t('Unable to render the content possibly due to path restrictions.');
    }

    return new JsonResponse(['access' => TRUE, 'html' => $content]); // , 'config' => $config
  }

}
