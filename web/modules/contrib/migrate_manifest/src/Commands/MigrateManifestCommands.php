<?php

namespace Drupal\migrate_manifest\Commands;

use Drupal\migrate_manifest\MigrateManifest;
use Drush\Commands\DrushCommands;

/**
 * Drush 9 Migrate manifest command.
 *
 * In addition to a commandfile like this one, you need a drush.services.yml
 * in root of your module.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class MigrateManifestCommands extends DrushCommands {

  /**
   * Execute the migrations as specified in a manifest file.
   *
   * @command migrate:manifest
   * @param $manifest The path to the manifest file
   * @option legacy-db-url A Drupal 6 style database URL.
   * @option legacy-db-prefix A database table prefix to apply.
   * @option legacy-db-key A database connection key from settings.php. Use as an alternative to legacy-db-url
   * @option update In addition to processing unprocessed items from the source, update previously-imported items with the current data
   * @option force Force an operation to run, even if all dependencies are not satisfied
   * @validate-module-enabled migrate_manifest
   * @aliases migrate-manifest2,mm,migrate-manifest
   */
  public function manifest($manifest, $options = [
    'legacy-db-url' => NULL,
    'legacy-db-prefix' => NULL,
    'legacy-db-key' => NULL,
    'update' => NULL,
    'force' => NULL,
  ]) {
    try {
      $migration_manager = \Drupal::service('plugin.manager.migration');
      $manifest_runner = new MigrateManifest($migration_manager, $options['force'], $options['update']);
      MigrateManifest::setDbState($options['legacy-db-key'], $options['legacy-db-url'], $options['legacy-db-prefix']);
      $manifest_runner->import($manifest);
    }
    catch (\Exception $e) {
      drush_set_error('MIGRATE_ERROR', $e->getMessage());
    }

    drush_invoke_process('@self', 'cache-rebuild', [], [], FALSE);
  }

  /**
   * Lists migration templates available to run.
   *
   * @command migrate:template:list
   * @option tag Template tag
   * @option as-yaml Create output as yaml that can be used as a manifest.
   * @validate-module-enabled migrate_manifest
   * @aliases migrate-template-list,mml
   */
  public function templateList($options = ['tag' => NULL, 'as-yaml' => NULL]) {
    $tag = $options['tag'];
    $as_yaml = $options['as-yaml'];

    /** @var \Drupal\migrate_manifest\MigrateTemplateStorageInterface $template_storage */
    $template_storage = \Drupal::service('migrate_manifest.template_storage');
    if ($tag) {
      $templates = $template_storage->findTemplatesByTag($tag);
    }
    else {
      $templates = $template_storage->getAllTemplates();
    }

    foreach ($templates as $template) {
      $this->output()->writeln(($as_yaml ? '- ' : '') . $template['id']);
    }
  }

}
