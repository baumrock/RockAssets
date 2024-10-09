<?php

namespace ProcessWire;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;

/**
 * @author Bernhard Baumrock, 08.10.2024
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockAssets extends WireData implements Module, ConfigurableModule
{
  /**
   * Extension of the first added asset
   * to determine whether to render <script> or <link>
   * @var mixed
   */
  private $ext;

  /** @var FilenameArray */
  private $files;

  private $preventMinify = [];

  public function init()
  {
    // attach autoloader
    require_once __DIR__ . "/vendor/autoload.php";

    // init variables
    $this->files = new FilenameArray();

    // hooks
    wire()->addHookAfter('Modules::refresh', $this, 'resetCache');
  }

  /**
   * Add file to assets
   * Supports either full paths or paths relative to pw root.
   * Does not check if the added file exists!
   */
  public function add(string $file, bool $preventMinify = false): self
  {
    // never add files if not in debug mode
    // to save some extra milliseconds
    if (!wire()->config->debug) return $this;
    $file = $this->toPath($file);
    $this->setExtension($file);
    $this->files->add($file);
    if ($preventMinify) $this->preventMinify[$this->toUrl($file)] = true;
    return $this;
  }

  public function addAll(
    string $dir,
    string $extension = null,
    bool $addDotFiles = false,
    bool $addUnderscoreFiles = false,
    bool $preventMinify = false,
  ): self {
    $options = $extension
      ? ['extensions' => [$extension]]
      : ['extensions' => ['js', 'css']];
    $dir = $this->toPath($dir);
    foreach (wire()->files->find($dir, $options) as $file) {
      $name = basename($file);
      if (!$addDotFiles && str_starts_with($name, '.')) continue;
      if (!$addUnderscoreFiles && str_starts_with($name, '_')) continue;
      $this->add($file, $preventMinify);
    }
    return $this;
  }

  private function compile(string $file, bool $minify): self
  {
    // no minify? simply merge all files to one
    if (!$minify) {
      $content = $this->mergeFiles();
      wire()->files->filePutContents($file, $content);
    } else {
      if ($this->ext === 'JS') $min = new JS();
      elseif ($this->ext === 'CSS') $min = new CSS();
      else throw new WireException($this->ext . ' not implemented yet');

      foreach ($this->files as $f) {
        $url = $this->toUrl($f);
        if (array_key_exists($url, $this->preventMinify)) {
          $min->add("/*! nominify-$url */");
        } else $min->add($f);
      }
      $min->minify($file);
      $this->replaceNoMinifyTags($file);
    }
    return $this;
  }

  public function __debugInfo(): array
  {
    return [
      'files' => $this->filesArray(),
    ];
  }

  public function filesArray($useUrls = false): array
  {
    $files = [];
    foreach ($this->files as $file) {
      $files[] = $useUrls ? $this->toUrl($file) : $file;
    }
    return $files;
  }

  /**
   * Files string as used for the cache data
   */
  public function filesString($useUrls = true): string
  {
    // add info about prevented minify files
    $prevent = "::prevent::" . implode(',', array_keys($this->preventMinify));
    return implode(',', $this->filesArray($useUrls)) . $prevent;
  }

  private function getCacheKey(string $file, bool $minify): string
  {
    return 'rockassets-' . $this->toUrl($file) . ($minify ? '-min' : '');
  }

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    return $inputfields;
  }

  private function mergeFiles(): string
  {
    $content = "";
    foreach ($this->files as $f) {
      if (!is_file($f)) return "File not found: $f";
      $content .= file_get_contents($f) . "\n";
    }
    return $content;
  }

  /**
   * Check if the file or settings have changed and we need to recompile
   */
  private function recompile(string $file, bool $minify): bool
  {
    if (wire()->config->rockassetsForceRecompile) return true;

    // no debug mode, no recompile
    if (!wire()->config->debug) return false;

    // if file does not exist, we need to recompile
    if (!is_file($file)) return true;

    // otherwise we get the modified timestamp from cache
    // we don't use filemtime because we want to recompile also
    // if any of the settings changed (like recompile)
    $key = $this->getCacheKey($file, $minify);
    $cache = (string)wire()->cache->get($key);
    $parts = explode(':::', $cache, 2);
    $mCache = (int)$parts[0];
    $oldFiles = @$parts[1];

    // did files change? eg when a file was removed
    if ($this->filesString() !== $oldFiles) return true;

    // get the latest modified timestamp from any of the files
    $mFile = filemtime($file);
    foreach ($this->files as $f) $mFile = max($mFile, filemtime($f));

    // if cache is newer we don't need to recompile
    if ($mCache >= $mFile) return false;

    // otherwise we need to recompile
    return true;
  }

  public function render(): string
  {
    return "TBD";
  }

  private function replaceNoMinifyTags(string $file): void
  {
    // if no items in the array, nothing to do
    if (!count($this->preventMinify)) return;

    // replace all nominify tags with the original content
    $content = wire()->files->fileGetContents($file);
    foreach (array_keys($this->preventMinify) as $url) {
      $_file = $this->toPath($url);
      $_content = wire()->files->fileGetContents($_file);
      $content = str_replace(
        "/*! nominify-$url */",
        $_content,
        $content
      );
    }

    // write back to file
    wire()->files->filePutContents($file, $content);
  }

  protected function resetCache(HookEvent $event): void
  {
    wire()->cache->delete('rockassets-*');
  }

  public function saveIf(
    bool $condition,
    string $file,
    bool $minify = true
  ): self {
    if ($condition == false) return $this;
    return $this->saveTo($file, $minify);
  }

  public function saveTo(string $file, bool $minify = true): self
  {
    $file = $this->toPath($file);
    if (!$this->recompile($file, $minify)) return $this;

    // make sure the folder exists
    wire()->files->mkdir(dirname($file));

    // recompile file
    bd($this->filesArray());
    if (!$this->ext) throw new WireException("No files added yet");
    $this->compile($file, $minify);

    // update cache
    $key = $this->getCacheKey($file, $minify);
    $content = time() . ':::' . $this->filesString();
    wire()->cache->save($key, $content, WireCache::expireNever);

    // log
    $this->log("Recompiled $file");
    if (function_exists('bd')) bd("Recompiled $file", "RockAssets");

    return $this;
  }

  private function setExtension(string $file): void
  {
    if ($this->ext) return;
    $this->ext = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
  }

  /**
   * Return a path that starts with the pw root without a trailing slash
   *
   * Supports both files and directories.
   * Does not check if the file or directory exists!
   *
   * Examples:
   *
   * toPath('site/templates')   // /var/www/html/site/templates
   * toPath('site/templates/')  // /var/www/html/site/templates
   * toPath('/site/templates/') // /var/www/html/site/templates
   *
   * toPath('/site/ready.php')  // /var/www/html/site/ready.php
   */
  public function toPath(string $path): string
  {
    $path = Paths::normalizeSeparators(trim($path));

    // if it is a directory we add a slash
    // so that we can later compare with root path
    if (is_dir($path)) $path = rtrim($path, '/') . '/';

    $root = wire()->config->paths->root;
    if (!$path) return rtrim($root, '/');
    if (str_starts_with($path, $root)) return rtrim($path, '/');
    if (str_starts_with($path, '/site/')) return $root . trim($path, '/');
    if (str_starts_with($path, 'site/')) return $root . trim($path, '/');
    if ($path === 'site') return $root . $path;
    if (str_starts_with($path, '/wire/')) return $root . trim($path, '/');
    if (str_starts_with($path, 'wire/')) return $root . trim($path, '/');
    if ($path === 'wire') return $root . $path;
    throw new WireException("Invalid Path $path");
  }

  /**
   * Return a url relative to the pw root without a trailing slash
   *
   * Supports both files and directories.
   * Does not check if the file or directory exists!
   *
   * Examples:
   *
   * toUrl('site/templates')   // /site/templates
   * toUrl('site/templates/')  // /site/templates
   * toUrl('/site/templates/') // /site/templates
   *
   * toUrl('/site/ready.php')  // /site/ready.php
   */
  public function toUrl(string $path): string
  {
    $path = str_replace(
      wire()->config->paths->root,
      wire()->config->urls->root,
      $this->toPath($path) . '/'
    );
    return rtrim($path, '/');
  }
}
