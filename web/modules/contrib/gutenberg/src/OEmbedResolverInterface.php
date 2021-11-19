<?php

namespace Drupal\gutenberg;

/**
 * Resolves oEmbed urls.
 */
interface OEmbedResolverInterface {

  /**
   * Resolve a URL's oEmbed resource.
   *
   * @param string $url
   *   The url to resolve an oEmbed for.
   * @param int $maxwidth
   *   The maximum width of the oEmbed resource.
   *
   * @return string|null
   *   The resolved oEmbed.
   */
  public function resolveOembed($url, $maxwidth);

  /**
   * Fetch oEmbed HTML from a remote resource.
   *
   * This is usually the provider URL.
   *
   * @param string $url
   *   The oEmbed resource.
   *
   * @return \Drupal\Component\Render\MarkupInterface|null
   *   The oEmbed HTML.
   */
  public function fetchOembedHtml($url);

  /**
   * Get the fallback provider URI.
   *
   * @return string
   *   The fallback provider URI.
   */
  public function getDefaultFallbackOembedProviderUri();

}
