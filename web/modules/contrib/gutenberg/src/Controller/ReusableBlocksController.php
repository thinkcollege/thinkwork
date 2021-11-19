<?php

namespace Drupal\gutenberg\Controller;

use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for our blocks routes.
 */
class ReusableBlocksController extends ControllerBase {

  /**
   * Returns JSON representing the loaded blocks.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $block_id
   *   The reusable block id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function load(Request $request, $block_id = NULL) {
    $headers = [
      'Allow' => 'GET, POST, PUT, PATCH, DELETE',
      'Access-Control-Allow-Methods' => 'OPTIONS, GET, POST, PUT, PATCH, DELETE',
      'Access-Control-Allow-Credentials' => 'true',
      'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
    ];

    if ($block_id && $block_id > 0) {
      $block = $this->loadBlockOrThrow($block_id);

      return new JsonResponse(
        $this->getBlockAttributes($block) + [
      // Kind of a hack but accepted by Gutenberg ;)
        'headers' => $headers,
        ], Response::HTTP_OK, $headers
      );
    }

    /*
     * TODO this should probably be paginated and lazy loaded in case a site
     *  has hundreds of reusable blocks.
     */
    $ids = \Drupal::entityQuery('block_content')
      ->condition('type', 'reusable_block')
      ->execute();

    $blocks = BlockContent::loadMultiple($ids);
    $result = [];

    /** @var \Drupal\block_content\BlockContentInterface[] $blocks */
    foreach ($blocks as $key => $block) {
      $result[] = $this->getBlockAttributes($block);
    }

    return new JsonResponse($result, Response::HTTP_OK, $headers);
  }

  /**
   * Saves reusable block.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $block_id
   *   The reusable block id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(Request $request, $block_id = NULL) {
    $updating_block = $block_id && $block_id > 0;

    // Cast to string to avoid null pointer when the title or content is empty.
    $title = (string) $request->request->get('title');
    $content = (string) $request->request->get('content');

    if ($updating_block) {
      $block = $this->loadBlockOrThrow($block_id);
      $block->set('info', $title);
      $block->set('body', $content);
    }
    else {
      $block = BlockContent::create([
        'info' => $title,
        'type' => 'reusable_block',
        'body' => [
          'value' => $content,
          'format' => 'full_html',
        ],
      ]);
    }

    $block->save();

    $headers = [
      'Allow' => 'GET, POST',
      'Access-Control-Allow-Methods' => 'OPTIONS, GET, POST, PUT, PATCH, DELETE',
    ];

    if ($updating_block) {
      $response_code = Response::HTTP_OK;
    }
    else {
      $response_code = Response::HTTP_CREATED;
    }

    return new JsonResponse($this->getBlockAttributes($block), $response_code, $headers);
  }

  /**
   * Delete reusable block.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $block_id
   *   The reusable block id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function delete(Request $request, $block_id = NULL) {
    $block = $this->loadBlockOrThrow($block_id);
    $block->delete();

    return new JsonResponse([
      'id' => (int) $block_id,
    ]);
  }

  /**
   * Controller routes access callback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   * @param string $block_id
   *   Block id from route parameter.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed access result if all conditions are met.
   */
  public function access(AccountInterface $account, $block_id) {
    $block = BlockContent::load($block_id);

    // Check if the user has the proper permissions.
    $access = AccessResult::allowedIfHasPermission($account, 'use gutenberg');
    if ($access->isAllowed()) {
      // Only throw the 404 if the user is allowed to access the route.
      // Avoids anonymous users scanning for a block's existence.
      if (!$block) {
        throw new NotFoundHttpException();
      }
    }
    // Check if it's a reusable block.
    $access = $access->andIf(AccessResult::allowedIf($block && $block->bundle() === 'reusable_block'));
    // Add it as a cache dependency.
    $access->addCacheableDependency($block);

    return $access;
  }

  /**
   * Load a reusable block content entity or throw an HTTP exception.
   *
   * An HTTP exception is thrown if the block could not be loaded or it's not a
   * reusable block type.
   *
   * @param int $block_id
   *   The block ID.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The block instance.
   */
  protected function loadBlockOrThrow($block_id) {
    $block_id = (int) $block_id;

    if ($block_id > 0 && $block = BlockContent::load($block_id)) {
      /* @var \Drupal\block_content\BlockContentInterface $block */
      if ($block->bundle() !== 'reusable_block') {
        // Avoid accidental/malicious manipulation of non reusable blocks in
        // this controller.
        throw new BadRequestHttpException("Block '$block_id' is not a reusable block.");
      }
      return $block;
    }

    throw new NotFoundHttpException("Block '$block_id' does not exist.");
  }

  /**
   * Get the block as a response array.
   *
   * @param \Drupal\block_content\BlockContentInterface $block
   *   The block instance.
   *
   * @return array
   *   The block response array.
   */
  protected function getBlockAttributes(BlockContentInterface $block) {
    return [
      'id' => (int) $block->id(),
      'title' => [
        'raw' => (string) $block->get('info')->value,
      ],
      'content' => [
        'block_version' => 1,
        'raw' => (string) $block->get('body')->value,
      ],
      'type' => 'wp_block',
      'status' => 'publish',
      'slug' => 'reusable_block_' . $block->id(),
    ];
  }

}
