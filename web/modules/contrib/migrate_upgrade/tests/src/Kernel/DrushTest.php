<?php

namespace Drupal\Tests\migrate_upgrade\Kernel {

  use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
  use Drupal\migrate_plus\Entity\Migration;
  use Drupal\migrate_upgrade\Commands\MigrateUpgradeCommands;
  use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
  use Drupal\Tests\migrate_drupal\Traits\CreateMigrationsTrait;

  /**
   * Tests the drush command runner for migrate upgrade.
   *
   * @group migrate_upgrade
   *
   * @requires module migrate_plus
   */
  class DrushTest extends MigrateDrupalTestBase {

    use FileSystemModuleDiscoveryDataProviderTrait;
    use CreateMigrationsTrait;

    /**
     * The migration plugin manager.
     *
     * @var \Drupal\migrate\Plugin\MigrationPluginManager
     */
    protected $migrationManager;

    /**
     * The Migrate Upgrade Command drush service.
     *
     * @var \Drupal\migrate_upgrade\Commands\MigrateUpgradeCommands
     */
    protected $commands;

    /**
     * The state service.
     *
     * @var \Drupal\Core\State\StateInterface
     */
    protected $state;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
      // Enable all modules.
      self::$modules = array_merge(array_keys($this->coreModuleListDataProvider()), [
        'migrate_plus',
        'migrate_upgrade',
      ]);
      parent::setUp();
      $this->installSchema('system', ['key_value', 'key_value_expire']);
      $this->installConfig(self::$modules);
      $this->installEntitySchema('migration_group');
      $this->installEntitySchema('migration');
      $this->migrationManager = \Drupal::service('plugin.manager.migration');
      $this->state = $this->container->get('state');
      $this->commands = new MigrateUpgradeCommands($this->state);
    }

    /**
     * Tests that all D6 migrations are generated as migrate plus entities.
     */
    public function testD6Migrations() {
      $skipped_migrations = [
        'upgrade_d6_entity_reference_translation_comment__comment_forum',
      ];
      $migrations = $this->drupal6Migrations();
      $options = [
        'configure-only' => TRUE,
        'legacy-db-key' => $this->sourceDatabase->getKey(),
      ];
      $this->commands->upgrade($options);
      $migrate_plus_migrations = Migration::loadMultiple();
      $this->assertMigrations($migrations, $migrate_plus_migrations, $skipped_migrations);
      $optional = array_flip($migrate_plus_migrations['upgrade_d6_url_alias']->toArray()['migration_dependencies']['optional']);
      $this->assertArrayHasKey('upgrade_d6_node_translation_page', $optional);
    }

    /**
     * Tests that all D7 migrations are generated as migrate plus entities.
     */
    public function testD7Migrations() {
      $skipped_migrations = [
        'upgrade_d7_entity_reference_translation_comment__comment_forum',
      ];
      $migrations = $this->drupal7Migrations();
      $this->sourceDatabase->update('system')
        ->fields(['status' => 1])
        ->condition('name', 'profile')
        ->execute();
      $options = [
        'configure-only' => TRUE,
        'legacy-db-key' => $this->sourceDatabase->getKey(),
      ];
      $this->commands->upgrade($options);
      $migrate_plus_migrations = Migration::loadMultiple();
      $this->assertMigrations($migrations, $migrate_plus_migrations, $skipped_migrations);
      $optional = array_flip($migrate_plus_migrations['upgrade_d7_url_alias']->toArray()['migration_dependencies']['optional']);
      $this->assertArrayHasKey('upgrade_d7_node_translation_page', $optional);
    }

    /**
     * Asserts that all migrations are exported as migrate plus entities.
     *
     * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
     *   The migrations.
     * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $migrate_plus_migrations
     *   The migrate plus config entities.
     * @param array $skipped_migrations
     *   The migrations to skip.
     */
    protected function assertMigrations(array $migrations, array $migrate_plus_migrations, array $skipped_migrations) {
      foreach ($migrations as $id => $migration) {
        $migration_id = 'upgrade_' . str_replace(':', '_', $migration->id());
        if (in_array($migration_id, $skipped_migrations, TRUE)) {
          continue;
        }
        $this->assertArrayHasKey($migration_id, $migrate_plus_migrations);
      }
    }

  }

}

namespace {

  if (!function_exists('drush_print')) {

    /**
     * Stub for drush_print.
     *
     * @param string $message
     *   The message to print.
     * @param int $indent
     *   The indentation (space chars)
     * @param resource $handle
     *   File handle to write to.  NULL will write to standard output, STDERR
     *   will write to the standard error. See
     *   http://php.net/manual/en/features.commandline.io-streams.php.
     * @param bool $newline
     *   Add a "\n" to the end of the output.  Defaults to TRUE.
     */
    function drush_print($message = '', $indent = 0, $handle = NULL, $newline = TRUE) {
      // Do nothing.
    }

  }

  if (!function_exists('dt')) {

    /**
     * Stub for dt().
     *
     * @param string $message
     *   The text.
     * @param array $replace
     *   The replacement values.
     *
     * @return string
     *   The text.
     */
    function dt($message, array $replace = []) {
      return strtr($message, $replace);
    }

  }

  if (!function_exists('drush_op')) {

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

  }

}
