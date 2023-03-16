<?php

namespace Drupal\Tests\subpathauto\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\path_alias\PathAliasInterface;
use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\subpathauto\PathProcessor
 * @group subpathauto
 */
class SubPathautoKernelTest extends KernelTestBase {

  use NodeCreationTrait;
  use TestFileCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'language',
    'link',
    'node',
    'path_alias',
    'redirect',
    'subpathauto',
    'system',
    'user',
  ];

  /**
   * The path processor service.
   *
   * @var \Drupal\subpathauto\PathProcessor
   */
  protected $pathProcessor;

  /**
   * The path alias storage.
   *
   * @var \Drupal\path_alias\PathAliasStorage
   */
  protected $pathAliasStorage;

  /**
   * The redirect storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $redirectStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('redirect');
    $this->installConfig('subpathauto');
    $this->installConfig('filter');
    $this->installConfig('language');

    // Redirect module can create automatically a redirect when the alias
    // changes. The old alias redirects back to the internal path and path_alias
    // modules resolved this to the new alias.
    \Drupal::configFactory()->getEditable('redirect.settings')->set('auto_redirect', TRUE)->save();

    // Create the node bundles required for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();
    $this->pathAliasStorage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $this->redirectStorage = \Drupal::entityTypeManager()->getStorage('redirect');
    $this->pathProcessor = $this->container->get('path_processor_subpathauto');
    $this->container->get('path_alias.whitelist')->set('node', TRUE);

    User::create(['uid' => 0, 'name' => 'anonymous user'])->save();

    // Set for default language prefix default_language.
    $this->config('language.negotiation')
      ->set('url.source', LanguageNegotiationUrl::CONFIG_PATH_PREFIX)
      ->set('url.prefixes', [
        'en' => 'default_language'
      ])->save();
  }

  /**
   * Tests inbound URL processing.
   */
  public function testProcessInbound(): void {
    $this->createNode();
    $path_alias = $this->createAlias('/node/1', '/kittens');

    // Alias should not be converted for aliases that are not valid.
    $processed = $this->pathProcessor->processInbound('/kittens/are-fake', Request::create('/kittens/are-fake'));
    $this->assertSame('/kittens/are-fake', $processed);

    // Alias should be converted on a request with language prefix.
    $processed = $this->pathProcessor->processInbound('/kittens/edit', Request::create('/default_language/kittens/edit'));
    $this->assertSame('/node/1/edit', $processed);


    // Alias should be converted even when the user doesn't have permissions to
    // view the page.
    $processed = $this->pathProcessor->processInbound('/kittens/edit', Request::create('/kittens/edit'));
    $this->assertSame('/node/1/edit', $processed);

    // Alias should be converted because of admin user has access to edit the
    // node.
    $admin_user = $this->createUser();
    \Drupal::currentUser()->setAccount($admin_user);
    $processed = $this->pathProcessor->processInbound('/kittens/edit', Request::create('/kittens/edit'));
    $this->assertSame('/node/1/edit', $processed);

    // When the redirect module is enabled, changing the alias creates a
    // redirect from the old alias to the new one. Change the alias and verify
    // that the cases above still work.
    $path_alias->setAlias('/more-kittens')->save();

    // Old alias redirects properly while new ones are working normally.
    // Note that we do not test the "/more-kittens" here as it is not a sub path
    // but a simple alias, so the
    // \Drupal\subpathauto\PathProcessor::processInbound should not be
    // responsible to resolve this.
    $processed = $this->pathProcessor->processInbound('/kittens', Request::create('/default_language/kittens'));
    $this->assertSame('/node/1', $processed);

    // The 'edit' page is a link template, a subpage for the node 1. The
    // subpathauto processor should detect the base URL, find the redirect and
    // then resolve the full path.
    $processed = $this->pathProcessor->processInbound('/kittens/edit', Request::create('/default_language/kittens/edit'));
    $this->assertSame('/node/1/edit', $processed);
    $processed = $this->pathProcessor->processInbound('/more-kittens/edit', Request::create('/default_language/more-kittens/edit'));
    $this->assertSame('/node/1/edit', $processed);
  }

  /**
   * Tests outbound URL processing.
   */
  public function testProcessOutbound(): void {
    $this->createNode();
    $this->createAlias('/node/1', '/kittens');

    // Alias should not be converted for invalid paths.
    $processed = $this->pathProcessor->processOutbound('/kittens/are-fake');
    $this->assertSame('/kittens/are-fake', $processed);

    // Alias should be converted even when the user doesn't have permissions to
    // view the page.
    $processed = $this->pathProcessor->processOutbound('/node/1/edit');
    $this->assertSame('/kittens/edit', $processed);

    // Alias should be converted also for user that has access to view the page.
    $admin_user = $this->createUser();
    \Drupal::currentUser()->setAccount($admin_user);
    $processed = $this->pathProcessor->processOutbound('/node/1/edit');
    $this->assertSame('/kittens/edit', $processed);

    // Check that alias is converted for absolute paths. The Redirect module,
    // for instance, requests an absolute path when it checks if a redirection
    // is needed.
    $options = ['absolute' => TRUE];
    $processed = $this->pathProcessor->processOutbound('/node/1/edit', $options);
    $this->assertSame('/kittens/edit', $processed);

    $this->createNode();
    $this->createAlias('/node/2', '/node/1/foo');
    $processed = $this->pathProcessor->processOutbound('/node/2/edit', $options);
    // Ensure that the URL is not converted to '/kittens/foo/edit' being that
    // '/node/1' has the alias '/kittens'.
    $this->assertSame('/node/1/foo/edit', $processed);
  }

  /**
   * Tests that redirects are taken into account.
   */
  public function testAliasesAndRedirects() {
    $node = $this->createNode();
    $alias = $this->createAlias('/' . $node->toUrl()->getInternalPath(), '/node/foo');

    // Subpathauto does not process full aliases.
    $processed = $this->pathProcessor->processInbound('/node/foo', Request::create('/node/foo'));
    $this->assertSame("/node/foo", $processed);

    // Subpaths such as link templates can be processed.
    $processed = $this->pathProcessor->processInbound('/node/foo/edit', Request::create('/node/foo/edit'));
    $this->assertSame("/node/{$node->id()}/edit", $processed);

    $alias->setAlias('/node/foo/bar')->save();
    // Saving a new alias above, with the redirect set to create a redirect
    // automatically, both the old and the new alias should resolve properly
    // by the subpathauto processor.
    foreach (['/node/foo/edit', '/node/foo/bar/edit'] as $url) {
      $processed = $this->pathProcessor->processInbound($url, Request::create($url));
      $this->assertSame("/node/{$node->id()}/edit", $processed);
    }

    // In order to attempt and complicate things a bit more, create an alias for
    // the default /node page.
    $nodes_alias = $this->createAlias('/node', '/articles');
    // The '/node' to '/articles' alias is not part of the '/node/foo' which is
    // already a complete alias.
    $processed = $this->pathProcessor->processInbound('/node/foo/edit', Request::create('/node/foo/edit'));
    $this->assertSame("/node/{$node->id()}/edit", $processed);

    // Add even more complexity to the test to verify that there are no loose
    // ends.
    // Update the base node alias. This will also create a redirect from
    // '/articles' to '/node' and the alias will now be from '/node' to
    // '/more-articles'.
    $nodes_alias->setAlias('/more-articles')->save();

    // This will also create a redirect from '/node/foo' to '/node/<node ID>'
    // and an alias from the '/node/<node ID>' to '/articles/foo'.
    // However, the '/articles' is now a redirect to '/node' and from there to
    // '/more-articles'.
    $alias->setAlias('/articles/foo')->save();

    foreach (['/articles/foo/edit', '/node/foo/edit'] as $url) {
      $processed = $this->pathProcessor->processInbound($url, Request::create($url));
      $this->assertSame("/node/{$node->id()}/edit", $processed);
    }

    // Finally, add some node that mimics being a sub-node of the first one.
    $another_node = $this->createNode();
    $another_alias = $this->createAlias('/' . $another_node->toUrl()->getInternalPath(), '/articles/foo/sub-pages/new-page');

    $processed = $this->pathProcessor->processInbound('/articles/foo/sub-pages/new-page/edit', Request::create('/articles/foo/sub-pages/new-page/edit'));
    $this->assertSame("/node/{$another_node->id()}/edit", $processed);

    // Change the new alias to something simple.
    $another_alias->setAlias('/bar')->save();
    $processed = $this->pathProcessor->processInbound('/articles/foo/sub-pages/new-page/edit', Request::create('/articles/foo/sub-pages/new-page/edit'));
    $this->assertSame("/node/{$another_node->id()}/edit", $processed);
    $processed = $this->pathProcessor->processInbound('/bar/edit', Request::create('/bar/edit'));
    $this->assertSame("/node/{$another_node->id()}/edit", $processed);
  }

  /**
   * Tests that redirect support can be disabled in the settings.
   *
   * @param bool $redirect_support
   *   Whether redirects are supported.
   * @param string $expected_result
   *   The expected result.
   *
   * ::@dataProvider noRedirectSupportDataProvider
   */
  public function testNoRedirectSupport(bool $redirect_support, string $expected_result) {
    $this->createNode();
    $alias = $this->createAlias('/node/1', '/foo/bar');
    $alias->save();
    $alias->setAlias('/xyz')->save();

    \Drupal::configFactory()->getEditable('subpathauto.settings')->set('redirect_support', $redirect_support)->save();
    $processed = $this->pathProcessor->processInbound('/foo/bar/edit', Request::create('/foo/bar/edit'));
    $this->assertSame($expected_result, $processed);
  }

  /**
   * Returns test cases for the testNoRedirectSupport.
   *
   * @return array
   *   An array of test cases, each of which contains whether redirect is
   *   supported and the expected string.
   */
  public function noRedirectSupportDataProvider(): array {
    return [
      'redirect is supported' => [TRUE, '/node/1/edit'],
      'redirect is not supported' => [FALSE, '/foo/bar/edit'],
    ];
  }

  /**
   * Tests that redirects are not supported if the module is not installed.
   */
  public function testNoRedirectInstalled() {
    $this->createNode();
    $alias = $this->createAlias('/node/1', '/foo/bar');
    $alias->save();
    $alias->setAlias('/xyz')->save();

    \Drupal::configFactory()->getEditable('subpathauto.settings')->set('redirect_support', TRUE)->save();
    $this->disableModules(['redirect']);
    $processed = $this->pathProcessor->processInbound('/foo/bar/edit', Request::create('/foo/bar/edit'));
    $this->assertSame('/foo/bar/edit', $processed);
  }

  /**
   * Tests that redirects pointing externally, do not break the aliases.
   */
  public function testExternalRedirects() {
    $this->createNode();
    // Assume that for some reason, an external URL is used for the landing page
    // of node 1.
    $this->createRedirect('/node/1', 'http://example.com');
    $processed = $this->pathProcessor->processInbound('/node/1/edit', Request::create('/node/1/edit'));
    $this->assertSame("/node/1/edit", $processed);

    // Ensure that even if the redirect is skipped, the rest of the parts are
    // processed properly.
    $this->createAlias('/node', '/content');
    $processed = $this->pathProcessor->processInbound('/content/1/edit', Request::create('/content/1/edit'));
    $this->assertSame("/node/1/edit", $processed);
  }

  /**
   * Tests the case when a redirect file destination doesn't exist.
   *
   * @see https://www.drupal.org/project/subpathauto/issues/3175637#comment-14293730
   */
  public function testRedirectToFile(): void {
    $source = str_replace('public://', '/sites/default/files/', current($this->getTestFiles('html'))->uri);
    $this->createRedirect($source, '/foo/bar/baz/myfile.pdf');
    $this->pathProcessor->processInbound($source, Request::create($source));
  }

  /**
   * Creates and returns an alias.
   *
   * @param string $path
   *   The source URL.
   * @param string $alias
   *   The target URL.
   *
   * @return \Drupal\path_alias\PathAliasInterface
   *   The created alias.
   */
  protected function createAlias(string $path, string $alias): PathAliasInterface {
    /** @var \Drupal\path_alias\PathAliasInterface $alias */
    $alias = $this->pathAliasStorage->create([
      'path' => $path,
      'alias' => $alias,
    ]);
    $alias->save();
    return $alias;
  }

  /**
   * Creates and returns a redirect entity.
   *
   * @param string $path
   *   The redirect source path.
   * @param string $redirect_uri
   *   The redirect URI.
   *
   * @return \Drupal\redirect\Entity\Redirect
   *   The redirect entity.
   */
  protected function createRedirect(string $path, string $redirect_uri): Redirect {
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->redirectStorage->create();
    $redirect->setSource($path);
    $redirect->setRedirect($redirect_uri);
    $redirect->setLanguage('en');
    $redirect->setStatusCode(301);
    $redirect->save();
    return $redirect;
  }

}
