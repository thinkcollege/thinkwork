<?php

namespace Drupal\gutenberg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;

/**
 * Controller for handling node entity queries to use as URLs.
 */
class SearchController extends ControllerBase {

  /**
   * Return a list of nodes containing a piece of search text.
   *
   * Used for link auto-completion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function search(Request $request) {
    $search = (string) $request->query->get('search');
    $limit = (int) $request->query->get('per_page', 20);

    $query = \Drupal::entityQuery('node');
    $query->condition('title', $search, 'CONTAINS')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit);

    $node_ids = $query->execute();
    $nodes = Node::loadMultiple($node_ids);
    $result = [];
    foreach ($nodes as $node) {
      $result[] = [
        'id' => $node->id(),
        'title' => $node->getTitle(),
        'type' => $node->getType(),
        'url' => $node->toUrl('canonical', ['absolute' => FALSE])->toString(),
      ];
    }

    return new JsonResponse($result);
  }

}
