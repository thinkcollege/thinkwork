<?php

namespace Drupal\fontyourface\Controller;

use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\fontyourface\Entity\Font;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for forum routes.
 */
class FontYourFaceController extends ControllerBase {

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an AdminToolbarSearchController object.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(RedirectDestinationInterface $redirect_destination, MessengerInterface $messenger) {
    $this->redirectDestination = $redirect_destination;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function activateFont(Font $font, $js) {
    try {
      $font->activate();
      if ($js == 'ajax') {
        $url = Url::fromRoute('entity.font.deactivate', [
          'js' => 'nojs',
          'font' => $font->id(),
        ], [
          'query' => $this->redirectDestination->getAsArray(),
        ]);
        $url->setOptions(
          [
            'attributes' => [
              'id' => 'font-status-' . $font->id(),
              'class' => ['font-status', 'enabled', 'use-ajax'],
            ],
          ]
        );
        $text = $this->t('Enable');
        $link = Link::fromTextAndUrl($text, $url)->toString();

        $response = new AjaxResponse();
        return $response->addCommand(new ReplaceCommand('#font-status-' . $font->id(), $link));
      }
      else {
        $this->messenger->addMessage($this->t('Font @font successfully enabled', ['@font' => $font->name->value]));
        return $this->redirect('entity.font.collection');
      }
    }
    catch (Exception $e) {
      $error = $e->getMessage();
      if ($js == 'ajax') {
        return new AjaxResponse([
          'response' => TRUE,
          'message' => $error,
        ], 503);
      }
      else {
        $this->messenger->addMessage($error, 'error');
        return $this->redirect('entity.font.collection');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deactivateFont(Font $font, $js) {
    try {
      $font->deactivate();
      if ($js == 'ajax') {
        $url = Url::fromRoute('entity.font.activate', [
          'js' => 'nojs',
          'font' => $font->id(),
        ], [
          'query' => $this->redirectDestination->getAsArray(),
        ]);
        $url->setOptions(
          [
            'attributes' => [
              'id' => 'font-status-' . $font->id(),
              'class' => ['font-status', 'disabled', 'use-ajax'],
            ],
          ]
        );
        $text = $this->t('Enable');
        $link = Link::fromTextAndUrl($text, $url)->toString();

        $response = new AjaxResponse();
        return $response->addCommand(new ReplaceCommand('#font-status-' . $font->id(), $link));
      }
      else {
        $this->messenger->addMessage($this->t('Font @font successfully disabled', ['@font' => $font->name->value]));
        return $this->redirect('entity.font.collection');
      }
    }
    catch (Exception $e) {
      $error = $e->getMessage();
      if ($js == 'ajax') {
        return new AjaxResponse([
          'response' => TRUE,
          'message' => $error,
        ], 503);
      }
      else {
        $this->messenger->addMessage($error, 'error');
        return $this->redirect('entity.font.collection');
      }
    }
  }

}
