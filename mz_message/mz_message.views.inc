<?php

/**
 * @file
 * Provide views data for message.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function mz_message_views_data_alter(&$data) {
  $data['message']['message_text'] = [
    'title' => t('Message text ccms'),
    'help' => t('Get the message text'),
    'field' => [
      // ID of the area handler plugin to use.
      'id' => 'mz_message_text',
    ],
  ];
}
