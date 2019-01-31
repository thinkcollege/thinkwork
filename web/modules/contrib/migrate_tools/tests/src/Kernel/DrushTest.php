<?php

namespace Drupal\Tests\migrate_tools\Kernel;

use Drupal\migrate_tools\Commands\MigrateToolsCommands;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests for the Drush 9 commands.
 *
 * @group migrate_tools
 */
class DrushTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate_tools_test',
    'migrate_tools',
    'migrate_plus',
    'taxonomy',
    'text',
    'system',
  ];

  /**
   * Base options array for import.
   *
   * @var array
   */
  protected $importBaseOptions = [
    'all' => NULL,
    'group' => NULL,
    'tag' => NULL,
    'limit' => NULL,
    'feedback' => NULL,
    'idlist' => NULL,
    'idlist-delimiter' => ':',
    'update' => NULL,
    'force' => NULL,
    'execute-dependencies' => NULL,
  ];

  /**
   * The Migrate Tools Command drush service.
   *
   * @var \Drupal\migrate_tools\Commands\MigrateToolsCommands
   */
  protected $commands;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig('migrate_plus');
    $this->installConfig('migrate_tools_test');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('system', ['key_value', 'key_value_expire']);
    $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
    $this->logger = $this->container->get('logger.channel.migrate_tools');
    $this->commands = new MigrateToolsCommands(
      $this->migrationPluginManager,
      $this->container->get('date.formatter'),
      $this->container->get('entity_type.manager'),
      $this->container->get('keyvalue'));
    $this->commands->setLogger($this->logger);
  }

  /**
   * Tests drush ms.
   */
  public function testStatus() {
    $this->executeMigration('fruit_terms');
    /** @var \Consolidation\OutputFormatters\StructuredData\RowsOfFields $result */
    $result = $this->commands->status('fruit_terms');
    $rows = $result->getArrayCopy();
    $this->assertSame(1, count($rows));
    $row = reset($rows);
    $this->assertSame('fruit_terms', $row['id']);
    $this->assertSame(3, $row['total']);
    $this->assertSame(3, $row['imported']);
    $this->assertSame('Idle', $row['status']);
  }

  /**
   * Tests drush mim.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testImport() {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance('fruit_terms');
    $id_map = $migration->getIdMap();
    $this->commands->import('fruit_terms', ['idlist' => 'Apple'] + $this->importBaseOptions);
    $this->assertSame(1, $id_map->importedCount());
    $this->commands->import('fruit_terms');
    $this->assertSame(3, $id_map->importedCount());
    $this->commands->import('fruit_terms', ['idlist' => 'Apple', 'update' => TRUE] + $this->importBaseOptions);
    $this->assertSame(0, count($id_map->getRowsNeedingUpdate(100)));
  }

  /**
   * Tests drush mmsg.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testMessages() {
    $this->executeMigration('fruit_terms');
    $message = $this->getRandomGenerator()->string(16);
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance('fruit_terms');
    $id_map = $migration->getIdMap();
    $id_map->saveMessage(['name' => 'Apple'], $message);
    /** @var \Consolidation\OutputFormatters\StructuredData\RowsOfFields $result */
    $result = $this->commands->messages('fruit_terms');
    $rows = $result->getArrayCopy();
    $this->assertSame($message, $rows[0]['message']);
  }

  /**
   * Tests drush mr.
   */
  public function testRollback() {
    $this->executeMigration('fruit_terms');
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance('fruit_terms');
    $id_map = $migration->getIdMap();
    $this->assertSame(3, $id_map->importedCount());
    $this->commands->rollback('fruit_terms');
    $this->assertSame(0, $id_map->importedCount());
  }

  /**
   * Tests drush mrs.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testReset() {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance('fruit_terms');
    $migration->setStatus(MigrationInterface::STATUS_IMPORTING);
    $this->assertSame('Importing', $this->commands->status('fruit_terms')->getArrayCopy()[0]['status']);
    $this->commands->resetStatus('fruit_terms');
    $this->assertSame(MigrationInterface::STATUS_IDLE, $migration->getStatus());

  }

  /**
   * Tests drush mst.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testStop() {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance('fruit_terms');
    $migration->setStatus(MigrationInterface::STATUS_IMPORTING);
    $this->commands->stop('fruit_terms');
    $this->assertSame(MigrationInterface::STATUS_STOPPING, $migration->getStatus());
  }

  /**
   * Tests drush mfs.
   */
  public function testFieldsSource() {
    /** @var \Consolidation\OutputFormatters\StructuredData\RowsOfFields $result */
    $result = $this->commands->fieldsSource('fruit_terms');
    $rows = $result->getArrayCopy();
    $this->assertSame(1, count($rows));
    $this->assertSame('name', $rows[0]['machine_name']);
    $this->assertSame('name', $rows[0]['description']);
  }

}

namespace Drupal\migrate_tools\Commands;

/**
 * Stub for drush_op.
 *
 * @param callable $callable
 *   The function to call.
 */
function drush_op(callable $callable) {
  $args = func_get_args();
  array_shift($args);
  call_user_func_array($callable, $args);
}

/**
 * Stub for dt().
 *
 * @param string $text
 *   The text.
 *
 * @return string
 *   The text.
 */
function dt($text) {
  return $text;
}
