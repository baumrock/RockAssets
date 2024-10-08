<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 08.10.2024
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockAssets extends WireData implements Module, ConfigurableModule
{
  /** @var FilenameArray */
  private $files;

  public function init()
  {
    $this->files = new FilenameArray();
  }

  /**
   * Add file to assets
   * Supports either full paths or paths relative to pw root.
   * Does not check if the added file exists!
   */
  public function add(string $file): self
  {
    $file = Paths::normalizeSeparators($file);
    $root = wire()->config->paths->root;
    if (str_starts_with($file, '/site/')) $file = $root . ltrim($file, '/');
    if (str_starts_with($file, 'site/')) $file = $root . $file;
    if (str_starts_with($file, '/wire/')) $file = $root . ltrim($file, '/');
    if (str_starts_with($file, 'wire/')) $file = $root . $file;
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

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    return $inputfields;
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
    return $this;
  }

  public function saveTo(string $file): self
  {
    return $this;
  }
}
