<?php

namespace Drupal\mailgun;

/**
 * The interface for Mailgun handler service.
 */
interface MailgunHandlerInterface {

  const CONFIG_NAME = 'mailgun.settings';

  /**
   * Connects to Mailgun API and sends out the email.
   *
   * @param array $mailgunMessage
   *   A message array, as described in
   *   https://documentation.mailgun.com/en/latest/api-sending.html#sending.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted by the API, FALSE otherwise.
   *
   * @see https://documentation.mailgun.com/en/latest/api-sending.html#sending
   */
  public function sendMail(array $mailgunMessage);

  /**
   * Returns domains list from API.
   *
   * @return array
   *   Returns the list of domains. Both array keys and values are domain names,
   *   E.g.:
   *   [
   *     'domain.name' => 'domain.name',
   *   ]
   */
  public function getDomains();

  /**
   * Parses and returns domain based on the email "From" value.
   *
   * @param string $from
   *   "From" parameter of the mail message.
   *
   * @return string|bool
   *   Returns domain name or FALSE if we couldn't parse it.
   */
  public function getDomain($from);

  /**
   * Validates Mailgun library and API settings.
   *
   * @param bool $showMessage
   *   Whether error messages should be shown.
   *
   * @return bool
   *   Whether the library installed and API settings are ok.
   */
  public function moduleStatus($showMessage = FALSE);

  /**
   * Validates Mailgun API key.
   *
   * @param string $key
   *   The API key.
   *
   * @return bool
   *   Whether the API key is valid.
   */
  public function validateMailgunApiKey($key);

  /**
   * Checks if API settings are correct and not empty.
   *
   * @param bool $showMessage
   *   Whether error messages should be shown.
   *
   * @return bool
   *   Whether API settings are valid.
   */
  public function validateMailgunApiSettings($showMessage = FALSE);

  /**
   * Checks if Mailgun PHP SDK is installed correctly.
   *
   * @param bool $showMessage
   *   Whether error messages should be shown.
   *
   * @return bool
   *   Whether the Mailgun PHP SDK is installed correctly.
   */
  public function validateMailgunLibrary($showMessage = FALSE);

}
