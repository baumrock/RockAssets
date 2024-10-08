# RockAssets

ProcessWire module to merge and minify LESS/SCSS/CSS/JS files via PHP

## Quickstart

```php
$rockassets()
  ->addAll('/site/modules/RockCommerce/alpineNEW')
  ->add('/site/modules/RockCommerce/foo.js')
  ->add('/site/modules/RockCommerce/bar.js')
  ->saveIf(
    $config->debug,
    '/site/templates/debug/RockCommerce.js',
    minify: false,
  )
  ->save('/site/templates/bundle/RockCommerce.min.js')
  ->render(
    // if not provided will render the last saved file
    file: $config->debug
      ? '/site/templates/debug/RockCommerce.js'
      : '/site/templates/bundle/RockCommerce.min.js',
    defer: true,
  );
```

For more informations please read the docs at https://www.baumrock.com/en/modules/rockassets/docs/.
