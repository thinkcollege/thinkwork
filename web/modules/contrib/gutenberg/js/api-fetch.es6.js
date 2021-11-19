/* eslint func-names: ["error", "never"] */
(function(wp, Drupal, drupalSettings, $) {
  /**
   * Parse query strings into an object.
   * @see https://stackoverflow.com/a/2880929
   *
   * @param {string} query The query string
   *
   * @return {object} The decoded query string as an object.
   */
  function parseQueryStrings(query) {
    let match;
    const urlParams = {};
    // Regex for replacing addition symbol with a space
    const pl = /\+/g;
    const search = /([^&=]+)=?([^&]*)/g;
    const decode = function(s) {
      return decodeURIComponent(s.replace(pl, ' '));
    };

    // eslint-disable-next-line no-cond-assign
    while ((match = search.exec(query)) !== null) {
      if (decode(match[1]) in urlParams) {
        if (!Array.isArray(urlParams[decode(match[1])])) {
          urlParams[decode(match[1])] = [urlParams[decode(match[1])]];
        }
        urlParams[decode(match[1])].push(decode(match[2]));
      } else {
        urlParams[decode(match[1])] = decode(match[2]);
      }
    }

    return urlParams;
  }

  /**
   * Handles API errors.
   *
   * @param {object} errorResponse The error object.
   * @param {function} reject The promise reject callback.
   * @param {string|null} fallbackMessage The fallback error message.
   */
  function errorHandler(errorResponse, reject, fallbackMessage = null) {
    let errorMessage;
    let rawHTML = false;
    if (errorResponse && errorResponse.responseJSON) {
      const responseJSON = errorResponse.responseJSON;
      if (typeof responseJSON.error === 'string') {
        errorMessage = responseJSON.error;
      } else if (typeof responseJSON.message === 'string') {
        // ExceptionJsonSubscriber error handler.
        errorMessage = responseJSON.message;
      }
      if (errorMessage && responseJSON.rawHTML) {
        rawHTML = responseJSON.rawHTML;
      }
    }

    if (!errorMessage && fallbackMessage) {
      errorMessage = fallbackMessage;
    }

    if (errorMessage) {
      Drupal.notifyError(errorMessage, rawHTML);
    } else {
      // eslint-disable-next-line no-console
      console.warn(
        `API error: unexpected error message: ${JSON.stringify(errorResponse)}`,
      );
    }

    reject(errorResponse);
  }

  const types = {
    page: {
      id: 1,
      labels: {
        singular_name: 'Node',
        Document: Drupal.t('Node'),
        document: Drupal.t('Node'),
        posts: Drupal.t('Nodes'),
      },
      name: 'Page',
      rest_base: 'pages',
      slug: 'page',
      supports: {
        author: false,
        comments: false, // hide discussion-panel
        'custom-fields': true,
        editor: true,
        excerpt: false,
        discussion: false,
        'page-attributes': false, // hide page-attributes panel
        revisions: false,
        thumbnail: false, // featured-image panel
        title: false, // show title on editor
      },
      taxonomies: [],
    },
    block: {
      capabilities: {},
      labels: {
        singular_name: 'Block',
      },
      name: 'Blocks',
      rest_base: 'blocks',
      slug: 'wp_block',
      description: '',
      hierarchical: false,
      supports: {
        title: true,
        editor: true,
      },
      viewable: true,
    },
  };

  const user = {
    id: 1,
    name: 'Human Made',
    url: '',
    description: '',
    link: 'https://demo.wp-api.org/author/humanmade/',
    slug: 'humanmade',
    avatar_urls: {
      24: 'http://2.gravatar.com/avatar/83888eb8aea456e4322577f96b4dbaab?s=24&d=mm&r=g',
      48: 'http://2.gravatar.com/avatar/83888eb8aea456e4322577f96b4dbaab?s=48&d=mm&r=g',
      96: 'http://2.gravatar.com/avatar/83888eb8aea456e4322577f96b4dbaab?s=96&d=mm&r=g',
    },
    meta: [],
    _links: {
      self: [],
      collection: [],
    },
  };

  const requestPaths = {
    'save-page': {
      method: 'PUT',
      regex: /\/wp\/v2\/pages\/(\d*)/g,
      process(matches, data) {
        const date = new Date().toISOString();

        window.wp.node = {
          id: 1,
          type: 'page',
          date,
          date_gmt: date,
          title: {
            raw: document.title,
            rendered: document.title,
          },
          status: 'draft',
          content: {
            raw: data.content,
            rendered: data.content,
          },
        };

        return new Promise(resolve => {
          resolve(window.wp.node);
        });
      },
    },
    'load-node': {
      method: 'GET',
      regex: /\/wp\/v2\/pages\/(\d*)/g,
      process() {
        return new Promise(resolve => {
          resolve(window.wp.node);
        });
      },
    },
    'media-options': {
      method: 'OPTIONS',
      regex: /\/wp\/v2\/media/g,
      process() {
        return new Promise(resolve => {
          resolve({
            headers: {
              get: value => {
                if (value === 'allow') {
                  return ['POST'];
                }
              },
            },
          });
        });
      },
    },
    'load-media': {
      method: 'GET',
      regex: /\/wp\/v2\/media\/((\d+)(.*))/,
      process(matches) {
        return new Promise((resolve, reject) => {
          Drupal.toggleGutenbergLoader('show');
          $.ajax({
            method: 'GET',
            url: Drupal.url(`editor/media/load/${matches[1]}`),
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            })
            .always(() => {
              Drupal.toggleGutenbergLoader('hide');
            });
        });
      },
    },
    'save-media': {
      method: 'POST',
      regex: /\/wp\/v2\/media/g,
      process(matches, data, body) {
        return new Promise((resolve, reject) => {
          let file;
          const entries = body.entries();

          for (const pair of entries) {
            if (pair[0] === 'file') {
              /* eslint prefer-destructuring: ["error", {"array": false}] */
              file = pair[1];
            }
          }

          const formData = new FormData();
          formData.append('files[fid]', file);
          formData.append('fid[fids]', '');
          formData.append('attributes[alt]', 'Test');
          formData.append('_drupal_ajax', '1');
          formData.append('form_id', $('[name="form_id"]').val());
          formData.append('form_build_id', $('[name="form_build_id"]').val());
          formData.append('form_token', $('[name="form_token"]').val());

          Drupal.toggleGutenbergLoader('show');
          $.ajax({
            method: 'POST',
            // TODO match the current editor instance dynamically.
            url: Drupal.url('editor/media/upload/gutenberg'),
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
          })
            .done(result => {
              if (Drupal.isMediaEnabled()) {
                Drupal.notifySuccess(
                  Drupal.t(
                    'File and media entity have been created successfully.',
                  ),
                );
              } else {
                Drupal.notifySuccess(
                  Drupal.t('File entity has been created successfully.'),
                );
              }
              resolve(result);
            })
            .fail(error => {
              errorHandler(error, reject);
            })
            .always(() => {
              Drupal.toggleGutenbergLoader('hide');
            });
        });
      },
    },
    'load-medias': {
      method: 'GET',
      regex: /\/wp\/v2\/media/g,
      process() {
        return new Promise(resolve => {
          resolve([]);
        });
      },
    },
    'load-media-library-dialog': {
      method: 'GET',
      regex: /load-media-library-dialog/g,
      process(matches, data) {
        Drupal.toggleGutenbergLoader('show');
        return new Promise((resolve, reject) => {
          $.ajax({
            method: 'GET',
            url: Drupal.url('editor/media/dialog'),
            data: {
              types: (data.allowedTypes || []).join(','),
            },
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            })
            .always(() => {
              Drupal.toggleGutenbergLoader('hide');
            });
        });
      },
    },
    'load-media-edit-dialog': {
      method: 'GET',
      regex: /load-media-edit-dialog/g,
      process(matches, data) {
        // FIXME is this actually used?
        Drupal.toggleGutenbergLoader('show');
        return new Promise((resolve, reject) => {
          $.ajax({
            method: 'GET',
            url: Drupal.url('media/6/edit'),
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            })
            .always(() => {
              Drupal.toggleGutenbergLoader('hide');
            });
        });
      },
    },
    categories: {
      method: 'GET',
      regex: /\/wp\/v2\/categories\?(.*)/g,
      process() {
        return new Promise(resolve => {
          resolve([]);
        });
      },
    },
    users: {
      method: 'GET',
      regex: /\/wp\/v2\/users\/\?(.*)/g,
      process() {
        return new Promise(resolve => {
          resolve([user]);
        });
      },
    },
    taxonomies: {
      method: 'GET',
      regex: /\/wp\/v2\/taxonomies/g,
      process() {
        return new Promise(resolve => {
          resolve([]);
        });
      },
    },
    embed: {
      method: 'GET',
      regex: /\/oembed\/1\.0\/proxy\?(.*)/g,
      process(matches) {
        return new Promise((resolve, reject) => {
          const data = parseQueryStrings(matches[1]);
          data.maxWidth = data.maxWidth || 800;

          $.ajax({
            method: 'GET',
            url: Drupal.url('editor/oembed'),
            data,
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            });
        });
      },
    },
    root: {
      method: 'GET',
      regex: /(^\/$|^$)/g,
      process() {
        return new Promise(resolve =>
          resolve({
            theme_supports: {
              formats: [
                'standard',
                'aside',
                'image',
                'video',
                'quote',
                'link',
                'gallery',
                'audio',
              ],
              'post-thumbnails': true,
            },
          }),
        );
      },
    },
    themes: {
      method: 'GET',
      regex: /\/wp\/v2\/themes\?(.*)/g,
      process() {
        return new Promise(resolve =>
          resolve([
            {
              theme_supports: {
                formats: [
                  'standard',
                  'aside',
                  'image',
                  'video',
                  'quote',
                  'link',
                  'gallery',
                  'audio',
                ],
                'post-thumbnails': true,
                'responsive-embeds': false,
              },
            },
          ]),
        );
      },
    },

    'load-type-page': {
      method: 'GET',
      regex: /\/wp\/v2\/types\/page/g,
      process() {
        return new Promise(resolve => resolve(types.page));
      },
    },
    'load-type-block': {
      method: 'GET',
      regex: /\/wp\/v2\/types\/wp_block/g,
      process() {
        return new Promise(resolve => resolve(types.block));
      },
    },
    'load-types': {
      method: 'GET',
      regex: /\/wp\/v2\/types($|\?(.*))/g,
      process() {
        return new Promise(resolve => resolve(types));
      },
    },

    'update-block': {
      method: 'PUT',
      regex: /\/wp\/v2\/blocks\/(\d+)/g,
      process(matches, data) {
        return new Promise((resolve, reject) => {
          $.ajax({
            method: 'PUT',
            url: Drupal.url(`editor/reusable-blocks/${data.id}`),
            data,
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            });
        });
      },
    },

    'delete-block': {
      method: 'DELETE',
      regex: /\/wp\/v2\/blocks\/(\d+)/g,
      process(matches) {
        return new Promise((resolve, reject) => {
          $.ajax({
            method: 'DELETE',
            url: Drupal.url(`editor/reusable-blocks/${matches[1]}`),
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            });
        });
      },
    },

    'insert-block': {
      method: 'POST',
      regex: /\/wp\/v2\/blocks/g,
      process(matches, data) {
        return new Promise((resolve, reject) => {
          const formData = new FormData();
          formData.append('title', data.title);
          formData.append('content', data.content);

          $.ajax({
            method: 'POST',
            url: Drupal.url('editor/reusable-blocks'),
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            });
        });
      },
    },
    'load-block': {
      method: 'GET',
      regex: /\/wp\/v2\/blocks\/(\d*)/g,
      process(matches) {
        return new Promise((resolve, reject) => {
          $.ajax({
            method: 'GET',
            url: Drupal.url(`editor/reusable-blocks/${matches[1]}`),
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            });
        });
      },
    },
    'load-blocks': {
      method: 'GET',
      regex: /\/wp\/v2\/blocks\?(.*)/g,
      process(matches) {
        return new Promise((resolve, reject) => {
          $.ajax({
            method: 'GET',
            url: Drupal.url(`editor/reusable-blocks`),
            data: parseQueryStrings(matches[1]),
          })
            .done(resolve)
            .fail(error => {
              errorHandler(error, reject);
            });
        });
      },
    },
    'block-options': {
      method: 'OPTIONS',
      regex: /\/wp\/v2\/blocks/g,
      process() {
        return new Promise(resolve => {
          resolve({
            headers: {
              get: value => {
                if (value === 'allow') {
                  return ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
                }
              },
            },
          });
        });
      },
    },

    'search-content': {
      method: 'GET',
      regex: /\/wp\/v2\/search\?(.*)/g,
      process(matches) {
        return new Promise((resolve, reject) => {
          $.ajax({
            method: 'GET',
            url: Drupal.url('editor/search'),
            data: parseQueryStrings(matches[1]),
          })
            .done(result => {
              resolve(result);
            })
            .fail(err => {
              reject(err);
            });
        });
      },
    },

    'load-autosaves': {
      method: 'GET',
      regex: /\/wp\/v2\/(.*)\/autosaves\?(.*)/g,
      process() {
        return new Promise(resolve => {
          resolve([]);
        });
      },
    },
    'save-autosaves': {
      method: 'POST',
      regex: /\/wp\/v2\/(.*)\/autosaves\?(.*)/g,
      process() {
        return new Promise(resolve => {
          resolve([]);
        });
      },
    },
    'load-me': {
      method: 'GET',
      regex: /\/wp\/v2\/users\/me/g,
      process() {
        return new Promise(resolve => {
          resolve(user);
        });
      },
    },
  };

  function processPath(options) {
    if (!options.path) {
      return new Promise(resolve => resolve('No action required.'));
    }

    // for-in is used to be able to do a simple short-circuit
    // whwn a match is found.
    /* eslint no-restricted-syntax: ["error", "never"] */
    for (const key in requestPaths) {
      if (requestPaths.hasOwnProperty(key)) {
        const requestPath = requestPaths[key];
        requestPath.regex.lastIndex = 0;
        const matches = requestPath.regex.exec(`${options.path}`);

        if (
          matches &&
          matches.length > 0 &&
          ((options.method && options.method === requestPath.method) ||
            requestPath.method === 'GET')
        ) {
          return requestPath.process(matches, options.data, options.body);
        }
      }
    }

    // None found, return type settings.
    return new Promise((resolve, reject) =>
      reject(new Error(`API handler not found - ${JSON.stringify(options)}`)),
    );
  }

  wp.apiFetch = processPath;
})(wp, Drupal, drupalSettings, jQuery);
