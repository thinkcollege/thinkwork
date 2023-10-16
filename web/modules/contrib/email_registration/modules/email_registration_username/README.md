# Email Registration Username module

This module updates a user's username with their email-address (on user
creation) and keeps both values in sync (if they had them previously synced).

This module also provides an action, to update a user's username with their
email-address. This action automatically replaces all occurrences of the
main module's action logic to update the username, including the action on the
core "People" view.

## Security implications

Having the email-address as the username could result to leaked email-addresses
(see https://www.drupal.org/drupal-security-team/security-team-procedures/disclosure-of-usernames-and-user-ids-is-not-considered-a-weakness).
The option to obfuscate the users display name will elevate this security
implication slightly.

## Installation

Install as you would normally install a contributed Drupal module. For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

Go to "Configuration -> People -> Account Settings"
(`/admin/config/people/accounts`) to configure this module's options:
- "Enable account display name obfuscation" (`obfuscate_display_name`)
  - Enables obfuscation of the users display name. This will slightly elevate 
  the security implications of using the mail address as the username.
- "Obfuscation value" (`obfuscation_value`)
  - Enter text to obfuscate a users display name with. This field supports
  token.
