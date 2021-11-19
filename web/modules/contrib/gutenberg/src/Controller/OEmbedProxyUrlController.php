<?php

namespace Drupal\gutenberg\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\gutenberg\OEmbedResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class OEmbedProxyUrlController.
 *
 * @package Drupal\gutenberg\Controller
 */
class OEmbedProxyUrlController extends ControllerBase {

  /**
   * The oembed resolver.
   *
   * @var \Drupal\gutenberg\OEmbedResolverInterface
   */
  protected $oembedResolver;

  /**
   * OEmbedProxyUrlController constructor.
   *
   * @param \Drupal\gutenberg\OEmbedResolverInterface $oembed_resolver
   *   The oEmbed resolver.
   */
  public function __construct(OEmbedResolverInterface $oembed_resolver) {
    $this->oembedResolver = $oembed_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gutenberg.oembed_resolver')
    );
  }

  /**
   * Handle an oEmbed proxy request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The JSON response.
   */
  public function request(Request $request) {
    $url = $request->query->get('url');
    $max_width = $request->query->get('maxWidth', 800);

    if (empty($url) || !UrlHelper::isValid($url, TRUE)) {
      throw new BadRequestHttpException('Invalid URL was provided.');
    }

    /*
     * TODO: up for discussion, but the proxy previews won't match the custom oEmbeds settings declared in the
     *  OEmbedProcessor settings. I think the settings should be moved into an API for consolidation.
     */
    $oembed = $this->oembedResolver->resolveOembed($url, $max_width);

    if ($oembed) {
      $cacheable_response = new CacheableJsonResponse([
        'html' => $oembed,
      ]);
      $metadata = new CacheableMetadata();
      // Cache based on the current request uri (includes query strings).
      $metadata->addCacheContexts([
        'url',
      ]);
      // Cache the results for 15 minutes at a time.
      $metadata->setCacheMaxAge(15 * 60);
      $cacheable_response->addCacheableDependency($metadata);

      return $cacheable_response;
    }

    throw new NotFoundHttpException(sprintf('The oEmbed URL (%s) could not be resolved.', $url));
  }

}
