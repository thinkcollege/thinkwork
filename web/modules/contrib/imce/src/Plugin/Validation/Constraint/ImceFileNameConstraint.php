<?php

namespace Drupal\imce\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the file name contains invalid characters.
 *
 * @Constraint(
 *   id = "ImceFileName",
 *   label = @Translation("Imce File Name", context = "Validation"),
 *   type = "file"
 * )
 */
class ImceFileNameConstraint extends Constraint {

  /**
   * Custom regular expression string to check against the filename.
   *
   * @var string
   */
  public $filter;

  /**
   * The error message.
   *
   * @var string
   */
  public $message = '%filename contains invalid characters.';

}
