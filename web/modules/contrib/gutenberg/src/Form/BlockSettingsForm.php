<?php

namespace Drupal\gutenberg\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\gutenberg\BlocksRendererHelper;

/**
 * Provides a form to update a block.
 *
 * @internal
 *   Form classes are internal.
 */
class BlockSettingsForm extends FormBase implements BaseFormIdInterface {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * Drupal\gutenberg\BlocksRendererHelper instance.
   *
   * @var \Drupal\gutenberg\BlocksRendererHelper
   */
  protected $blocksRenderer;

  /**
   * Constructs a new block form.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\gutenberg\BlocksRendererHelper $blocks_renderer
   *   Blocks renderer helper service.
   */
  public function __construct(BlockManagerInterface $block_manager, PluginFormFactoryInterface $plugin_form_manager, BlocksRendererHelper $blocks_renderer) {
    $this->blockManager = $block_manager;
    $this->pluginFormFactory = $plugin_form_manager;
    $this->blocksRenderer = $blocks_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'gutenberg_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gutenberg_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('plugin_form.factory'),
      $container->get('gutenberg.blocks_renderer')
    );
  }

  /**
   * Builds the block form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL, $config = []) {
    function array_flatten($array = null) {
      $result = array();
  
      if (!is_array($array)) {
          $array = func_get_args();
      }
  
      foreach ($array as $key => $value) {
          if (is_array($value)) {
              $result = array_merge($result, array_flatten($value));
          } else {
              $result = array_merge($result, array($key => $value));
          }
      }
  
      return $result;
    }

    $form_state->set('block_theme', $this->config('system.theme')->get('default'));
    $user_input = $form_state->getUserInput();
    $configuration = array_merge($user_input['settings'], array_flatten($user_input['settings']));

    $plugin_block = $this->blocksRenderer->getBlockFromPluginId($plugin_id, $configuration ?? []);
    $block_form = $plugin_block->blockForm($form, $form_state);
 
    // $form['#attached']['library'][] = 'gutenberg/drupal-block-settings';

    $form['#tree'] = TRUE;

    $form['settings'] = $block_form;

    $form['actions'] = [
      '#type' => 'container',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->submitLabel(),
      '#button_type' => 'primary',

      '#attributes' => [
        'onclick' => 'return false;'
      ],
      '#attached' => array(
        'library' => array(
          'gutenberg/drupal-block-settings',
        ),
      ),
    ];

    // @todo static::ajaxSubmit() requires data-drupal-selector to be the same
    //   between the various Ajax requests. A bug in
    //   \Drupal\Core\Form\FormBuilder prevents that from happening unless
    //   $form['#id'] is also the same. Normally, #id is set to a unique HTML
    //   ID via Html::getUniqueId(), but here we bypass that in order to work
    //   around the data-drupal-selector bug. This is okay so long as we
    //   assume that this form only ever occurs once on a page. Remove this
    //   workaround in https://www.drupal.org/node/2897377.
    $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitLabel() {
    return $this->t('Update');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    $response->addCommand(new SettingsCommand([
      'something' => $form_state->getValue('settings'),
    ], TRUE));
    // $response->addCommand(new ReplaceCommand('#layout-builder', '<div>Damn</div>'));
    $form_state->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  // protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
  //   // $response = $this->rebuildLayout($section_storage);
  //   $response = new AjaxResponse();
  //   $response->addCommand(new CloseModalDialogCommand());
  //   $response->addCommand(new ReplaceCommand('#layout-builder', '<div>Damn</div>'));
  //   $form_state->setResponse($response);
  //   return $response;
  // }
}
