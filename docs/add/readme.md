# Adding Files

## add()

You can add a single file to the files list with the add() method.

```php
$assets->add('path/to/file.js');
```

Note: You can add different file types like .js, .css, .scss and .less, but the first file added will set the allowed file type for all files added after it.

If the first file added is a `.js` file, then only `.js` files can be added further.

If you add the same file multiple times, it will only be added once. For the sort order the first call will count:

```php
$assets->add('path/to/foo.js');
$assets->add('path/to/bar.js');
$assets->add('path/to/foo.js');

// result:
// path/to/foo.js
// path/to/bar.js

// NOT:
// path/to/bar.js
// path/to/foo.js
```

## addAll()

With the addAll() method you can add all files in a directory. You can even combine it with the add() method to add a specific file first:

```php
// define the source directory
$src = __DIR__ . '/alpine';
$assets
  // make sure RockCommerce is loaded first
  ->add($src . '/RockCommerce.js')
  // then load all other components from that folder
  ->addAll($src)
  ->saveTo(__DIR__ . '/dst/RockCommerce.min.js');
```

Note: This method will only add files with the same file extension as the first file added.
