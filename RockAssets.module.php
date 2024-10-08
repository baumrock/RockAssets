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
    require_once __DIR__ . "/vendor/autoload.php";
    $this->files = new FilenameArray();
  }

  /**
   * Add file to assets
   * Supports either full paths or paths relative to pw root.
   * Does not check if the added file exists!
   */
  public function add(string $file): self
  {
    // never add files if not in debug mode
    // see docs about saving files
    if (!wire()->config->debug) return $this;
    $file = $this->toPath($file);
    $this->setExtension($file);
    $this->files->add($file);
    return $this;
  }

  public function addAll(string $dir, string $extension = null): self
  {
    $options = $extension
      ? ['extensions' => [$extension]]
      : ['extensions' => ['js', 'css']];
    foreach (wire()->files->find($dir, $options) as $file) {
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

  public function filesArray(): array
  {
    $files = [];
    foreach ($this->files as $file) $files[] = $file;
    return $files;
  }

  private function getCacheKey(string $file, bool $minify): string
  {
    return 'rockassets-' . $file . ($minify ? '-min' : '');
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
    $mCache = (int)wire()->cache->get($key);
    $mFile = filemtime($file);

    // if cache is newer we don't need to recompile
    if ($mCache >= $mFile) return false;

    // otherwise we need to recompile
    return true;
  }

  public function render(): string
  {
    return "TBD";
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
    wire()->cache->save($key, time());

    return $this;
  }

  private function setExtension(string $file): void
  {
    if ($this->ext) return;
    $this->ext = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
  }

  public function toPath(string $file): string
  {
    $file = Paths::normalizeSeparators($file);
    $root = wire()->config->paths->root;
    if (str_starts_with($file, $root)) return $file;
    if (str_starts_with($file, '/site/')) return $root . ltrim($file, '/');
    if (str_starts_with($file, 'site/')) return $root . $file;
    if (str_starts_with($file, '/wire/')) return $root . ltrim($file, '/');
    if (str_starts_with($file, 'wire/')) return $root . $file;
    throw new WireException("Invalid Path $file");
  }
}
