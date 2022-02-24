<?php

namespace Drupal\mailgun\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\FilterPluginManager;
use Drupal\mailgun\MailgunHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Mailgun configuration form.
 */
class MailgunAdminSettingsForm extends ConfigFormBase {

  /**
   * Mailgun handler.
   *
   * @var \Drupal\mailgun\MailgunHandlerInterface
   */
  protected $mailgunHandler;

  /**
   * The filter plugin manager.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mailgun.mail_handler'),
      $container->get('plugin.manager.filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailgunHandlerInterface $mailgun_handler, FilterPluginManager $filter_manager) {
    parent::__construct($config_factory);
    $this->mailgunHandler = $mailgun_handler;
    $this->filterManager = $filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      MailgunHandlerInterface::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailgun_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $entered_api_key = $form_state->getValue('api_key');
    if (!empty($entered_api_key) && $this->mailgunHandler->validateMailgunApiKey($entered_api_key) === FALSE) {
      $form_state->setErrorByName('api_key', $this->t("Couldn't connect to the Mailgun API. Please check your API settings."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->mailgunHandler->validateMailgunLibrary(TRUE);
    $config = $this->config(MailgunHandlerInterface::CONFIG_NAME);

    $form['description'] = [
      '#markup' => $this->t('Please refer to @link for your settings.', [
        '@link' => Link::fromTextAndUrl($this->t('dashboard'), Url::fromUri('https://app.mailgun.com/app/dashboard', [
          'attributes' => [
            'onclick' => "target='_blank'",
          ],
        ]))->toString(),
      ]),
    ];

    $form['api_key'] = [
      '#title' => $this->t('Mailgun API Key'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => $this->t('Enter your @link.', [
        '@link' => Link::fromTextAndUrl($this->t('Private API key'), Url::fromUri('https://app.mailgun.com/app/account/security/api_keys'))->toString(),
      ]),
      '#default_value' => $config->get('api_key'),
    ];

    // Load not-editable configuration object to check actual api key value
    // including overrides.
    $not_editable_config = $this->configFactory()->get(MailgunHandlerInterface::CONFIG_NAME);

    // Don't show other settings until we don't set API key.
    if (empty($not_editable_config->get('api_key'))) {
      return parent::buildForm($form, $form_state);
    }

    // If "API Key" is overridden in settings.php it won't be visible in form.
    // We have to make the field optional and allow configure other settings.
    if (empty($config->get('api_key')) && !empty($not_editable_config->get('api_key'))) {
      $form['api_key']['#required'] = FALSE;
    }

    $form['working_domain'] = [
      '#title' => $this->t('Mailgun API Working Domain'),
      '#type' => 'select',
      '#options' => [
        '_sender' => $this->t('Get domain from sender address'),
      ] + $this->mailgunHandler->getDomains(),
      '#default_value' => $config->get('working_domain'),
    ];

    $form['api_endpoint'] = [
      '#title' => $this->t('Mailgun Region'),
      '#type' => 'select',
      '#required' => TRUE,
      '#description' => $this->t('Select which Mailgun region to use.'),
      '#options' => [
        'https://api.mailgun.net' => $this->t('Default (US)'),
        'https://api.eu.mailgun.net' => $this->t('Europe'),
      ],
      '#default_value' => $config->get('api_endpoint'),
    ];

    $form['debug_mode'] = [
      '#title' => $this->t('Enable Debug Mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('debug_mode'),
      '#description' => $this->t('Enable to log every email and queuing.'),
    ];

    $form['test_mode'] = [
      '#title' => $this->t('Enable Test Mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('test_mode'),
      '#description' => $this->t('Mailgun will accept the message but will not send it. This is useful for testing purposes.'),
    ];

    $form['advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['advanced_settings']['tracking'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tracking'),
    ];

    $form['advanced_settings']['tracking']['tracking_opens'] = [
      '#title' => $this->t('Enable Track Opens'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('Use domain setting'),
        'no' => $this->t('No'),
        'yes' => $this->t('Yes'),
      ],
      '#default_value' => $config->get('tracking_opens'),
      '#description' => $this->t('Enable to track the opening of an email. See: @link for details.', [
        '@link' => Link::fromTextAndUrl($this->t('Tracking Opens'), Url::fromUri('https://documentation.mailgun.com/en/latest/user_manual.html#tracking-opens', [
          'attributes' => [
            'onclick' => "target='_blank'",
          ],
        ]))->toString(),
      ]),
    ];

    $form['advanced_settings']['tracking']['tracking_clicks'] = [
      '#title' => $this->t('Enable Track Clicks'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('Use domain setting'),
        'no' => $this->t('No'),
        'yes' => $this->t('Yes'),
        'htmlonly' => $this->t('HTML only'),
      ],
      '#default_value' => $config->get('tracking_clicks'),
      '#description' => $this->t('Enable to track the clicks of within an email. See: @link for details.', [
        '@link' => Link::fromTextAndUrl($this->t('Tracking Clicks'), Url::fromUri('https://documentation.mailgun.com/en/latest/user_manual.html#tracking-clicks', [
          'attributes' => [
            'onclick' => "target='_blank'",
          ],
        ]))->toString(),
      ]),
    ];
    $form['advanced_settings']['tracking']['tracking_exception'] = [
      '#title' => $this->t('Do not track the following mails'),
      '#type' => 'textarea',
      '#default_value' => $config->get('tracking_exception'),
      '#description' => $this->t('Add all mail keys you want to except from tracking. One key per line. Format: module:key (e.g.: user:password_reset).'),
    ];

    $form['advanced_settings']['format'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Format'),
    ];

    $options = [
      '' => $this->t('None'),
    ];
    $filter_formats = filter_formats();
    foreach ($filter_formats as $filter_format_id => $filter_format) {
      $options[$filter_format_id] = $filter_format->label();
    }

    // Add additional description text if there is a recommended filter plugin.
    // To be sure we are using the correct plugin name, let's use the plugin definition.
    $recommendation = !$this->filterManager->hasDefinition('filter_autop') ? ''
      : $this->t('Recommended format filters: @filter.', ['@filter' => $this->filterManager->getDefinition('filter_autop')['title'] ?? '']);

    $form['advanced_settings']['format']['format_filter'] = [
      '#title' => $this->t('Format filter'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $config->get('format_filter'),
      '#description' => $this->t('@text_format to use to render the message. @recommendation', [
        '@text_format' => Link::fromTextAndUrl($this->t('Text format'), Url::fromRoute('filter.admin_overview'))->toString(),
        '@recommendation' => $recommendation,
      ]),
    ];
    $form['advanced_settings']['format']['use_theme'] = [
      '#title' => $this->t('Use theme'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('use_theme'),
      '#description' => $this->t('Enable to pass the message through a theme function. Default "mailgun" or pass one with $message["params"]["theme"].'),
    ];

    $form['advanced_settings']['use_queue'] = [
      '#title' => $this->t('Enable Queue'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('use_queue'),
      '#description' => $this->t('Enable to queue emails and send them out during cron run. You can also enable queue for specific email keys by selecting Mailgun mailer (queued) plugin in @link.', [
        '@link' => Link::fromTextAndUrl($this->t('mail system configuration'), Url::fromRoute('mailsystem.settings'))->toString(),
      ]),
    ];

    $form['advanced_settings']['tagging_mailkey'] = [
      '#title' => $this->t('Enable tags by mail key'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('tagging_mailkey'),
      '#description' => $this->t('Add tag by mail key. See @link for details. Warning: adding tags will automatically add the "List-Unsubscribe" header to e-emails.', [
        '@link' => Link::fromTextAndUrl($this->t("Mailgun's tagging documentation"), Url::fromUri('https://documentation.mailgun.com/en/latest/user_manual.html#tagging', [
          'attributes' => [
            'onclick' => "target='_blank'",
          ],
        ]))->toString(),
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_keys = [
      'working_domain', 'api_key', 'debug_mode', 'test_mode', 'tracking_opens',
      'tracking_clicks', 'tracking_exception', 'format_filter', 'use_queue',
      'use_theme', 'tagging_mailkey', 'api_endpoint',
    ];

    $mailgun_config = $this->config(MailgunHandlerInterface::CONFIG_NAME);
    foreach ($config_keys as $config_key) {
      if ($form_state->hasValue($config_key)) {
        $mailgun_config->set($config_key, $form_state->getValue($config_key));
      }
    }
    $mailgun_config->save();

    $this->messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

}
