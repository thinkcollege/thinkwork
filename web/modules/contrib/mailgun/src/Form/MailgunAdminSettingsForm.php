<?php

namespace Drupal\mailgun\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\mailgun\MailgunHandler;
use Drupal\mailgun\MailgunHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MailgunAdminSettingsForm.
 *
 * @package Drupal\mailgun\Form
 */
class MailgunAdminSettingsForm extends ConfigFormBase {

  /**
   * Mailgun handler.
   *
   * @var \Drupal\mailgun\MailgunHandlerInterface
   */
  protected $mailgunHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mailgun.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailgunHandlerInterface $mailgunHandler) {
    parent::__construct($config_factory);

    $this->mailgunHandler = $mailgunHandler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      MAILGUN_CONFIG_NAME,
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

    if (MailgunHandler::validateKey($form_state->getValue('api_key')) === FALSE) {
      $form_state->setErrorByName('api_key', $this->t("Couldn't connect to the Mailgun API. Please check your API settings."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    MailgunHandler::checkLibrary(TRUE);
    $config = $this->config(MAILGUN_CONFIG_NAME);

    $form['description'] = [
      '#markup' => $this->t('Please refer to @link for your settings.', [
        '@link' => Link::fromTextAndUrl($this->t('dashboard'), Url::fromUri('https://mailgun.com/app/domains', [
          'attributes' => [
            'onclick' => "target='_blank'",
          ],
        ]))->toString(),
      ]),
    ];

    $api_key = $config->get('api_key');
    $form['api_key'] = [
      '#title' => $this->t('Mailgun API Key'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => $this->t('Enter your API key. It should be similar to: @key', ['@key' => 'key-1234567890abcdefghijklmnopqrstu']),
      '#default_value' => $api_key,
    ];

    // Don't show other settings until we don't set API key.
    if (empty($api_key)) {
      return parent::buildForm($form, $form_state);
    }

    $form['working_domain'] = [
      '#title' => $this->t('Mailgun API Working Domain'),
      '#type' => 'select',
      '#options' => [
        '_sender' => $this->t('Get domain from sender address'),
      ] + $this->mailgunHandler->getDomains(),
      '#default_value' => $config->get('working_domain'),
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
    $form['advanced_settings']['format']['format_filter'] = [
      '#title' => $this->t('Format filter'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $config->get('format_filter'),
      '#description' => $this->t('Format filter to use to render the message.'),
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
      '#description' => $this->t('Enable to queue emails and send them out during cron run.'),
    ];

    $form['advanced_settings']['tagging_mailkey'] = [
      '#title' => $this->t('Enable tags by mail key'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('tagging_mailkey'),
      '#description' => $this->t('Add tag by mail key. See @link for details.', [
        '@link' => Link::fromTextAndUrl($this->t("Mailgun's tagging documentation"), Url::fromUri('https://documentation.mailgun.com/user_manual.html#tagging', [
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
    // Set default value for domain when we submit form for the first time.
    $domain = $form_state->getValue('working_domain');
    $this->config(MAILGUN_CONFIG_NAME)
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('working_domain', empty($domain) ? '_sender' : $domain)
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->set('test_mode', $form_state->getValue('test_mode'))
      ->set('tracking_opens', $form_state->getValue('tracking_opens'))
      ->set('tracking_clicks', $form_state->getValue('tracking_clicks'))
      ->set('tracking_exception', $form_state->getValue('tracking_exception'))
      ->set('format_filter', $form_state->getValue('format_filter'))
      ->set('use_queue', $form_state->getValue('use_queue'))
      ->set('use_theme', $form_state->getValue('use_theme'))
      ->set('tagging_mailkey', $form_state->getValue('tagging_mailkey'))
      ->save();

    $this->messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

}
