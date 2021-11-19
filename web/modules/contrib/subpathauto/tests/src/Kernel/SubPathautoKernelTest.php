<?php

namespace Drupal\Tests\subpathauto\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\subpathauto\PathProcessor
 * @group subpathauto
 */
class SubPathautoKernelTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'path_alias',
    'subpathauto',
    'node',
    'user',
  ];

  /**
   * The path processor service.
   *
   * @var \Drupal\subpathauto\PathProcessor
   */
  protected $pathProcessor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');

    $this->installConfig('subpathauto');

    // Create the node bundles required for testing.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $type->save();

    $this->pathProcessor = $this->container->get('path_processor_subpathauto');
    $aliasWhiteList = $this->container->get('path_alias.whitelist');

    Node::create(['type' => 'page', 'title' => 'test'])->save();

    $aliasStorage = \Drupal::entityTypeManager()
      ->getStorage('path_alias');

    $path_alias = $aliasStorage->create([
      'path' => '/node/1',
      'alias' => '/kittens',
    ]);
    $path_alias->save();

    $aliasWhiteList->set('node', TRUE);

    User::create(['uid' => 0, 'name' => 'anonymous user'])->save();
  }

  /**
   * @covers ::processInbound
   */
  public function testProcessInbound() {
    // Alias should not be converted for aliases that are not valid.
    $processed = $this->pathProcessor->processInbound('/kittens/are-fake', Request::create('/kittens/are-fake'));
    $this->assertEquals('/kittens/are-fake', $processed);

    // Alias should be converted on a request wih language prefix.
    $processed = $this->pathProcessor->processInbound('/kittens/edit', Request::create('/en/kittens/edit'));
    $this->assertEquals('/node/1/edit', $processed);

    // Alias should be converted even when the user doesn't have permissions to
    // view the page.
    $processed = $this->pathProcessor->processInbound('/kittens/edit', Request::create('/kittens/edit'));
    $this->assertEquals('/node/1/edit', $processed);

    // Alias should be converted because of admin user has access to edit the
    // node.
    $admin_user = $this->createUser();
    \Drupal::currentUser()->setAccount($admin_user);
    $processed = $this->pathProcessor->processInbound('/kittens/edit', Request::create('/kittens/edit'));
    $this->assertEquals('/node/1/edit', $processed);
  }

  /**
   * @covers ::processOutbound
   */
  public function testProcessOutbound() {
    // Alias should not be converted for invalid paths.
    $processed = $this->pathProcessor->processOutbound('/kittens/are-fake');
    $this->assertEquals('/kittens/are-fake', $processed);

    // Alias should be converted even when the user doesn't have permissions to
    // view the page.
    $processed = $this->pathProcessor->processOutbound('/node/1/edit');
    $this->assertEquals('/kittens/edit', $processed);

    // Alias should be converted also for user that has access to view the page.
    $admin_user = $this->createUser();
    \Drupal::currentUser()->setAccount($admin_user);
    $processed = $this->pathProcessor->processOutbound('/node/1/edit');
    $this->assertEquals('/kittens/edit', $processed);

    // Check that alias is converted for absolute paths. The Redirect module,
    // for instance, requests an absolute path when it checks if a redirection
    // is needed.
    $options = ['absolute' => TRUE];
    $processed = $this->pathProcessor->processOutbound('/node/1/edit', $options);
    $this->assertEquals('/kittens/edit', $processed);
  }

}
