<?php

namespace ProcessWire;

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
  public function add(string $file): self
  {
    // never add files if not in debug mode
    // to save some extra milliseconds
    if (!wire()->config->debug) return $this;
    $file = $this->toPath($file);
    $this->setExtension($file);
    $this->files->add($file);
    return $this;
  }

  public function addAll(
    string $dir,
    string $extension = null,
    bool $addDotFiles = false,
    bool $addUnderscoreFiles = false,
  ): self {
    $options = $extension
      ? ['extensions' => [$extension]]
      : ['extensions' => ['js', 'css']];
    foreach (wire()->files->find($dir, $options) as $file) {
      $name = basename($file);
      if (!$addDotFiles && str_starts_with($name, '.')) continue;
      if (!$addUnderscoreFiles && str_starts_with($name, '_')) continue;
      $this->add($file);
    }
    return $this;
  }

  private function compileJS(string $file, bool $minify): self
  {
    if ($minify) {
      // use minifier
      $min = new JS();
      foreach ($this->files as $f) $min->add($f);
      $min->minify($file);
    } else {
      // use custom merge
      $content = $this->mergeFiles();
      wire()->files->filePutContents($file, $content);
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
    return implode(',', $this->filesArray($useUrls));
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
    if ($this->ext === 'JS') $this->compileJS($file, $minify);
    else {
      throw new WireException($this->ext . ' not implemented yet');
    }

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

  public function toPath(string $path): string
  {
    $path = Paths::normalizeSeparators($path);
    $root = wire()->config->paths->root;
    if (str_starts_with($path, $root)) return $path;
    if (str_starts_with($path, '/site/')) return $root . ltrim($path, '/');
    if (str_starts_with($path, 'site/')) return $root . $path;
    if (str_starts_with($path, '/wire/')) return $root . ltrim($path, '/');
    if (str_starts_with($path, 'wire/')) return $root . $path;
    throw new WireException("Invalid Path $path");
  }

  public function toUrl(string $path): string
  {
    return str_replace(
      wire()->config->paths->root,
      wire()->config->urls->root,
      $this->toPath($path)
    );
  }
}
