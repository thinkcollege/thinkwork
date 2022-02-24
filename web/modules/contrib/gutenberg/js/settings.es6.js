((Drupal) => {

  const settings = {
    alignWide: true,
    supportsLayout: false,
    allowedBlockTypes: true,
    allowedMimeTypes: {
      "jpg|jpeg|jpe": "image/jpeg",
      png: "image/png",
      gif: "image/gif",
      "mp3|m4a|m4b": "audio/mpeg",
      "mov|qt": "video/quicktime",
      avi: "video/avi",
      wmv: "video/x-ms-wmv",
      "mid|midi": "audio/midi",
      pdf: "application/pdf",
      "mp4|m4v": "video/mp4",
      webm: "video/webm",
      ogv: "video/ogg",
      "txt|asc|c|cc|h|srt": "text/plain",
      webp: "image/webp",
    },
    defaultEditorStyles: [
      {
        css: '/**\n * Colors\n */\n/**\n * Breakpoints & Media Queries\n */\n/**\n * SCSS Variables.\n *\n * Please use variables from this sheet to ensure consistency across the UI.\n * Don\'t add to this sheet unless you\'re pretty sure the value will be reused in many places.\n * For example, don\'t add rules to this sheet that affect block visuals. It\'s purely for UI.\n */\n/**\n * Colors\n */\n/**\n * Fonts & basic variables.\n */\n/**\n * Grid System.\n * https://make.wordpress.org/design/2019/10/31/proposal-a-consistent-spacing-system-for-wordpress/\n */\n/**\n * Dimensions.\n */\n/**\n * Shadows.\n */\n/**\n * Editor widths.\n */\n/**\n * Block & Editor UI.\n */\n/**\n * Block paddings.\n */\n/**\n * React Native specific.\n * These variables do not appear to be used anywhere else.\n */\n/**\n*  Converts a hex value into the rgb equivalent.\n*\n* @param {string} hex - the hexadecimal value to convert\n* @return {string} comma separated rgb values\n*/\n/**\n * Breakpoint mixins\n */\n/**\n * Long content fade mixin\n *\n * Creates a fading overlay to signify that the content is longer\n * than the space allows.\n */\n/**\n * Focus styles.\n */\n/**\n * Applies editor left position to the selector passed as argument\n */\n/**\n * Styles that are reused verbatim in a few places\n */\n/**\n * Allows users to opt-out of animations via OS-level preferences.\n */\n/**\n * Reset default styles for JavaScript UI based pages.\n * This is a WP-admin agnostic reset\n */\n/**\n * Reset the WP Admin page styles for Gutenberg-like pages.\n */\n:root {\n  --wp-admin-theme-color: #007cba;\n  --wp-admin-theme-color--rgb: 0, 124, 186;\n  --wp-admin-theme-color-darker-10: #006ba1;\n  --wp-admin-theme-color-darker-10--rgb: 0, 107, 161;\n  --wp-admin-theme-color-darker-20: #005a87;\n  --wp-admin-theme-color-darker-20--rgb: 0, 90, 135;\n  --wp-admin-border-width-focus: 2px;\n}\n@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {\n  :root {\n    --wp-admin-border-width-focus: 1.5px;\n  }\n}\n\n/**\n* Default editor styles.\n*\n* These styles are shown if a theme does not register its own editor style,\n* a theme.json file, or has toggled off "Use theme styles" in preferences.\n*/\nbody {\n  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;\n  font-size: 18px;\n  line-height: 1.5;\n  --wp--style--block-gap: 2em;\n}\n\np {\n  line-height: 1.8;\n}\n\n.editor-post-title__block {\n  margin-top: 2em;\n  margin-bottom: 1em;\n  font-size: 2.5em;\n  font-weight: 800;\n}',
      },
    ],
    blockCategories: [
      {
        slug: "text",
        title: "Text",
        icon: null,
      },
      {
        slug: "media",
        title: "Media",
        icon: null,
      },
      {
        slug: "design",
        title: "Design",
        icon: null,
      },
      {
        slug: "widgets",
        title: "Widgets",
        icon: null,
      },
      {
        slug: "theme",
        title: "Theme",
        icon: null,
      },
      {
        slug: "embed",
        title: "Embeds",
        icon: null,
      },
      {
        slug: "reusable",
        title: "Reusable Blocks",
        icon: null,
      },
    ],
    disableCustomColors: false,
    disableCustomFontSizes: false,
    disableCustomGradients: false,
    enableCustomLineHeight: false,
    enableCustomSpacing: false,
    enableCustomUnits: false,
    isRTL: false,
    imageDefaultSize: "large",
    imageDimensions: {
      thumbnail: {
        width: 150,
        height: 150,
        crop: true,
      },
      medium: {
        width: 300,
        height: 300,
        crop: false,
      },
      large: {
        width: 1024,
        height: 1024,
        crop: false,
      },
    },
    imageEditing: true,
    imageSizes: [
      {
        slug: "thumbnail",
        name: "Thumbnail",
      },
      {
        slug: "medium",
        name: "Medium",
      },
      {
        slug: "large",
        name: "Large",
      },
      {
        slug: "full",
        name: "Full Size",
      },
    ],
    maxUploadFileSize: 10485760,
    styles: [],
    colors: [
      {
        name: "Dark Blue",
        slug: "dark-blue",
        color: "#0073aa",
      },
      { name: "Light Blue", slug: "light-blue", color: "#229fd8" },
      { name: "Dark Gray", slug: "dark-gray", color: "#444" },
      { name: "Light Gray", slug: "light-gray", color: "#eee" },
    ],
    availableTemplates: [],
    disablePostFormats: true,
    titlePlaceholder: "Add title",
    bodyPlaceholder: "Start writing or type / to choose a block",
    autosaveInterval: 10,
    richEditingEnabled: true,
    postLock: { isLocked: false, user: null },
    postLockUtils: {
      nonce: "c802b6bb70",
      unlockNonce: "1875d756af",
      ajaxUrl: "https://wordpress.org/gutenberg/wp-admin/admin-ajax.php",
    },
    __experimentalFeatures: {
      border: { color: false, radius: false, style: false, width: false },
      color: {
        customDuotone: true,
        link: false,
        background: true,
        text: true,
        duotone: [
          {
            name: "Dark grayscale",
            colors: ["#000000", "#7f7f7f"],
            slug: "dark-grayscale",
          },
          {
            name: "Grayscale",
            colors: ["#000000", "#ffffff"],
            slug: "grayscale",
          },
          {
            name: "Purple and yellow",
            colors: ["#8c00b7", "#fcff41"],
            slug: "purple-yellow",
          },
          {
            name: "Blue and red",
            colors: ["#000097", "#ff4747"],
            slug: "blue-red",
          },
          {
            name: "Midnight",
            colors: ["#000000", "#00a5ff"],
            slug: "midnight",
          },
          {
            name: "Magenta and yellow",
            colors: ["#c7005a", "#fff278"],
            slug: "magenta-yellow",
          },
          {
            name: "Purple and green",
            colors: ["#a60072", "#67ff66"],
            slug: "purple-green",
          },
          {
            name: "Blue and orange",
            colors: ["#1900d8", "#ffa96b"],
            slug: "blue-orange",
          },
        ],
      },
      spacing: { blockGap: null, margin: false },
      typography: {
        dropCap: true,
        fontStyle: true,
        fontWeight: true,
        letterSpacing: true,
        textDecoration: true,
        textTransform: true,
        fontSizes: {
          core: [
            { name: "Small", slug: "small", size: "13px" },
            { name: "Normal", slug: "normal", size: "16px" },
            { name: "Medium", slug: "medium", size: "20px" },
            { name: "Large", slug: "large", size: "36px" },
            { name: "Huge", slug: "huge", size: "42px" },
          ],
        },
      },
      blocks: {
        // "core/button": { border: { radius: true } },
        "core/pullquote": {
          border: { color: true, radius: true, style: true, width: true },
        },
      },
    },
    gradients: [
      {
        name: "Vivid cyan blue to vivid purple",
        gradient:
          "linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%)",
        slug: "vivid-cyan-blue-to-vivid-purple",
      },
      {
        name: "Light green cyan to vivid green cyan",
        gradient:
          "linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%)",
        slug: "light-green-cyan-to-vivid-green-cyan",
      },
      {
        name: "Luminous vivid amber to luminous vivid orange",
        gradient:
          "linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%)",
        slug: "luminous-vivid-amber-to-luminous-vivid-orange",
      },
      {
        name: "Luminous vivid orange to vivid red",
        gradient:
          "linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%)",
        slug: "luminous-vivid-orange-to-vivid-red",
      },
      {
        name: "Very light gray to cyan bluish gray",
        gradient:
          "linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%)",
        slug: "very-light-gray-to-cyan-bluish-gray",
      },
      {
        name: "Cool to warm spectrum",
        gradient:
          "linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%)",
        slug: "cool-to-warm-spectrum",
      },
      {
        name: "Blush light purple",
        gradient:
          "linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%)",
        slug: "blush-light-purple",
      },
      {
        name: "Blush bordeaux",
        gradient:
          "linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%)",
        slug: "blush-bordeaux",
      },
      {
        name: "Luminous dusk",
        gradient:
          "linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%)",
        slug: "luminous-dusk",
      },
      {
        name: "Pale ocean",
        gradient:
          "linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%)",
        slug: "pale-ocean",
      },
      {
        name: "Electric grass",
        gradient:
          "linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%)",
        slug: "electric-grass",
      },
      {
        name: "Midnight",
        gradient: "linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%)",
        slug: "midnight",
      },
    ],
    fontSizes: [
      { name: "Small", slug: "small", size: "13px" },
      { name: "Normal", slug: "normal", size: "16px" },
      { name: "Medium", slug: "medium", size: "20px" },
      { name: "Large", slug: "large", size: "36px" },
      { name: "Huge", slug: "huge", size: "42px" },
    ],
  };

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.defaultSettings = settings;

})(Drupal);