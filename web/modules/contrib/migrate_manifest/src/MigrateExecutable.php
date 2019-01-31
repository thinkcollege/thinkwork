<?php

namespace Drupal\migrate_manifest;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

class MigrateExecutable extends \Drupal\migrate\MigrateExecutable {

  /**
   * {@inheritdoc}
   */
  public function import() {
    // Only begin the import operation if the migration is currently idle.
    if ($this->migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      $this->message->display($this->t('Migration @id is busy with another operation: @status',
        [
          '@id' => $this->migration->id(),
          '@status' => $this->t($this->migration->getStatusLabel()),
        ]), 'error');
      return MigrationInterface::RESULT_FAILED;
    }
    $this->getEventDispatcher()->dispatch(MigrateEvents::PRE_IMPORT, new MigrateImportEvent($this->migration, $this->message));

    // Knock off migration if the requirements haven't been met.
    try {
      $this->migration->checkRequirements();
    }
    catch (RequirementsException $e) {
      $this->message->display(
        $this->t(
          'Migration @id did not meet the requirements. @message @requirements',
          [
            '@id' => $this->migration->id(),
            '@message' => $e->getMessage(),
            '@requirements' => $e->getRequirementsString(),
          ]
        ),
        'error'
      );

      return MigrationInterface::RESULT_FAILED;
    }

    $this->migration->setStatus(MigrationInterface::STATUS_IMPORTING);
    $return = MigrationInterface::RESULT_COMPLETED;
    $source = $this->getSource();
    $destination = $this->migration->getDestinationPlugin();

    try {
      foreach ($source as $row) {
        $this->importRow($row, $destination);
        // Check for memory exhaustion.
        if (($return = $this->checkStatus()) != MigrationInterface::RESULT_COMPLETED) {
          break;
        }

        // If anyone has requested we stop, return the requested result.
        if ($this->migration->getStatus() == MigrationInterface::STATUS_STOPPING) {
          $return = $this->migration->getInterruptionResult();
          $this->migration->clearInterruptionResult();
          break;
        }
      }
    }
    catch (\Exception $e) {
      $this->message->display(
        $this->t('Migration failed with source plugin exception: @e', ['@e' => $e->getMessage()]), 'error');
      $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
      return MigrationInterface::RESULT_FAILED;
    }

    $this->getEventDispatcher()->dispatch(MigrateEvents::POST_IMPORT, new MigrateImportEvent($this->migration, $this->message));
    $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
    return $return;
  }

  /**
   * Helper method that imports a single row.
   *
   * @param \Drupal\migrate\Row $row
   * @param \Drupal\migrate\Plugin\MigrateDestinationInterface $destination
   */
  private function importRow(Row $row, MigrateDestinationInterface $destination) {

    $id_map = $this->migration->getIdMap();

    // Hide values in internal property so saveMessages can use them.
    $this->sourceIdValues = $row->getSourceIdValues();

    try {
      $this->processRow($row);
      $this->getEventDispatcher()->dispatch(MigrateEvents::PRE_ROW_SAVE, new MigratePreRowSaveEvent($this->migration, $this->message, $row));
      $destination_ids = $id_map->lookupDestinationIds($this->sourceIdValues);
      $destination_id_values = $destination_ids ? reset($destination_ids) : [];
      $destination_id_values = $destination->import($row, $destination_id_values);
      $this->getEventDispatcher()->dispatch(MigrateEvents::POST_ROW_SAVE, new MigratePostRowSaveEvent($this->migration, $this->message, $row, $destination_id_values));
      if ($destination_id_values) {
        // We do not save an idMap entry for config.
        if ($destination_id_values !== TRUE) {
          $id_map->saveIdMapping($row, $destination_id_values, $this->sourceRowStatus, $destination->rollbackAction());
        }
      }
      else {
        $id_map->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_FAILED);
        if (!$id_map->messageCount()) {
          $message = $this->t('New object was not saved, no error provided');
          $this->saveMessage($message);
          $this->message->display($message);
        }
      }
    }
    catch (MigrateException $e) {
      $id_map->saveIdMapping($row, [], $e->getStatus());
      $this->saveMessage($e->getMessage(), $e->getLevel());
    }
    catch (MigrateSkipRowException $e) {
      if ($e->getSaveToMap()) {
        $id_map->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_IGNORED);
      }
      if ($message = trim($e->getMessage())) {
        $this->saveMessage($message, MigrationInterface::MESSAGE_INFORMATIONAL);
      }
    }
    catch (\Exception $e) {
      $id_map->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_FAILED);
      $this->handleException($e);
    }

    $this->sourceRowStatus = MigrateIdMapInterface::STATUS_IMPORTED;
  }

}
