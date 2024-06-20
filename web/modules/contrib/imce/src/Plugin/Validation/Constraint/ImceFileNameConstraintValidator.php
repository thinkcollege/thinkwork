<?php

namespace Drupal\imce\Plugin\Validation\Constraint;

use Drupal\file\Plugin\Validation\Constraint\BaseFileConstraintValidator;
use Drupal\imce\Imce;
use Symfony\Component\Validator\Constraint;

/**
 * Validates imce file name constaint.
 */
class ImceFileNameConstraintValidator extends BaseFileConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    $filename = $this->assertValueIsFile($value)->getFileName();
    if (!Imce::validateFileName($filename, $constraint->filter ?? '')) {
      $this->context->addViolation($constraint->message, [
        '%filename' => $filename,
      ]);
    }
  }

}
