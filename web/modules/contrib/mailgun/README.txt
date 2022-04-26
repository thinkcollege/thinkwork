* Introduction

Mailgun module provides Mailgun Mail System by integration with
Mailgun's Official SDK for PHP - https://github.com/mailgun/mailgun-php.

* Requirements

This module requires the following modules and libraries:

 - Mail System (https://www.drupal.org/project/mailsystem)
 - Mailgun SDK (https://github.com/mailgun/mailgun-php)
 - Html2Text (https://github.com/mtibben/html2text)

* Recommended modules

 - Reroute Email (https://www.drupal.org/project/reroute_email)
 - Key (https://www.drupal.org/project/key)

* Installation

Install this module as usual by Composer: composer require drupal/mailgun

* Configuration

1. Go to https://www.mailgun.com and sign up for a Mailgun account.
2. Configure API settings on the Mailgun settings page:
  admin/config/services/mailgun/settings.
3. Enable Mailgun as Default (or Module-specific) Mail System on the
  Mail System admin page (admin/config/system/mailsystem).

** Key module integration

With the Key module enabled, it is possible to store the Mailgun API key using
the Key module. This is more secure that storing the API key in configuration.

* Maintainers

 - @matroskeen (https://www.drupal.org/u/matroskeen)
 - @bohart (https://www.drupal.org/u/bohart)
