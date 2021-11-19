<?php

namespace Drupal\gutenberg_cloud\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\gutenberg_cloud\CloudBlockInterface;
use Drupal\gutenberg_cloud\CloudBlockManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Gutenberg Cloud blocks.
 */
class BlockManagerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gutenberg_cloud.blocks',
    ];
  }

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Cloud Block Manager service.
   *
   * @var \Drupal\gutenberg_cloud\CloudBlockManager
   */
  protected $blockManager;

  /**
   * Library Discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * BlockManagerForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messanger service.
   * @param \Drupal\gutenberg_cloud\CloudBlockManager $block_manager
   *   Cloud Block Manager service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   Library Discovery service.
   */
  public function __construct(MessengerInterface $messenger, CloudBlockManager $block_manager, LibraryDiscoveryInterface $library_discovery) {
    $this->messenger = $messenger;
    $this->blockManager = $block_manager;
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('gutenberg_cloud.block_manager'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gutenberg_cloud_block_manager_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cloudBlocks = $this->blockManager->loadAllFromRemote();

    if (!empty($cloudBlocks)) {

      $this->addFilters($form);

      $form['blocks'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'gutenberg-cloud__blocks',
          ],
        ],
      ];

      // Loop index - default order of the blocks.
      $weight = 0;

      foreach ($cloudBlocks as $key => $block) {
        $id = Html::cleanCssIdentifier($block->getName());

        $form['blocks'][$key] = [
          '#type' => 'fieldset',
          '#open' => TRUE,
          '#id' => $id,
          '#attributes' => [
            'class' => [
              'gutenberg-cloud__item',
            ],
            'data-popularity' => $block->getRaw()->installs,
            'data-weight' => $weight++,
          ],
        ];

        $form['blocks'][$key]['details_wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'gutenberg-cloud__details',
            ],
          ],
        ];

        $form['blocks'][$key]['details_wrapper']['details'] = [
          '#type' => 'button',
          '#value' => $this->t('More details'),
          '#ajax' => [
            'callback' => '::viewDetails',
            'event' => 'click',
          ],
          '#attributes' => [
            'class' => ['details'],
          ],
          '#name' => 'details__' . $block->getName(),
        ];

        $form['blocks'][$key]['details_wrapper']['image'] = [
          '#theme' => 'image',
          '#uri' => $block->getAssetUrl('screenshot', $this->blockManager->getCdnUrl()),
        ];

        $form['blocks'][$key]['title'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $block->getLabel(),
          '#attributes' => [
            'class' => ['title'],
          ],
        ];

        $form['blocks'][$key]['install'] = [
          '#type' => 'submit',
          '#value' => $this->t('Install'),
          '#ajax' => [
            'callback' => '::installCallback',
            'event' => 'click',
            'method' => 'replace',
            'effect' => 'fade',
            'wrapper' => $id,
            'progress' => [
              'type' => 'submit',
              'message' => $this->t('Installing...'),
            ],
          ],
          '#button_type' => 'primary',
          '#name' => 'install__' . $block->getName(),
          '#access' => FALSE,
        ];

        $form['blocks'][$key]['update'] = [
          '#type' => 'submit',
          '#value' => $this->t('Update'),
          '#ajax' => [
            'callback' => '::updateCallback',
            'event' => 'click',
            'method' => 'replace',
            'effect' => 'fade',
            'wrapper' => $id,
            'progress' => [
              'type' => 'submit',
              'message' => $this->t('Updating...'),
            ],
          ],
          '#button_type' => 'primary',
          '#name' => 'update__' . $block->getName(),
          '#access' => FALSE,
        ];

        $form['blocks'][$key]['uninstall'] = [
          '#type' => 'submit',
          '#value' => $this->t('Uninstall'),
          '#ajax' => [
            'callback' => '::uninstallCallback',
            'event' => 'click',
            'method' => 'replace',
            'effect' => 'fade',
            'wrapper' => $id,
            'progress' => [
              'type' => 'throbber',
              'message' => $this->t('Uninstalling...'),
            ],
          ],
          '#name' => 'uninstall__' . $block->getName(),
          '#access' => FALSE,
        ];

        if (!$this->blockManager->isBlockEnabled($block)) {
          $form['blocks'][$key]['install']['#access'] = TRUE;
        }
        else {
          if ($this->blockManager->hasUpdates($block)) {
            $form['blocks'][$key]['update']['#access'] = TRUE;
          }
          $form['blocks'][$key]['uninstall']['#access'] = TRUE;
          $form['blocks'][$key]['#attributes']['class'][] = 'enabled';
        }
      }

      $form['#attached']['library'] = [
        'gutenberg_cloud/blocks',
      ];
    }
    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * Ajax callback to display block details in modal.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Modal dialog.
   */
  public function viewDetails(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $name = str_replace('details__', '', $element['#name']);
    $block = $this->blockManager->loadRemote($name);
    $rawBlock = $block->getRaw();

    $details = [
      '#theme' => 'gutenberg_cloud__details',
      '#name' => $block->getLabel(),
      '#version' => $block->getVersion(),
      '#description' => $block->getDescription(),
      '#author' => $rawBlock->package->author->name,
      '#author_url' => $rawBlock->package->author->url,
      '#picture' => $block->getAssetUrl('screenshot', $this->blockManager->getCdnUrl()),
      '#tags' => $rawBlock->package->keywords,
    ];

    $details['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $details['#attached']['library'][] = 'gutenberg_cloud/details';

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($block->getLabel(), $details, ['width' => '80%']));

    return $response;
  }

  /**
   * Block installation callback.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form element
   */
  public function installCallback(array &$form, FormStateInterface $form_state) {
    $output = $this->performCallbackAction($form, $form_state, 'install');

    return $output;
  }

  /**
   * Block update callback.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form element
   */
  public function updateCallback(array &$form, FormStateInterface $form_state) {
    $output = $this->performCallbackAction($form, $form_state, 'update');

    return $output;
  }

  /**
   * Block uninstall callback.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form element
   */
  public function uninstallCallback(array &$form, FormStateInterface $form_state) {
    $output = $this->performCallbackAction($form, $form_state, 'uninstall');

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Save configs and alter the form on ajax callback.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $type
   *   Action to perform.
   *
   * @return array
   *   Form element
   */
  protected function performCallbackAction(array &$form, FormStateInterface $form_state, $type = '') {
    $config = $this->config('gutenberg_cloud.blocks');
    $element = $form_state->getTriggeringElement();
    $parents = $element['#array_parents'];
    $output = $form[$parents[0]][$parents[1]];

    $name = str_replace($type . '__', '', $element['#name']);
    switch ($type) {
      case 'update':
      case 'install':
        $block = $this->blockManager->loadRemote($name);

        if ($block instanceof CloudBlockInterface) {
          $config
            ->set($name, $block->getConfig())
            ->save();
        }

        $output['install']['#access'] = FALSE;
        $output['update']['#access'] = FALSE;
        $output['uninstall']['#access'] = TRUE;
        $output['#attributes']['class'][] = 'enabled';

        $message = $type == 'install' ? 'Installed' : 'Updated';

        $this->messenger->addStatus($this->t('@message', ['@message' => $message]));
        break;

      case 'uninstall':
        $config->clear($name)->save();

        $output['install']['#access'] = TRUE;
        $output['update']['#access'] = FALSE;
        $output['uninstall']['#access'] = FALSE;

        $classes = $output['#attributes']['class'];
        $output['#attributes']['class'] = array_diff($classes, ['enabled']);

        $this->messenger->addStatus($this->t('Uninstalled'));
        break;

      default:
        break;
    }

    $this->libraryDiscovery->clearCachedDefinitions();

    return $output;
  }

  /**
   * Adds filters to form.
   *
   * @param array $form
   *   Form structure.
   */
  protected function addFilters(array &$form) {
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'gutenberg-cloud__filters',
        ],
      ],
    ];

    $filters = [
      'all' => [
        'label' => $this->t('All'),
        'filter' => 'all',
      ],
      'installed' => [
        'label' => $this->t('Installed'),
        'filter' => 'enabled',
      ],
      'popular' => [
        'label' => $this->t('Popular'),
        'filter' => 'popular',
      ],
    ];

    foreach ($filters as $name => $filter) {
      $form['filters'][$name] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $filter['label'],
        '#attributes' => [
          'class' => ['filter'],
          'type' => 'button',
          'data-filter' => $filter['filter'],
        ],
      ];
    }

    $form['filters']['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Search block'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Search blocks...'),
      '#attributes' => [
        'class' => ['search'],
        'autocomplete' => 'on',
      ],
    ];
  }

}
