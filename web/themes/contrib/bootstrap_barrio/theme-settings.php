<?php

/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings for Bootstrap Barrio-based themes.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function bootstrap_barrio_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  $form['#attached']['library'][] = 'bootstrap_barrio/color-picker';

  $color_config = [
    'colors' => [
      'bootstrap_barrio_base_primary_color' => 'Primary base color',
      'bootstrap_barrio_base_secondary_color' => 'Secondary base color',
    ],
    'schemes' => [
      'default' => [
        'label' => 'Blue & pink',
        'colors' => [
          'bootstrap_barrio_base_primary_color' => '#2F3C7E',
          'bootstrap_barrio_base_secondary_color' => '#FBEAEB',
        ],
      ],
      'blue_peach' => [
        'label' => 'Royal blue & peach',
        'colors' => [
          'bootstrap_barrio_base_primary_color' => '#00539C',
          'bootstrap_barrio_base_secondary_color' => '#EEA47F',
        ],
      ],
      'red_yellow' => [
        'label' => 'Red & yellow',
        'colors' => [
          'bootstrap_barrio_base_primary_color' => '#F96167',
          'bootstrap_barrio_base_secondary_color' => '#F9E795',
        ],
      ],
      'peach_orange' => [
        'label' => 'Peach & burnt orange',
        'colors' => [
          'bootstrap_barrio_base_primary_color' => '#FCEDDA',
          'bootstrap_barrio_base_secondary_color' => '#EE4E34',
        ],
      ],
      'red_pink' => [
        'label' => 'Cherry red & bubblegum pink',
        'colors' => [
          'bootstrap_barrio_base_primary_color' => '#CC313D',
          'bootstrap_barrio_base_secondary_color' => '#F7C5CC',
        ],
      ],
      'purple_mint' => [
        'label' => 'Light purple, mint',
        'colors' => [
          'bootstrap_barrio_base_primary_color' => '#AA96DA',
          'bootstrap_barrio_base_secondary_color' => '#FFFFD2',
        ],
      ],
      'blue_yellow' => [
        'label' => 'Royal blue & pale yellow',
        'colors' => [
          'bootstrap_barrio_base_primary_color' => '#234E70',
          'bootstrap_barrio_base_secondary_color' => '#FBF8BE',
        ],
      ],
      'scarlet_olive' => [
        'label' => 'Scarlet, light olive',
        'colors' => [
          'bootstrap_barrio_base_primary_color' => '#B85042',
          'bootstrap_barrio_base_secondary_color' => '#E7E8D1',
        ],
      ],
    ],
  ];

  $form['#attached']['drupalSettings']['barrio']['colorSchemes'] = $color_config['schemes'];

  // General "alters" use a form id. Settings should not be set here. The only
  // thing useful about this is if you need to alter the form for the running
  // theme and *not* the theme setting.
  // @see http://drupal.org/node/943212
  if (isset($form_id)) {
    return;
  }

  // Change collapsible fieldsets (now details) to default #open => FALSE.
  $form['theme_settings']['#open'] = FALSE;
  $form['logo']['#open'] = FALSE;
  $form['favicon']['#open'] = FALSE;

  // Library settings.
  if (\Drupal::moduleHandler()->moduleExists('bootstrap_library')) {
    $form['bootstrap_barrio_library'] = [
      '#type' => 'select',
      '#title' => t('Load library'),
      '#default_value' => theme_get_setting('bootstrap_barrio_library'),
      '#options' => [
        'cdn' => t('CDN'),
        'development' => t('Local non-minimized (development)'),
        'production' => t('Local minimized (production)'),
      ],
      '#empty_option' => t('None'),
      '#description' => t('If none is selected, you should load the library via Bootstrap Library or manually. If CDN is selected, the library version must be configured in the @bootstrap_library_link.',
      ['@bootstrap_library_link' => Link::fromTextAndUrl('Bootstrap Library Settings', Url::fromRoute('bootstrap_library.admin'))->toString()]),
    ];
  }
  else {
    $form['bootstrap_barrio_source'] = [
      '#type' => 'select',
      '#title' => t('Load library'),
      '#default_value' => theme_get_setting('bootstrap_barrio_source'),
      '#options' => [
        'bootstrap_barrio/bootstrap' => t('Local'),
        'bootstrap_barrio/bootstrap_cdn' => t('CDN'),
        'bootstrap_barrio/bootswatch_cerulean' => t('Bootswatch Cerulean'),
        'bootstrap_barrio/bootswatch_cosmo' => t('Bootswatch Cosmo'),
        'bootstrap_barrio/bootswatch_cyborg' => t('Bootswatch Cyborg'),
        'bootstrap_barrio/bootswatch_darkly' => t('Bootswatch Darkly'),
        'bootstrap_barrio/bootswatch_flatly' => t('Bootswatch Flatly'),
        'bootstrap_barrio/bootswatch_journal' => t('Bootswatch Journal'),
        'bootstrap_barrio/bootswatch_litera' => t('Bootswatch Litera'),
        'bootstrap_barrio/bootswatch_lumen' => t('Bootswatch Lumen'),
        'bootstrap_barrio/bootswatch_lux' => t('Bootswatch Lux'),
        'bootstrap_barrio/bootswatch_materia' => t('Bootswatch Materia'),
        'bootstrap_barrio/bootswatch_minty' => t('Bootswatch Minty'),
        'bootstrap_barrio/bootswatch_pulse' => t('Bootswatch Pulse'),
        'bootstrap_barrio/bootswatch_sandstone' => t('Bootswatch Sandstone'),
        'bootstrap_barrio/bootswatch_simplex' => t('Bootswatch Simplex'),
        'bootstrap_barrio/bootswatch_sketchy' => t('Bootswatch Sketchy'),
        'bootstrap_barrio/bootswatch_slate' => t('Bootswatch Slate'),
        'bootstrap_barrio/bootswatch_solar' => t('Bootswatch Solar'),
        'bootstrap_barrio/bootswatch_spacelab' => t('Bootswatch Spacelab'),
        'bootstrap_barrio/bootswatch_superhero' => t('Bootswatch Superhero'),
        'bootstrap_barrio/bootswatch_united' => t('Bootswatch United'),
        'bootstrap_barrio/bootswatch_yeti' => t('Bootswatch Yeti'),
      ],
      '#empty_option' => t('None'),
      '#empty_value' => false,
    ];
  }

  // Vertical tabs.
  $form['bootstrap'] = [
    '#type' => 'vertical_tabs',
    '#prefix' => '<h2><small>' . t('Bootstrap settings') . '</small></h2>',
    '#weight' => -10,
  ];

  // Colors.
  $form['colors'] = [
    '#type' => 'details',
    '#title' => t('Colors'),
    '#group' => 'bootstrap',
  ];

  $form['colors']['scheme'] = [
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#title' => t('Barrio Color Scheme Settings'),
  ];
  $form['colors']['scheme']['bootstrap_barrio_enable_color'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable color Scheme'),
    '#default_value' => theme_get_setting('bootstrap_barrio_enable_color'),
    '#ajax' => [
      'callback' => 'colorCallback',
      'wrapper' => 'color_container',
    ],
  ];
  $form['colors']['scheme']['bootstrap_barrio_scheme_description'] = [
    '#type' => 'html_tag',
    '#tag' => 'p',
    '#value' => t('These settings adjust the look and feel of the barrio based themes. Changing the colors below will change the basic color values the barrio based theme uses.'),
  ];
  $form['colors']['scheme']['color_container'] = [
    '#type' => 'container',
    '#attributes' => [
      'id' => 'color_container'
    ],
  ];

  if ($form_state->getValue('bootstrap_barrio_enable_color', theme_get_setting('bootstrap_barrio_enable_color'))) {
    $form['colors']['scheme']['color_container']['bootstrap_barrio_color_scheme'] = [
      '#type' => 'select',
      '#title' => t('Barrio Color Scheme'),
      '#empty_option' => t('Custom'),
      '#empty_value' => '',
      '#options' => [
        'default' => t('Blue & pink (Default)'),
        'blue_peach' => t('Royal blue & peach'),
        'red_yellow' => t('Red & yellow'),
        'peach_orange' => t('Peach & burnt orange'),
        'red_pink' => t('Cherry red & bubblegum pink'),
        'purple_mint' => t('Light purple, mint'),
        'blue_yellow' => t('Royal blue & pale yellow'),
        'scarlet_olive' => t('Scarlet, light olive'),
      ],
      '#input' => FALSE,
      '#wrapper_attributes' => [
        'style' => 'display:none;',
      ],
    ];
    foreach ($color_config['colors'] as $key => $title) {
      $form['colors']['scheme']['color_container'][$key] = [
        '#type' => 'textfield',
        '#maxlength' => 7,
        '#size' => 10,
        '#title' => t($title),
        '#description' => t('Enter color in full hexadecimal format (#abc123).') . '<br/>' . t('Derivatives will be formed from this color.'),
        '#default_value' => theme_get_setting($key),
        '#attributes' => [
          'pattern' => '^#[a-fA-F0-9]{6}',
        ],
        '#wrapper_attributes' => [
          'data-drupal-selector' => 'barrio-color-picker',
        ],
      ];
    }
    $form['colors']['scheme']['color_container']['bootstrap_barrio_body_color'] = [
      '#type' => 'select',
      '#title' => t('Body color'),
      '#default_value' => theme_get_setting('bootstrap_barrio_body_color') ?? 'gray-800',
      '#options' => [
        'gray-800' => t('Dark gray'),
        'black' => t('Black'),
      ],
    ];
    $form['colors']['scheme']['color_container']['bootstrap_barrio_body_bg_color'] = [
      '#type' => 'select',
      '#title' => t('Body Background Color'),
      '#default_value' => theme_get_setting('bootstrap_barrio_body_bg_color') ?? 'white',
      '#options' => [
        'white' => t('White'),
        'gray-200' => t('Light gray'),
      ],
    ];
    $form['colors']['scheme']['color_container']['bootstrap_barrio_h1_color'] = [
      '#type' => 'select',
      '#title' => t('H1 color'),
      '#default_value' => theme_get_setting('bootstrap_barrio_h1_color') ?? 'base',
      '#options' => [
        'base' => t('Base color'),
        'primary' => t('Primary color'),
        'secondary' => t('Secondary color'),
      ],
    ];
    $form['colors']['scheme']['color_container']['bootstrap_barrio_h2_color'] = [
      '#type' => 'select',
      '#title' => t('H2 color'),
      '#default_value' => theme_get_setting('bootstrap_barrio_h2_color') ?? 'base',
      '#options' => [
        'base' => t('Base color'),
        'primary' => t('Primary color'),
        'secondary' => t('Secondary color'),
      ],
    ];
    $form['colors']['scheme']['color_container']['bootstrap_barrio_h3_color'] = [
      '#type' => 'select',
      '#title' => t('H3 color'),
      '#default_value' => theme_get_setting('bootstrap_barrio_h3_color') ?? 'base',
      '#options' => [
        'base' => t('Base color'),
        'primary' => t('Primary color'),
        'secondary' => t('Secondary color'),
      ],
    ];
  }

  // System messages.
  $form['colors']['alerts'] = [
    '#type' => 'details',
    '#title' => t('System messages'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['colors']['alerts']['bootstrap_barrio_system_messages'] = [
    '#type' => 'select',
    '#title' => t('System messages color scheme'),
    '#default_value' => theme_get_setting('bootstrap_barrio_system_messages'),
    '#empty_option' => t('Default'),
    '#options' => [
      'messages_white' => t('White'),
      'messages_gray' => t('Gray'),
      'messages_light' => t('Light color'),
      'messages_dark' => t('Dark color'),
    ],
    '#description' => t('Replace the standard color scheme for system messages with a Google Material Design color scheme.'),
  ];

  // Tables.
  $form['colors']['tables'] = [
    '#type' => 'details',
    '#title' => t('Tables'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['colors']['tables']['bootstrap_barrio_table_style'] = [
    '#type' => 'select',
    '#title' => t('Table cell style'),
    '#default_value' => theme_get_setting('bootstrap_barrio_table_style'),
    '#empty_option' => t('Default'),
    '#options' => [
      'table-striped' => t('Striped'),
      'table-bordered' => t('Bordered'),
      'table-striped-columns' => t('Striped Columns'),
    ],
  ];
  $form['colors']['tables']['bootstrap_barrio_table_hover'] = [
    '#type' => 'checkbox',
    '#title' => t('Hover effect over table cells'),
    '#default_value' => theme_get_setting('bootstrap_barrio_table_hover'),
  ];
  $form['colors']['tables']['bootstrap_barrio_table_head'] = [
    '#type' => 'select',
    '#title' => t('Table header color scheme'),
    '#default_value' => theme_get_setting('bootstrap_barrio_table_head'),
    '#empty_option' => t('Default'),
    '#options' => [
      'thead-light' => t('Light'),
      'thead-dark' => t('Dark'),
    ],
  ];

  // Layout.
  $form['layout'] = [
    '#type' => 'details',
    '#title' => t('Layout'),
    '#group' => 'bootstrap',
  ];

  // Container.
  $form['layout']['container'] = [
    '#type' => 'details',
    '#title' => t('Container'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['layout']['container']['bootstrap_barrio_container'] = [
    '#type' => 'select',
    '#title' => t('Container'),
    '#default_value' => theme_get_setting('bootstrap_barrio_container') ??
      (theme_get_setting('bootstrap_barrio_fluid_container') ? 'container-fluid' : 'container'),
    '#options' => [
      'container' => t('Container'),
      'container-md' => t('Container Medium'),
      'container-lg' => t('Container Large'),
      'container-xl' => t('Container Extra Large'),
      'container-xxl' => t('Container Extra Extra Large'),
      'container-fluid' => t('Container Fluid'),
    ],
    '#description' => t('Use <code>.container-XX</code> class. See @bootstrap_fluid_containers_link.', [
      '@bootstrap_fluid_containers_link' => Link::fromTextAndUrl('Containers in the Bootstrap 5 documentation', Url::fromUri('https://getbootstrap.com/docs/5.2/layout/overview/', ['absolute' => TRUE, 'fragment' => 'containers']))->toString(),
    ]),
  ];
/*  $form['layout']['container']['bootstrap_barrio_fluid_container'] = [
    '#type' => 'checkbox',
    '#title' => t('Fluid container'),
    '#default_value' => theme_get_setting('bootstrap_barrio_fluid_container'),
    '#description' => t('Use <code>.container-fluid</code> class. See @bootstrap_fluid_containers_link.', [
      '@bootstrap_fluid_containers_link' => Link::fromTextAndUrl('Containers in the Bootstrap 5 documentation', Url::fromUri('https://getbootstrap.com/docs/5.2/layout/overview/', ['absolute' => TRUE, 'fragment' => 'containers']))->toString(),
    ]),
  ];
*/
  // List of regions.
  $theme = \Drupal::theme()->getActiveTheme()->getName();
  $region_list = system_region_list($theme);

  // Only for initial setup if not defined on install.
  $nowrap = [
    'breadcrumb',
    'highlighted',
    'content',
    'primary_menu',
    'header',
    'sidebar_first',
    'sidebar_second',
  ];

  // Region.
  $form['layout']['region'] = [
    '#type' => 'details',
    '#title' => t('Region'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  foreach ($region_list as $name => $description) {
    if (theme_get_setting('bootstrap_barrio_region_clean_' . $name) !== NULL) {
      $region_clean = theme_get_setting('bootstrap_barrio_region_clean_' . $name);
    }
    else {
      $region_clean = in_array($name, $nowrap);
    }
    if (theme_get_setting('bootstrap_barrio_region_class_' . $name) !== NULL) {
      $region_class = theme_get_setting('bootstrap_barrio_region_class_' . $name);
    }
    else {
      $region_class = $region_clean ? NULL : 'row';
    }

    $form['layout']['region'][$name] = [
      '#type' => 'details',
      '#title' => $description,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['layout']['region'][$name]['bootstrap_barrio_region_clean_' . $name] = [
      '#type' => 'checkbox',
      '#title' => t('Clean wrapper for @description region', ['@description' => $description]),
      '#default_value' => $region_clean,
    ];
    $form['layout']['region'][$name]['bootstrap_barrio_region_width_' . $name] = [
      '#type' => 'checkbox',
      '#title' => t('Full width wrapper for @description region', ['@description' => $description]),
      '#default_value' => theme_get_setting('bootstrap_barrio_region_width_' . $name),
    ];
    $form['layout']['region'][$name]['bootstrap_barrio_region_class_' . $name] = [
      '#type' => 'textfield',
      '#title' => t('Classes for @description region', ['@description' => $description]),
      '#default_value' => $region_class,
      '#size' => 40,
      '#maxlength' => 40,
    ];
  }

  // Sidebar Position.
  $form['layout']['sidebar_position'] = [
    '#type' => 'details',
    '#title' => t('Sidebar position'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['layout']['sidebar_position']['bootstrap_barrio_sidebar_position'] = [
    '#type' => 'select',
    '#title' => t('Sidebars position'),
    '#default_value' => theme_get_setting('bootstrap_barrio_sidebar_position'),
    '#options' => [
      'left' => t('Left'),
      'both' => t('Both sides'),
      'right' => t('Right'),
    ],
  ];
  $form['layout']['sidebar_position']['bootstrap_barrio_content_offset'] = [
    '#type' => 'select',
    '#title' => t('Content offset'),
    '#default_value' => theme_get_setting('bootstrap_barrio_content_offset'),
    '#options' => [
      0 => t('None'),
      1 => t('1 cols'),
      2 => t('2 cols'),
    ],
  ];

  // Sidebar first layout.
  $form['layout']['sidebar_first'] = [
    '#type' => 'details',
    '#title' => t('Sidebar First Layout'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['layout']['sidebar_first']['bootstrap_barrio_sidebar_collapse'] = [
    '#type' => 'checkbox',
    '#title' => t('Sidebar collapse'),
    '#default_value' => theme_get_setting('bootstrap_barrio_sidebar_collapse'),
  ];
  $form['layout']['sidebar_first']['bootstrap_barrio_sidebar_first_width'] = [
    '#type' => 'select',
    '#title' => t('Sidebar first width'),
    '#default_value' => theme_get_setting('bootstrap_barrio_sidebar_first_width'),
    '#options' => [
      2 => t('2 cols'),
      3 => t('3 cols'),
      4 => t('4 cols'),
    ],
  ];
  $form['layout']['sidebar_first']['bootstrap_barrio_sidebar_first_offset'] = [
    '#type' => 'select',
    '#title' => t('Sidebar first offset'),
    '#default_value' => theme_get_setting('bootstrap_barrio_sidebar_first_offset'),
    '#options' => [
      0 => t('None'),
      1 => t('1 cols'),
      2 => t('2 cols'),
    ],
  ];

  // Sidebar second layout.
  $form['layout']['sidebar_second'] = [
    '#type' => 'details',
    '#title' => t('Sidebar second layout'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['layout']['sidebar_second']['bootstrap_barrio_sidebar_second_width'] = [
    '#type' => 'select',
    '#title' => t('Sidebar second width'),
    '#default_value' => theme_get_setting('bootstrap_barrio_sidebar_second_width'),
    '#options' => [
      2 => t('2 cols'),
      3 => t('3 cols'),
      4 => t('4 cols'),
    ],
  ];
  $form['layout']['sidebar_second']['bootstrap_barrio_sidebar_second_offset'] = [
    '#type' => 'select',
    '#title' => t('Sidebar second offset'),
    '#default_value' => theme_get_setting('bootstrap_barrio_sidebar_second_offset'),
    '#options' => [
      0 => t('None'),
      1 => t('1 cols'),
      2 => t('2 cols'),
    ],
  ];

  // Components.
  $form['components'] = [
    '#type' => 'details',
    '#title' => t('Components'),
    '#group' => 'bootstrap',
  ];

  // Node.
  $form['components']['node'] = [
    '#type' => 'details',
    '#title' => t('Node'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['node']['bootstrap_barrio_hide_node_label'] = [
    '#type' => 'checkbox',
    '#title' => t('Hide node label'),
    '#default_value' => theme_get_setting('bootstrap_barrio_hide_node_label'),
    '#description' => t('Hide node label for all display. Usefull when using f.e. Layout Builder and you want full control of your output'),
  ];

  // Breadcrumbs.
  $form['components']['breadcrumb'] = [
    '#type' => 'details',
    '#title' => t('Breadcrumb'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['breadcrumb']['bootstrap_barrio_breadcrumb_divider'] = [
    '#type' => 'textfield',
    '#title' => t('Breadcrumb Divider'),
    '#size' => 60,
    '#maxlength' => 256,
    '#default_value' => theme_get_setting('bootstrap_barrio_breadcrumb_divider'),
    '#description' => t('Change the default breadcrumb divider. See @bootstrap_breadcrumb_link.', [
      '@bootstrap_breadcrumb_link' => Link::fromTextAndUrl('breadcrumb in the Bootstrap 5.x documentation', Url::fromUri('https://getbootstrap.com/docs/5.2/components/breadcrumb/', ['absolute' => TRUE, 'fragment' => 'outline-buttons']))->toString(),
    ]),
  ];

  // Buttons.
  $form['components']['buttons'] = [
    '#type' => 'details',
    '#title' => t('Buttons'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['buttons']['bootstrap_barrio_button'] = [
    '#type' => 'checkbox',
    '#title' => t('Convert input submit to button element'),
    '#default_value' => theme_get_setting('bootstrap_barrio_button'),
    '#description' => t('There is a known issue where Ajax exposed filters do not if this setting is enabled.'),
  ];
  $form['components']['buttons']['bootstrap_barrio_button_type'] = [
    '#type' => 'select',
    '#title' => t('Default button type'),
    '#default_value' => theme_get_setting('bootstrap_barrio_button_type'),
    '#options' => [
      'primary' => t('Primary'),
      'secondary' => t('Secondary'),
    ],
  ];
  $form['components']['buttons']['bootstrap_barrio_button_size'] = [
    '#type' => 'select',
    '#title' => t('Default button size'),
    '#default_value' => theme_get_setting('bootstrap_barrio_button_size'),
    '#empty_option' => t('Normal'),
    '#options' => [
      'btn-sm' => t('Small'),
      'btn-lg' => t('Large'),
    ],
  ];
  $form['components']['buttons']['bootstrap_barrio_button_outline'] = [
    '#type' => 'checkbox',
    '#title' => t('Button with outline format'),
    '#default_value' => theme_get_setting('bootstrap_barrio_button_outline'),
    '#description' => t('Use <code>.btn-default-outline</code> class. See @bootstrap_outline_buttons_link.', [
      '@bootstrap_outline_buttons_link' => Link::fromTextAndUrl('Outline buttons in the Bootstrap 4 documentation', Url::fromUri('https://getbootstrap.com/docs/5.2/components/buttons/', ['absolute' => TRUE, 'fragment' => 'outline-buttons']))->toString(),
    ]),
  ];

  // Images.
  $form['components']['images'] = [
    '#type' => 'details',
    '#title' => t('Images'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['images']['bootstrap_barrio_image_fluid'] = [
    '#type' => 'checkbox',
    '#title' => t('Apply img-fluid style to all content images'),
    '#default_value' => theme_get_setting('bootstrap_barrio_image_fluid'),
    '#description' => t('Adds a img-fluid style to all ".content img" elements'),
  ];

  // Navbar.
  $form['components']['navbar'] = [
    '#type' => 'details',
    '#title' => t('Navbar structure'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_container'] = [
    '#type' => 'checkbox',
    '#title' => t('Navbar width container'),
    '#description' => t('Check if navbar width will be inside container or fluid width.'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_container'),
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_toggle'] = [
    '#type' => 'select',
    '#title' => t('Navbar toggle size'),
    '#description' => t('Select size for navbar to collapse.'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_toggle'),
    '#options' => [
      'navbar-toggleable-xl' => t('Extra Large'),
      'navbar-toggleable-lg' => t('Large'),
      'navbar-toggleable-md' => t('Medium'),
      'navbar-toggleable-sm' => t('Small'),
      'navbar-toggleable-xs' => t('Extra small'),
      'navbar-toggleable-all' => t('All screens'),
    ],
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_top_navbar'] = [
    '#type' => 'checkbox',
    '#title' => t('Navbar top is navbar'),
    '#description' => t('Check if navbar top .navbar class should be added.'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_top_navbar'),
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_top_position'] = [
    '#type' => 'select',
    '#title' => t('Navbar top position'),
    '#description' => t('Select your navbar top position.'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_top_position'),
    '#options' => [
      'fixed-top' => t('Fixed top'),
      'fixed-bottom' => t('Fixed bottom'),
      'sticky-top' => t('Sticky top'),
    ],
    '#empty_option' => t('Normal'),
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_top_color'] = [
    '#type' => 'select',
    '#title' => t('Navbar top link color'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_top_color'),
    '#options' => [
      'navbar-light' => t('Light'),
      'navbar-dark' => t('Dark'),
    ],
    '#empty_option' => t('Default'),
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_top_background'] = [
    '#type' => 'select',
    '#title' => t('Navbar top background color'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_top_background'),
    '#options' => [
      'bg-primary' => t('Primary'),
      'bg-light' => t('Light'),
      'bg-dark' => t('Dark'),
    ],
    '#empty_option' => t('Default'),
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_position'] = [
    '#type' => 'select',
    '#title' => t('Navbar position'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_position'),
    '#options' => [
      'fixed-top' => t('Fixed top'),
      'fixed-bottom' => t('Fixed bottom'),
      'sticky-top' => t('Sticky top'),
    ],
    '#empty_option' => t('Normal'),
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_color'] = [
    '#type' => 'select',
    '#title' => t('Navbar link color'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_color'),
    '#options' => [
      'navbar-light' => t('Light'),
      'navbar-dark' => t('Dark'),
    ],
    '#empty_option' => t('Default'),
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_background'] = [
    '#type' => 'select',
    '#title' => t('Navbar background color'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_background'),
    '#options' => [
      'bg-primary' => t('Primary'),
      'bg-light' => t('Light'),
      'bg-dark' => t('Dark'),
    ],
    '#empty_option' => t('Default'),
  ];
  // Allow custom classes on Navbars.
  $form['components']['navbar']['bootstrap_barrio_navbar_top_class'] = [
    '#type' => 'textfield',
    '#title' => t('Custom classes for Navbar Top'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_top_class'),
    '#size' => 40,
    '#maxlength' => 40,
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_class'] = [
    '#type' => 'textfield',
    '#title' => t('Custom classes for Navbar'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_class'),
    '#size' => 40,
    '#maxlength' => 40,
  ];

  // Navbar behaviour.
  $form['components']['navbar_behaviour'] = [
    '#type' => 'details',
    '#title' => t('Navbar behaviour'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $form['components']['navbar_behaviour']['bootstrap_barrio_navbar_offcanvas'] = [
    '#type' => 'select',
    '#title' => t('Default/Bootstrap Offcanvas Collapse'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_offcanvas'),
    '#options' => [
      'offcanvas-collapse' => t('Offcanvas'),
    ],
    '#empty_option' => t('Default'),
  ];

  $form['components']['navbar_behaviour']['bootstrap_barrio_navbar_flyout'] = [
    '#type' => 'checkbox',
    '#title' => t('Flyout style main menu'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_flyout'),
  ];

  $form['components']['navbar_behaviour']['bootstrap_barrio_navbar_slide'] = [
    '#type' => 'checkbox',
    '#title' => t('Sliding navbar'),
    '#description' => t('Collapsed navbar will slide left to right'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_slide'),
    '#description' => t('DO NOT USE IN NEW SITES. Removed in favor of Bootstrap Offcanvas.'),
  ];

  // Tabs.
  $form['components']['tabs'] = [
    '#type' => 'details',
    '#title' => t('Tabs (local tasks)'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $form['components']['tabs']['bootstrap_barrio_tabs_style'] = [
    '#type' => 'select',
    '#title' => t('Tabs style'),
    '#default_value' => theme_get_setting('bootstrap_barrio_tabs_style'),
    '#options' => [
      'full' => t('Full width blocks'),
      'pills' => t('Pills'),
    ],
    '#empty_option' => t('Default'),
  ];

  // Messages.
  $form['components']['alerts'] = [
    '#type' => 'details',
    '#title' => t('Messages'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['alerts']['bootstrap_barrio_messages_widget'] = [
    '#type' => 'select',
    '#title' => t('Messages widget'),
    '#default_value' => theme_get_setting('bootstrap_barrio_messages_widget'),
    '#options' => [
      'default' => t('Alerts classic'),
      'alerts' => t('Alerts bottom'),
      'toasts' => t('Toasts'),
    ],
  ];
  $form['components']['alerts']['bootstrap_barrio_messages_widget_toast_delay'] = [
    '#type' => 'number',
    '#title' => t('Toast delay'),
    '#default_value' => theme_get_setting('bootstrap_barrio_messages_widget_toast_delay') ?? 10000,
    '#description' => t('How long to keep the toast open in milliseconds.'),
    '#states' => [
      'visible' => [
        ':input[name="bootstrap_barrio_messages_widget"]' => ['value' => 'toasts'],
      ],
    ],
  ];

  // Form.
  $form['components']['form'] = [
    '#type' => 'details',
    '#title' => t('Form Elements'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['form']['bootstrap_barrio_float_label'] = [
    '#type' => 'checkbox',
    '#title' => t('Float Labels'),
    '#default_value' => theme_get_setting('bootstrap_barrio_float_label'),
  ];
  $form['components']['form']['bootstrap_barrio_checkbox'] = [
    '#type' => 'select',
    '#title' => t('Checkbox & Radio Style'),
    '#default_value' => theme_get_setting('bootstrap_barrio_checkbox'),
    '#empty_option' => t('Default'),
    '#options' => [
      'switch' => t('Switch'),
      'button' => t('Button'),
    ],
  ];

  // Affix.
  $form['affix'] = [
    '#type' => 'details',
    '#title' => t('Affix'),
    '#group' => 'bootstrap',
  ];
  $form['affix']['navbar_top'] = [
    '#type' => 'details',
    '#title' => t('Affix navbar top'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['affix']['navbar_top']['bootstrap_barrio_navbar_top_affix'] = [
    '#type' => 'checkbox',
    '#title' => t('Affix navbar top'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_top_affix'),
  ];
  /*
  $form['affix']['navbar_top']['bootstrap_barrio_navbar_top_affix_top'] = [
  '#type' => 'textfield',
  '#title' => t('Affix top'),
  '#default_value' => theme_get_setting('bootstrap_barrio_navbar_top_affix_top'
  ),
  '#prefix' => '<div id="navbar-top-affix">',
  '#size' => 6,
  '#maxlength' => 3,
  '#states' => [
  'invisible' => [
  'input[name="bootstrap_barrio_navbar_top_affix"]' => ['checked' => FALSE],
  ],
  ],
  ];
  $form['affix']['navbar_top']['bootstrap_barrio_navbar_top_affix_bottom'] = [
  '#type' => 'textfield',
  '#title' => t('Affix bottom'),
  '#default_value' => theme_get_setting(
  'bootstrap_barrio_navbar_top_affix_bottom'),
  '#suffix' => '</div>',
  '#size' => 6,
  '#maxlength' => 3,
  '#states' => [
  'invisible' => [
  'input[name="bootstrap_barrio_navbar_top_affix"]' => ['checked' => FALSE],
  ],
  ],
  ];
   */
  $form['affix']['navbar'] = [
    '#type' => 'details',
    '#title' => t('Affix navbar'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['affix']['navbar']['bootstrap_barrio_navbar_affix'] = [
    '#type' => 'checkbox',
    '#title' => t('Affix navbar'),
    '#default_value' => theme_get_setting('bootstrap_barrio_navbar_affix'),
  ];
  /*
  $form['affix']['navbar']['bootstrap_barrio_navbar_affix_top'] = [
  '#type' => 'textfield',
  '#title' => t('Affix top'),
  '#default_value' => theme_get_setting('bootstrap_barrio_navbar_affix_top'),
  '#prefix' => '<div id="navbar-affix">',
  '#size' => 6,
  '#maxlength' => 3,
  '#states' => [
  'invisible' => [
  'input[name="bootstrap_barrio_navbar_affix"]' => ['checked' => FALSE],
  ],
  ],
  ];
  $form['affix']['navbar']['bootstrap_barrio_navbar_affix_bottom'] = [
  '#type' => 'textfield',
  '#title' => t('Affix bottom'),
  '#default_value' => theme_get_setting('bootstrap_barrio_navbar_affix_bottom'),
  '#suffix' => '</div>',
  '#size' => 6,
  '#maxlength' => 3,
  '#states' => [
  'invisible' => [
  'input[name="bootstrap_barrio_navbar_affix"]' => ['checked' => FALSE],
  ],
  ],
  ];
   */
  $form['affix']['sidebar_first'] = [
    '#type' => 'details',
    '#title' => t('Affix sidebar first'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['affix']['sidebar_first']['bootstrap_barrio_sidebar_first_affix'] = [
    '#type' => 'checkbox',
    '#title' => t('Affix sidebar first'),
    '#default_value' => theme_get_setting('bootstrap_barrio_sidebar_first_affix'),
  ];
  /*
  $form['affix']['sidebar_first'][
  'bootstrap_barrio_sidebar_first_affix_top'] = array(
  '#type' => 'textfield',
  '#title' => t('Affix top'),
  '#default_value' => theme_get_setting(
  'bootstrap_barrio_sidebar_first_affix_top'),
  '#prefix' => '<div id="sidebar-first-affix">',
  '#size' => 6,
  '#maxlength' => 3,
  '#states' => [
  'invisible' => [
  'input[name="bootstrap_barrio_sidebar_first_affix"]' => ['checked' => FALSE],
  ],
  ],
  );
  $form['affix']['sidebar_first'][
  'bootstrap_barrio_sidebar_first_affix_bottom'] = array(
  '#type' => 'textfield',
  '#title' => t('Affix bottom'),
  '#default_value' => theme_get_setting(
  'bootstrap_barrio_sidebar_first_affix_bottom'),
  '#suffix' => '</div>',
  '#size' => 6,
  '#maxlength' => 3,
  '#states' => [
  'invisible' => [
  'input[name="bootstrap_barrio_sidebar_first_affix"]' => ['checked' => FALSE],
  ],
  ],
  ); */
  $form['affix']['sidebar_second'] = [
    '#type' => 'details',
    '#title' => t('Affix sidebar second'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['affix']['sidebar_second']['bootstrap_barrio_sidebar_second_affix'] = [
    '#type' => 'checkbox',
    '#title' => t('Affix sidebar second'),
    '#default_value' => theme_get_setting('bootstrap_barrio_sidebar_second_affix'),
  ];
  /*
  $form['affix']['sidebar_second'][
  'bootstrap_barrio_sidebar_second_affix_top'] = [
  '#type' => 'textfield',
  '#title' => t('Affix top'),
  '#default_value' => theme_get_setting(
  'bootstrap_barrio_sidebar_second_affix_top'),
  '#prefix' => '<div id="sidebar-second-affix">',
  '#size' => 6,
  '#maxlength' => 3,
  '#states' => [
  'invisible' => [
  'input[name="bootstrap_barrio_sidebar_second_affix"]' => ['checked' => FALSE],
  ],
  ],
  ];
  $form['affix']['sidebar_second'][
  'bootstrap_barrio_sidebar_second_affix_bottom'] = [
  '#type' => 'textfield',
  '#title' => t('Affix bottom'),
  '#default_value' => theme_get_setting(
  'bootstrap_barrio_sidebar_second_affix_bottom'),
  '#suffix' => '</div>',
  '#size' => 6,
  '#maxlength' => 3,
  '#states' => [
  'invisible' => [
  'input[name="bootstrap_barrio_sidebar_second_affix"]' => ['checked' => FALSE],
  ],
  ],
  ];
   */
  // Scroll Spy.
  $form['scroll_spy'] = [
    '#type' => 'details',
    '#title' => t('Scroll Spy'),
    '#group' => 'bootstrap',
  ];
  $form['scroll_spy']['bootstrap_barrio_scroll_spy'] = [
    '#type' => 'textfield',
    '#title' => t('Scrollspy element ID'),
    '#description' => t('Specify a valid jQuery ID for the element containing a .nav that will behave with scrollspy.'),
    '#default_value' => theme_get_setting('bootstrap_barrio_scroll_spy'),
    '#size' => 40,
    '#maxlength' => 40,
  ];

  // Fonts.
  $form['fonts'] = [
    '#type' => 'details',
    '#title' => t('Fonts & icons'),
    '#group' => 'bootstrap',
  ];
  $form['fonts']['fonts'] = [
    '#type' => 'details',
    '#title' => t('Fonts'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];
  $form['fonts']['fonts']['bootstrap_barrio_google_fonts'] = [
    '#type' => 'select',
    '#title' => t('Google Fonts combination'),
    '#default_value' => theme_get_setting('bootstrap_barrio_google_fonts'),
    '#empty_option' => t('None'),
    '#options' => [
      'roboto' => t('Roboto Condensed, Roboto'),
      'monserrat_lato' => t('Monserrat, Lato'),
      'alegreya_roboto' => t('Alegreya, Roboto Condensed, Roboto'),
      'dancing_garamond' => t('Dancing Script, EB Garamond'),
      'amatic_josefin' => t('Amatic SC, Josefin Sans'),
      'oswald_droid' => t('Oswald, Droid Serif'),
      'playfair_alice' => t('Playfair Display, Alice'),
      'dosis_opensans' => t('Dosis, Open Sans'),
      'lato_hotel' => t('Lato, Grand Hotel'),
      'medula_abel' => t('Medula One, Abel'),
      'fjalla_cantarell' => t('Fjalla One, Cantarell'),
      'coustard_leckerli' => t('Coustard Ultra, Leckerli One'),
      'philosopher_muli' => t('Philosopher, Muli'),
      'vollkorn_exo' => t('Vollkorn, Exo'),
    ],
  ];
  $form['fonts']['bootstrap_icons'] = [
    '#type' => 'details',
    '#title' => t('Bootstrap icons'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];
  $form['fonts']['bootstrap_icons']['bootstrap_barrio_bootstrap_icons'] = [
    '#type' => 'checkbox',
    '#title' => t('Use Bootstrap icons'),
    '#default_value' => theme_get_setting('bootstrap_barrio_bootstrap_icons'),
  ];
  $form['fonts']['icons'] = [
    '#type' => 'details',
    '#title' => t('Icons'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];
  $form['fonts']['icons']['bootstrap_barrio_icons'] = [
    '#type' => 'select',
    '#title' => t('Icon set'),
    '#default_value' => theme_get_setting('bootstrap_barrio_icons'),
    '#empty_option' => t('None'),
    '#options' => [
      'material_design_icons' => t('Material Design Icons'),
      'fontawesome' => t('Font Awesome'),
    ],
  ];
}

/**
 * @param $form
 * @param FormStateInterface $form_state
 * @return mixed
 */
function colorCallback($form, FormStateInterface $form_state)
{
  return $form['colors']['scheme']['color_container'];
}
