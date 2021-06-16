<?php
/**
 * Plugin Name: item_copy_generator
 * Plugin URI: https://github.com/drajathasan/slims_copy_item_generator
 * Description: Pembuat nomor salin atau copy pada nomor panggil di item bibliografi
 * Version: 1.0.2
 * Author: Drajat Hasan
 * Author URI: https://github.com/drajathasan/
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// registering menus
$plugin->registerMenu('bibliography', 'Item Copy Generator', __DIR__ . '/index.php');
