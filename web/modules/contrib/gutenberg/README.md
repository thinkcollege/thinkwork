CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

 * Drupal Gutenberg brings the powerful content editing experience of Gutenberg
   to Drupal.

 * [Drupal](https://www.drupal.org/) +
   [Gutenberg](https://wordpress.org/gutenberg/) is a powerful combo. Drupal 8 is a rock solid CMS framework packed with powerful admin features.
   Our only complaint? Drupal 8 is missing a modern UI for rich content
   creation. Let’s change this!

 * More functionality wrapped in a smooth UI. Hundreds of hours with user
   testing. Decoupled. React.js. Clean output. Open source.

 * [Gutenberg on GitHub](https://github.com/WordPress/gutenberg/)

 * For a full description:
   https://www.drupal.org/project/gutenberg

 * Issue queue for Gutenberg:
   https://www.drupal.org/project/issues/gutenberg

  * Also check [Gutenberg-JS](https://www.npmjs.com/package/@frontkom/gutenberg-js), a stand-alone Gutenberg editor for your custom sites or web apps.

REQUIREMENTS
------------

No specific requirements.


INSTALLATION
------------

 * Install normally as other modules are installed. For Support:
   https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules

CONFIGURATION
-------------

 * To test the module, simply download it from the
   [Drupal Gutenberg project page](https://www.drupal.org/project/gutenberg)
   and enable it.

 * Then go to any content type edit page and enable *Gutenberg Experience*.

 * Assign the `use gutenberg` permission and access to the "Gutenberg" text format to all desired user roles.

DEVELOPMENT
-----------

  NodeJS 8.11+ and NPM 6.0+ are required. Alternatively Yarn 1.6+ can be used.
  * `npm install`
  * Watching file while developing: `npm start`
  * Build: `npm run build`

For custom block development, check the `example_block` sub-module.

Updating WordPress Gutenberg Packages
-----

This module uses the compiled JS packages from WordPress Gutenberg. Basically we just need to copy the files to `vendor/gutenberg`.

Steps to update WordPress Gutenberg packages:
 * Update `gutenberg-version` to the relevant version in the module's `package.json` (the `v` prefix is important).
 * Review the external vendor dependencies definitions in `scripts/generate-vendor.php` in case they need updating.
 * Run `npm run build:gutenbergjs` to build the Gutenberg JS scripts - it will also update the `gutenberg.libraries.yml` and `gutenberg.module` file.
 * Clear the Drupal cache and test that all core features are still functional.
 * Perform a full production release by running `npm run release`.

Vendor packages
-----

- React: [Build file](https://unpkg.com/react@16.8.6/umd/react.production.min.js), [project page](https://github.com/facebook/react)
- React DOM: [Build file](https://unpkg.com/react-dom@16.8.6/umd/react-dom.production.min.js), [project page](https://github.com/facebook/react)
- Lodash: [Build file](https://raw.githubusercontent.com/lodash/lodash/4.17.11-npm/lodash.min.js), [project page](https://lodash.com/)
- MomentJS: [Build file](https://momentjs.com/downloads/moment.min.js), [project page](https://momentjs.com/)
- Regenerator Runtime: [Build file](https://github.com/facebook/regenerator/blob/master/packages/regenerator-runtime/runtime.js), [project page](https://github.com/facebook/regenerator/tree/master/packages/regenerator-runtime)
- G-Media-Attributes: [Build file](https://unpkg.com/@frontkom/g-media-attributes@1.0.2/build/index.js), [project page](https://github.com/front/g-media-attributes)
- TinyMCE: [Build package](http://download.tiny.cloud/tinymce/community/tinymce_4.9.4.zip), [project page](https://www.tiny.cloud/get-tiny/)
- Gutenberg: [Project page](https://github.com/WordPress/gutenberg)
  Drupal Gutenberg relies on some JSON files (dependencies tree) generated when building Gutenberg. So, to get the correct files, the git repository must be cloned and built.

MAINTAINERS
-----------

Current maintainers:

 * Marco Fernandes (marcofernandes) - https://www.drupal.org/u/marcofernandes
 * Thor Andre Gretland (thorandre) - https://www.drupal.org/u/thorandre
 * Roberto Ornelas (roborn) - https://www.drupal.org/u/roborn
 * Paweł Pregiel (ppregiel) - https://www.drupal.org/u/ppregiel
 * codebymikey - https://www.drupal.org/u/codebymikey
