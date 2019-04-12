CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The htmLawed module uses the htmLawed PHP library to restrict and purify HTML
for compliance with site administrator policy and standards and for security.
Use of the htmLawed library allows for highly customizable control of HTML
markup.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/htmlawed
   or https://www.drupal.org/node/255886

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/htmlawed


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the htmLawed module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.

Note: If the htmLawed PHP library has been installed through the Libraries
Drupal module, then the htmLawed module will use that library, and not the
library in the htmLawed module's 'htmLawed' sub-directory.


CONFIGURATION
-------------

To enable and/or configure the htmLawed filter, such as for a text format, visit
the text formats section of your Drupal website and configure the text format
that will use or uses the filter.

More than one text format can use the filter, each configured with its own
settings for the filter. The htmLawed filter is configured by providing values
in the form for its settings. The 'Config.' form-field is filled with
comma-separated, quoted, key-value pairs like:

```
'safe'=>1, 'element'=>'a, em, strong'
```

(These are interpreted as PHP array elements).

The 'Spec.' form-field is an optional string of unquoted text. Visit the
htmLawed documentation for more on how 'Config.' and 'Spec.' can be set, for 
instance, to permit all HTML, or restrict links to only certain domains. The
default htmLawed filter settings allow the use of the a, em, strong, cite,
code, ol, ul, li, dl, dt, dd, br and p HTML tags, and deny the id and style
HTML attributes, and any unsafe markup (such as the scriptable onclick
attribute).

Content in the 'Short tip' and 'Long tip' form-fields are used to inform users
about the filter, such as about the tags that are allowed.

To allow HTML comments such as the one used for the Drupal teaser-break
indicator (<!--break-->), add "'comment' => 2" to the 'Config.' value of the
htmLawed settings. To allow PHP codes (flanked by '<?php' and '?>') add
"'save_php' => 1" to the 'Config.' value of the htmLawed settings.

Depending on the types of other filters in use, you may need to re-arrange the
processing order of filters. The htmLawed filter would usually be the last
filter to be run. If a filter generates HTML markup and is run before htmLawed,
then htmLawed should be configured appropriately to permit such markup.

Any in-built Drupal actions/filters to restrict HTML, correct broken HTML, or
balance or properly nest HTML tags can be disabled since htmLawed performs
these tasks. The htmLawed filter can also be used to restrict HTML attributes,
limit URL protocols, etc. Note that htmLawed does not convert URLs into links
nor does it convert line breaks into HTML.

It is important to understand the security implications of the htmLawed settings
in use and the limitations of htmLawed. To keep the htmLawed library included
with the module updated, replace the 'htmLawed.php' and 'htmLawed_README.htm'
files inside the 'htmLawed' sub-directory of the htmLawed module directory
('htmlawed') with newer versions downloaded from the htmLawed website
(www.bioinformatics.org/phplabware/internal_utilities/htmLawed). If the htmLawed
library is being used through the Libraries Drupal module, use that module to
update the library.


MAINTAINERS
-----------

 * alpha2zee - https://www.drupal.org/u/alpha2zee
