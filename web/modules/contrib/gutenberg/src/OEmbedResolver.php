<?php

namespace Drupal\gutenberg;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\media\OEmbed\ProviderException;
use Drupal\media\OEmbed\ResourceException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for resolving oEmbed URLs.
 */
class OEmbedResolver implements OEmbedResolverInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Render\RendererInterface instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The media module's oEmbed Url resolver.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $mediaOembedResolver;

  /**
   * The media module's OEmbed resource fetcher service.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $mediaOembedResourceFetcher;

  /**
   * OEmbedProcessor constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ContainerInterface $container,
    ClientInterface $client,
    RendererInterface $renderer,
    ModuleHandlerInterface $module_handler
  ) {
    $this->httpClient = $client;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;

    if ($module_handler->moduleExists('media')) {
      $this->mediaOembedResolver = $container->get('media.oembed.url_resolver', ContainerInterface::NULL_ON_INVALID_REFERENCE);
      $this->mediaOembedResourceFetcher = $container->get('media.oembed.resource_fetcher', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resolveOembed($url, $maxwidth) {
    $output = NULL;
    if ($this->mediaOembedResolver && $this->mediaOembedResourceFetcher) {
      // The media module is enabled. Attempt to use it to resolve the oembed.
      try {
        $resource_url = $this->mediaOembedResolver->getResourceUrl($url, $maxwidth);
        if ($resource_url) {
          $output = $this->mediaOembedResourceFetcher->fetchResource($resource_url)
            ->getHtml();
        }
      }
      catch (ResourceException $exception) {
        if (!empty($exception->getData()['html'])) {
          // Some might have valid HTML but might be missing certain attributes which result in an exception.
          // e.g. https://streamable.com/ba9f2
          $output = $exception->getData()['html'];
        }
      }
      /* @noinspection PhpRedundantCatchClauseInspection */
      catch (ProviderException $exception) {

      }
    }

    // If the media module was unable to resolve it, attempt using the fallback provider (iframe.ly by default).
    if (!$output) {
      $query_params = rawurldecode(UrlHelper::buildQuery([
        'url' => $url,
        'origin' => 'drupal',
        'format' => 'json',
        'maxwidth' => $maxwidth,
      ]));

      $default_provider_uri = $this->getDefaultFallbackOembedProviderUri();
      $arg_separator = (strpos($default_provider_uri, '?') === FALSE) ? '?' : '&';
      $output = $this->fetchOembedHtml($default_provider_uri . $arg_separator . $query_params);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchOembedHtml($url) {
    $output = NULL;

    try {
      $request = $this->httpClient->get($url);
      $response = $request->getBody();

      // No network error occurred.
      $output = '';

      if (!empty($response)) {
        $embed = json_decode($response);
        if (!empty($embed->html)) {
          $output = $embed->html;
        }
        elseif ($embed->type === 'link') {
          $render = [
            '#title' => $embed->title,
            '#type' => 'link',
            '#url' => Url::fromUri($embed->url),
          ];

          $output = $this->renderer->renderPlain($render);
        }
        elseif ($embed->type === 'photo') {
          $render = [
            '#type' => 'html_tag',
            '#tag' => 'img',
            '#attributes' => [
              'alt' => $embed->title,
              'src' => $embed->url,
              'title' => $embed->title,
              'class' => [
                'gutenberg-oembed-image',
              ],
              'style' => 'width:100%',
            ],
            '#prefix' => '<a href="' . htmlentities($url, ENT_QUOTES) . '">',
            '#suffix' => '</a>',
            '#gutenberg_embed_photo' => TRUE,
            '#gutenberg_embed_url' => $url,
          ];

          $output = $this->renderer->renderPlain($render);
        }
      }
    }
    catch (RequestException $e) {
      watchdog_exception('gutenberg_oembed', $e);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFallbackOembedProviderUri() {
    return Settings::get('gutenberg.default_oembed_provider', 'https://open.iframe.ly/api/oembed');
  }

}
