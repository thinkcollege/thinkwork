<?php

namespace Drupal\migrate_manifest;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class MigrateManifest {

  /**
   * @var bool
   */
  protected $update;

  /**
   * @var bool
   */
  protected $force;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new MigrateManifest object.
   *
   * @param $migration_manager
   * @param bool $force
   *   Force operation to regardless of dependencies.
   * @param bool $update
   *   Update previously imported items with current data.
   */
  public function __construct($migration_manager, $force = FALSE, $update = FALSE) {
    $this->force = $force;
    $this->update = $update;
    $this->manager = $migration_manager;
  }

  /**
   * Drush execution method. Runs imports on the supplied manifest.
   *
   * @param $manifest_file
   *   The location of the manifest file.
   *
   * @return array
   *   A list of run migrations.
   */
  public function import($manifest_file) {
    $nonexistent_migrations = [];

    if (!file_exists($manifest_file)) {
      throw new FileNotFoundException($manifest_file);
    }

    $migration_manifest = Yaml::parse(file_get_contents($manifest_file));
    $migration_info = [];
    // Standardize list of migrations.
    foreach ($migration_manifest as $manifest_row) {
      if (is_array($manifest_row)) {
        // The migration is stored as the key in the info array.
        // The info will be stored underneath that key as another array.
        // Any other info is just dropped. It can't be mapped and doesn't match
        // our expected input.
        $migration_info[key($manifest_row)] = current($manifest_row);
      }
      else {
        // If it wasn't an array then the info is just the migration_id.
        $migration_info[$manifest_row] = [];
      }
    }

    $run_migrations = [];
    foreach ($migration_info as $migration_id => $config) {
      do {
        $complete = true;
        // createInstance and createInstances doesn't follow the plugin interface
        // so this won't throw an exception. Instead we have to check the return
        // value to confirm that a migration was returned.
        // https://www.drupal.org/node/2744323
        /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
        $migration_instances = $this->manager->createInstances([$migration_id], [$migration_id => $config]);
        if (empty($migration_instances)) {
          $nonexistent_migrations[] = $migration_id;
          continue;
        }
        foreach ($migration_instances as $migration_instance) {
          if ($this->force == 2) {
            $migration_instance->set('requirements', []);
          }
          $migrations_to_run = $this->injectDependencies($migration_instance, $migration_instances);
          foreach ($migrations_to_run as $migration) {
            if (isset($run_migrations[$migration->id()])) {
              continue;
            }

            if ($this->force) {
              $migration->set('requirements', []);
            }

            if ($this->update) {
              $migration->getIdMap()->prepareUpdate();
            }

            // Store all the migrations for later.
            $run_migrations[$migration->id()] = $this->executeMigration($migration);
            if ($run_migrations[$migration->id()] == MigrationInterface::RESULT_INCOMPLETE) {
              unset($run_migrations[$migration->id()]);
              $complete = FALSE;
              break 2;
            }
          }
        }
        // Since we might be cycling because of memory leaks in the migration
        // instance, clear the references and force a gc to recover the memory.
        unset($migration_instances, $migrations_to_run);
        gc_collect_cycles();
      } while (!$complete);
    }

    // Warn the user if any migrations were not found.
    if (count($nonexistent_migrations) > 0) {
      drush_log(dt('The following migrations were not found: @migrations', [
        '@migrations' => implode(', ', $nonexistent_migrations),
      ]), 'warning');
    }

    return $run_migrations;
  }

  /**
   * A naive graph flattener for compressing dependencies into a list.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   * @param \Drupal\migrate\Plugin\MigrationInterface[] $manifest_list
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface[]
   */
  protected function injectDependencies(MigrationInterface $migration, array $manifest_list) {
    $migrations = [$migration->id() => $migration];
    if ($required_ids = $migration->get('requirements')) {
      /** @var \Drupal\migrate\Plugin\MigrationPluginManager $manager */
      $manager = \Drupal::service('plugin.manager.migration');
      /** @var \Drupal\migrate\Plugin\MigrationInterface[] $required_migrations */
      $required_migrations = [];
      foreach ($required_ids as $id) {
        // See if there are any configured versions of the migration already
        // in the manifest list.
        if (isset($manifest_list[$id])) {
          $required_migrations[$id] = $manifest_list[$id];
        }
        // Otherwise, create a new instance.
        else {
          // TODO - add migrations to manifest list to avoid duplicate creation.
          $required_migrations = $manager->createInstances($id) + $required_migrations;
        }
      }
      // Merge required migrations, using requirements as the base so they
      // bubble to the front.
      $migrations = $required_migrations + $migrations;

      // Recursively add and requirements the new migrations need.
      foreach ($required_migrations as $required_migration) {
        $migrations = $this->injectDependencies($required_migration, $manifest_list) + $migrations;
      }
    }
    return $migrations;
  }

  /**
   * Execute a single migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to run.
   *
   * @return \Drupal\migrate_manifest\MigrateExecutable
   *   The migration executable.
   */
  protected function executeMigration(MigrationInterface $migration) {
    $run_migration = unserialize(serialize($migration));
    drush_log('Running ' . $run_migration->id(), 'ok');
    $executable = new MigrateExecutable($run_migration, new DrushLogMigrateMessage());
    // drush_op() provides --simulate support.

    return drush_op([$executable, 'import']);
  }

  /**
   * Setup the legacy database connection to migrate from.
   */
  public static function setDbState($db_key, $db_url, $db_prefix) {
    if ($db_key) {
      $database_state['key'] = drush_get_option('legacy-db-key');
      $database_state_key = 'default';
      \Drupal::state()->set($database_state_key, $database_state);
      \Drupal::state()->set('migrate.fallback_state_key', $database_state_key);
    }
    else {
      if ($db_url) {
        if (function_exists('drush_convert_db_from_db_url')) {
          $db_spec = drush_convert_db_from_db_url($db_url);
        }
        elseif (class_exists('\Drush\Sql\SqlBase')) {
          $db_spec = \Drush\Sql\SqlBase::dbSpecFromDbUrl($db_url);
        }
        else {
          $db_spec = []; // support other conversion methods?
        }
        $db_spec['prefix'] = $db_prefix;
        Database::removeConnection('migrate');
        Database::addConnectionInfo('migrate', 'default', $db_spec);
      }
    }
  }

}
