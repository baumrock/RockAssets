<?php

namespace ProcessWire;

$info = [
  'title' => 'RockAssets',
  'version' => json_decode(file_get_contents(__DIR__ . "/package.json"))->version,
  'summary' => '',
  'autoload' => false,
  'singular' => false,
  'icon' => 'code',
  'requires' => [
    'PHP>=8.1',
  ],
];
