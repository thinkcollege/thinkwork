<?php

namespace Drupal\gutenberg\BlockProcessor;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\gutenberg\OEmbedResolverInterface;

/**
 * Processes oEmbed blocks.
 */
class OEmbedProcessor extends ConfigurableProcessorBase {

  use UseCacheBackendTrait;

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
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The oembed resolver.
   *
   * @var \Drupal\gutenberg\OEmbedResolverInterface
   */
  protected $oembedResolver;

  /**
   * Array of user provided oEmbed providers.
   *
   * @var array
   */
  protected $oembedProviders;

  /**
   * OEmbedProcessor constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\gutenberg\OEmbedResolverInterface $oembed_resolver
   *   The oEmbed resolver.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The cache backend.
   */
  public function __construct(
    RendererInterface $renderer,
    ModuleHandlerInterface $module_handler,
    TimeInterface $time,
    OEmbedResolverInterface $oembed_resolver,
    CacheBackendInterface $cache_backend = NULL
  ) {
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    $this->time = $time;
    $this->oembedResolver = $oembed_resolver;

    $this->cacheBackend = $cache_backend;
    $this->useCaches = isset($cache_backend);
  }

  /**
   * {@inheritdoc}
   */
  public function processBlock(array &$block, &$block_content, RefinableCacheableDependencyInterface $bubbleable_metadata) {
    $block_attributes = $block['attrs'];
    $url = $block_attributes['url'] ?? '';
    if (!$url) return;

    // Try and check against the cache as too many requests might lead to
    // the site being blacklisted.
    $maxwidth = $this->settings['oembed']['maxwidth'];
    $cache_id = "gutenberg:oembed_processor:$url:$maxwidth";

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      if ($cached->data) {
        // Only replace if there's cache data.
        $block_content = str_replace($url, $cached->data, $block_content);
      }
      return;
    }

    $regex = NULL;
    $provider_uri = NULL;

    list($regex, $provider_uri) = $this->getProviderUri($url);

    $output = NULL;

    if ($regex === 'LOCAL' && !empty($provider_uri)) {
      // Local provider configuration.
      $output = $this->getLocalProviderContent($provider_uri, $url);
    }
    else {
      $query_params = rawurldecode(UrlHelper::buildQuery([
        'url' => $url,
        'origin' => 'drupal',
        'format' => 'json',
        'maxwidth' => $maxwidth,
      ]));

      if (!empty($provider_uri)) {
        // User-defined provider.
        $arg_separator = (strpos($provider_uri, '?') === FALSE) ? '?' : '&';
        $output = $this->oembedResolver->fetchOembedHtml($provider_uri . $arg_separator . $query_params);
      }
      else {
        $output = $this->oembedResolver->resolveOembed($url, $maxwidth);
      }
    }

    if ($output === NULL) {
      // Cache the empty result for 5 minutes, if no response was received,
      // possibly due to network issues.
      $max_age = 5 * 60;
      $expiry = $this->time->getRequestTime() + $max_age;
    }
    else {
      $expiry = Cache::PERMANENT;
    }

    $this->cacheSet($cache_id, $output, $expiry);

    if ($output) {
      // Replace the oEmbed link.
      $block_content = str_replace($url, $output, $block_content);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isSupported(array $block, $block_content = '') {
    return $block['blockName'] === 'core/embed' || substr($block['blockName'], 0, 11) === 'core-embed/';
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    parent::setSettings($settings);
    $this->oembedProviders = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function provideSettings(array $form, FormStateInterface $form_state) {
    $settings['oembed'] = [
      '#type' => 'details',
      '#title' => $this->t('oEmbed settings'),
      '#weight' => 10,
      '#open' => TRUE,
    ];

    if (!$this->moduleHandler->moduleExists('media')) {
      $settings['oembed']['#description'] = $this->t('<b>Note:</b> The %media_module module is currently uninstalled, we recommended enabling it for improved oEmbed support.', [
        '%media_module' => $this->moduleHandler->getName('media'),
      ]);
    }

    $settings['oembed']['providers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Providers'),
      '#default_value' => $this->settings['oembed']['providers'],
      '#description' => $this->t(
        'A list of oEmbed providers. Add your own by adding a new line using the following pattern: <br/><code>[Url to match] | [oEmbed endpoint] | [Use regex (true or false)]</code>'
      ),
    ];

    $settings['oembed']['maxwidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum width of media embed'),
      '#field_suffix' => $this->t('pixels'),
      '#min' => 0,
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => TRUE,
      '#default_value' => $this->settings['oembed']['maxwidth'],
      '#description' => $this->t(
        'The maximum width of an embedded media, in pixels.'
      ),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'oembed' => [
        'maxwidth' => 800,
        'providers' => <<<EOL
#https?://(www\.)?youtube.com/watch.*#i | https://www.youtube.com/oembed | true
#https?://youtu\.be/\w*#i | https://www.youtube.com/oembed | true
#https?://(www\.)?vimeo\.com/\w*#i | http://vimeo.com/api/oembed.json | true
#http://(www\.)?hulu\.com/watch/.*#i | http://www.hulu.com/api/oembed.json | true
#https?://(www\.)?twitter.com/.+?/status(es)?/.*#i | https://api.twitter.com/1/statuses/oembed.json | true
#https?:\/\/(www\.)?google\.com\/maps\/embed\?pb\=.*#i | http://open.iframe.ly/api/oembed | true
#https?://maps.google.com/maps.*#i | google-maps | LOCAL
#https?://docs.google.com/(document|spreadsheet)/.*#i | google-docs | LOCAL
EOL
        ,
      ],
    ];
  }

  /**
   * Get a configured provider for the oEmbed resource.
   *
   * @param string $url
   *   The resource url to find a provider for.
   *
   * @return array
   *   Array containing [regex, provider url].
   */
  protected function getProviderUri($url) {
    foreach ($this->getOembedProviders() as $matchmask => $data) {
      $provider_url = NULL;
      $regex = NULL;

      list($provider_url, $regex) = $data;

      $regex = preg_replace('/\s+/', '', $regex);

      if ($regex === 'false') {
        $regex = FALSE;
      }

      if (!$regex) {
        $matchmask = '#' . str_replace(
            '___wildcard___',
            '(.+)',
            preg_quote(str_replace('*', '___wildcard___', $matchmask), '#')
          ) . '#i';
      }
      if (preg_match($matchmask, $url)) {
        $provider = $provider_url;

        return [$regex, $provider];
      }
    }

    return [NULL, NULL];
  }

  /**
   * Get the oEmbed provider definitions.
   *
   * @return array
   *   The oEmbed providers.
   */
  protected function getOembedProviders() {
    if ($this->oembedProviders === NULL) {
      $this->oembedProviders = [];

      $providers_string = $this->settings['oembed']['providers'];
      $providers_line = explode("\n", $providers_string);
      foreach ($providers_line as $value) {
        $items = explode(' | ', trim($value));
        if (count($items) === 3) {
          $key = array_shift($items);
          $this->oembedProviders[$key] = $items;
        }
      }
    }

    return $this->oembedProviders;
  }

  /**
   * Generate LOCAL oEmbed.
   *
   * @param string $provider
   *   The provider name.
   * @param string $url
   *   The oEmbed URL.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The oEmbed markup or original URL if a markup could not be resolved.
   *
   * @throws \Exception
   */
  protected function getLocalProviderContent($provider, $url) {
    $width = $this->settings['oembed']['maxwidth'];

    $url_separator = (strpos($url, '?') === FALSE) ? '?' : '&';

    if ($provider === 'google-maps') {
      $height = (int) ($width / 1.3);

      $render = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#attributes' => [
          'width' => $width,
          'height' => $height,
          'src' => "${url}${url_separator}output=embed",
          'frameborder' => 0,
          'class' => [
            'gutenberg-oembed-google-maps',
          ],
          'style' => 'border:0;width:100%;height:400px',
        ],
        '#suffix' => sprintf(
          '<br /><small><a href="%s" style="color:#0000FF;text-align:left">%s</a></small>',
          htmlentities("${url}${url_separator}source=embed", HTML_ENTITIES),
          $this->t('View Larger Map')->render()
        ),
        '#gutenberg_embed_local' => TRUE,
        '#gutenberg_embed_url' => $url,
      ];

      $output = $this->renderer->render($render);
    }
    elseif ($provider === 'google-docs') {
      $height = (int) ($width * 1.5);
      $render = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#attributes' => [
          'width' => $width,
          'height' => $height,
          'src' => "${url}${url_separator}widget=true",
        ],
        'frameborder' => 0,
        'class' => [
          'gutenberg-oembed-google-docs',
        ],
        '#gutenberg_embed_local' => TRUE,
        '#gutenberg_embed_url' => $url,
      ];

      $output = $this->renderer->render($render);
    }
    else {
      $output = $url;
    }

    return $output;
  }

}
