<?php

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate_plus\data_parser;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test of the data_parser SimpleXml migrate_plus plugin.
 *
 * @group migrate_plus
 */
class SimpleXmlTest extends KernelTestBase {

  public static $modules = ['migrate', 'migrate_plus'];

  /**
   * Tests reducing single values.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   */
  public function testReduceSingleValue() {
    $path = $this->container
      ->get('module_handler')
      ->getModule('migrate_plus')
      ->getPath();
    $url = $path . '/tests/data/simple_xml_reduce_single_value.xml';

    /** @var \Drupal\migrate_plus\DataParserPluginManager $plugin_manager */
    $plugin_manager = $this->container
      ->get('plugin.manager.migrate_plus.data_parser');
    $conf = [
      'plugin' => 'url',
      'data_fetcher_plugin' => 'file',
      'data_parser_plugin' => 'simple_xml',
      'destination' => 'node',
      'urls' => [$url],
      'ids' => ['id' => ['type' => 'integer']],
      'fields' => [
        [
          'name' => 'id',
          'label' => 'Id',
          'selector' => '@id',
        ],
        [
          'name' => 'values',
          'label' => 'Values',
          'selector' => 'values',
        ],
      ],
      'item_selector' => '/items/item',
    ];
    $parser = $plugin_manager->createInstance('simple_xml', $conf);

    $data = [];
    foreach ($parser as $item) {
      $values = [];
      foreach ($item['values'] as $value) {
        $values[] = (string) $value;
      }
      $data[] = $values;
    }

    $expected = [
      [
        'Value 1',
        'Value 2',
      ],
      [
        'Value 1 (single)',
      ],
    ];

    $this->assertEquals($expected, $data);
  }

}
