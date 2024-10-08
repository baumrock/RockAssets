# RockAssets

ProcessWire module to merge and minify LESS/SCSS/CSS/JS files via PHP

It can

- merge and minify asset files for your frontend
- merge and minify asset files for your modules

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

## Paths

RockAssets allows you to provide paths to your assets in different ways.

- a full path like `/var/www/html/site/templates/scripts/main.js`
- a path relative to the root like `site/templates/scripts/main.js` starting with either one of: `/site`, `site`, `/wire`, `wire`

## Recompile

<div class="uk-alert uk-alert-warning">
  RockAssets will only ever create files if `$config->debug` is true!
</div>

This is both for security and performance reasons. On production systems RockAssets will neither create any files nor add any files to it's file collection. It will only output the final `< script >` or `< link >` tag when requested via `render()`.

This is because we assume that you use RockAssets during local development and use a proper deployment process to move your final assets to production (or copy them over manually).

*When does RockAssets recompile?*

- If you do a `Modules::refresh`
- If any of the added files changed
- If any of previously added files have been removed
- If `$config->rockassetsForceRecompile` is set to true
