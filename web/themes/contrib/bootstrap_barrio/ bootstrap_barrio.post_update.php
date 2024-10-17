<?php

/**
 * @file
 * Post update functions for Bootstrap Barrio.
 */

/**
 * Sets the default toast widget delay of theme settings.
 */
function bootstrap_barrio_post_update_add_toast_widget_delay(): void {
  \Drupal::configFactory()->getEditable('bootstrap_barrio.settings')
    ->set('bootstrap_barrio_messages_widget_toast_delay', 10000)
    ->save();
}