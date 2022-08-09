CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers

INTRODUCTION
------------

The Glossify module provides filters that scan and parse content and adhance terms in the text with tooltips and optionally add links to their pages. Currently it consists of 2 filters:

Glossify with taxonomy

  links taxonomy terms appearing in content to their taxonomy term page.
  select which taxonomy vocabularies to use as the source for the terms.
  indicate whether or not matching is case sensitive.
  indicate whether or not every match should be linked or just the first occurrence.
  display the term definition as a tooltip while hovering the glossified link.

Glossify with content

  links node titles of content appearing in other content to their node page.
  select which content types to use as the source for the terms.
  indicate whether or not matching is case sensitive.
  indicate whether or not every match should be linked or just the first occurrence.
  display the text from a selected field on the linked node as a tooltip while hovering the glossified link.

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/glossify

* To submit bug reports and feature suggestions, or track changes:
  https://www.drupal.org/project/issues/admin_menu

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

* Configure the user permissions in Administration » Configuration » Text formats and editors:

  Configure the text format and enable the desired filter.

MAINTAINERS
-----------

Current maintainers:
* Stefan Auditor (sanduhrs) - https://www.drupal.org/u/sanduhrs
