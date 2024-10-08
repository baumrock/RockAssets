<?php

namespace ProcessWire;

$info = [
  'title' => 'RockAssets',
  'version' => json_decode(file_get_contents(__DIR__ . "/package.json"))->version,
  'summary' => 'Merge and minify LESS/SCSS/CSS/JS files via PHP',
  // we need autoload to flush the cash on modules::refresh
  'autoload' => true,
  'singular' => false,
  'icon' => 'code',
  'requires' => [
    'PHP>=8.1',
  ],
];
