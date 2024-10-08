# RockAssets

ProcessWire module to merge and minify LESS/SCSS/CSS/JS files via PHP

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
