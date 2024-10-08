# Saving

<div class="uk-alert uk-alert-warning">
  Please not that RockAssets will only ever create files if `$config->debug` is true! This is both for security and performance reasons. On production systems RockAssets will neither create any files nor add any files to it's file collection. It will only output the final `< script >` or `< link >` tag when requested via `render()`.

  This is because we assume that you use RockAssets during local development and use a proper deployment process to move your final assets to production (or copy them over manually).
</div>

## saveTo()

Note: