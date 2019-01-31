<?php

/**
 * @file
 * Allow manifests to be run from the command line.
 */

use Drupal\migrate_manifest\MigrateManifest;

/**
 * Implements hook_drush_command().
 */
function migrate_manifest_drush_command() {
  $items['migrate-manifest'] = [
    'description' => 'Execute the migrations as specified in a manifest file.',
    'arguments' => [
      'manifest' => 'The path to the manifest file',
    ],
    'required-arguments' => 1,
    'options' => [
      'legacy-db-url' => [
        'description' => 'A Drupal 6 style database URL.',
        'example-value' => 'mysql://root:pass@127.0.0.1/db',
        'required' => FALSE,
      ],
      'legacy-db-prefix' => [
        'description' => 'A database table prefix to apply.',
        'example-value' => 'dr_',
        'required' => FALSE,
      ],
      'legacy-db-key' => 'A database connection key from settings.php. Use as an alternative to legacy-db-url',
      'update' => ' In addition to processing unprocessed items from the source, update previously-imported items with the current data',
      'force' => 'Force an operation to run, even if all dependencies are not satisfied',
    ],
    'drupal dependencies' => ['migrate_manifest'],
    // Maintained for compatibility with initial release.
    'aliases' => ['migrate-manifest2', 'mm'],
  ];
  $items['migrate-template-list'] = [
    'description' => 'Lists migration templates available to run.',
    'options' => [
      'tag' => [
        'description' => 'Template tag',
        'example-value' => 'Drupal 6',
        'required' => FALSE,
      ],
      'as-yaml' => [
        'description' => 'Create output as yaml that can be used as a manifest.',
        'required' => FALSE,
      ],
    ],
    'drupal dependencies' => ['migrate_manifest'],
    'aliases' => ['mml'],
  ];

  return $items;
}

/**
 * Import from a manifest file.
 *
 * @param string $manifest
 *   The path to the manifest file.
 *
 * @return array
 *   A list of run migrations.
 */
function drush_migrate_manifest($manifest) {
  try {
    $manager = \Drupal::service('plugin.manager.migration');
    $manifest_runner = new MigrateManifest($manager, drush_get_option('force'), drush_get_option('update'));
    MigrateManifest::setDbState(
      drush_get_option('legacy-db-key'),
      drush_get_option('legacy-db-url'),
      drush_get_option('legacy-db-prefix')
    );
    return $manifest_runner->import($manifest);
  } catch (\Exception $e) {
    drush_set_error('MIGRATE_ERROR', $e->getMessage());
  }

  drush_invoke_process('@self', 'cache-rebuild', [], [], FALSE);
}

/**
 * List templates.
 */
function drush_migrate_manifest_migrate_template_list() {
  /** @var \Drupal\migrate_manifest\MigrateTemplateStorageInterface $template_storage */
  $template_storage = \Drupal::service('migrate_manifest.template_storage');

  $tag = drush_get_option('tag');
  if ($tag) {
    $templates = $template_storage->findTemplatesByTag($tag);
  }
  else {
    $templates = $template_storage->getAllTemplates();
  }

  $as_yaml = drush_get_option('as-yaml');

  foreach ($templates as $template) {
    drush_print(($as_yaml ? '- ' : '') . $template['id']);
  }
}
