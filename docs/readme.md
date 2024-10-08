# RockAssets

ProcessWire module to merge and minify LESS/SCSS/CSS/JS files via PHP

It can

- merge and minify asset files for your frontend
- merge and minify asset files for your modules

## Paths

RockAssets allows you to provide paths to your assets in different ways.

- a full path like `/var/www/html/site/templates/scripts/main.js`
- a path relative to the root like `site/templates/scripts/main.js` starting with either one of: `/site`, `site`, `/wire`, `wire`

## Quickstart

```php
$assets = wire()->modules->get('RockAssets');
$assets
  ->addAll('/site/templates/scripts', '.js')
  ->add('/site/modules/MyModule/MyAsset1.js')
  ->add('/site/modules/MyModule/MyAsset2.js')
  ->saveIf(
    $config->debug,
    file: '/site/templates/bundle/main.js',
    minify: false,
  )
  ->saveTo('/site/templates/bundle/main.min.js')
  ->render(
    // if not provided will render the last saved file
    file: $config->debug
      ? '/site/templates/bundle/main.js'
      : '/site/templates/bundle/main.min.js',
    attrs: "defer",
  );
```
