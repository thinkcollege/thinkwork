# Glossify

The Glossify module provides filters that scans and parses content and
terms in the text and allows adding links and tooltips to them.
It consists of 2 filters, one for taxonomy and one for content.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/glossify).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/glossify).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

You need to install both the Glossify module itself, but also Glossify Node
Tooltips or Glossify Taxonomy Tooltips.

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The filters included in the module can work on content or terms.

### Glossify with taxonomy

Links taxonomy terms appearing in content to their taxonomy term page.
select which taxonomy vocabularies to use as the source for the terms.
indicate whether or not matching is case sensitive.
indicate whether or not every match should be linked or just the first
occurrence. display the term definition as a tooltip while hovering the
glossified link.

### Glossify with content

Links node titles of content appearing in other content to their node page.
select which content types to use as the source for the terms.
indicate whether or not matching is case sensitive.
indicate whether or not every match should be linked or just the first
occurrence. display the text from a selected field on the linked node as a
tooltip while hovering the glossified link.

### Enable term or content filter

1. Go to Administration » Configuration » Content authoring » Text formats
   and editors
1. Edit a text format, for example "Basic HTML"
1. Enable a Glossify filter and configure it under "Filter settings"


## Maintainers

Current maintainers:

- Stefan Auditor - [sanduhrs](https://www.drupal.org/u/sanduhrs)
- Julian Pustkuchen - [Anybody](https://www.drupal.org/u/Anybody)
- keesje - [keesje](https://www.drupal.org/u/keesje)
- WorldFallz - [worldfallz](https://www.drupal.org/u/worldfallz) 
- Kobus Beljaars - [beljaako](https://www.drupal.org/u/beljaako)
- Joshua Sedler - [Grevil](https://www.drupal.org/u/Grevil)
