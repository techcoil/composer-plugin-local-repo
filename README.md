## Local Repo Composer Plugin
Composer plugin to help treating local repositories as packages.<br>
Make use of composer's `path` repository behind the scenes.

### Installation
Add the following to your `composer.json` file:

Install the plugin with
```bash
$ composer require techcoil/composer-plugin-local-repo
```

```json
{
    "require": {
        "techcoil/composer-plugin-local-repo": "*"
    },
    ...
    "config": {
      ...
      "allow-plugins": {
        "techcoil/composer-plugin-local-repo": true
      }
    }
}
```

## Config

Config overrides should be placed inside `extra.local-repo` in your `composer.json` file.

| Key | Description                                                                                                | Default |
| --- |------------------------------------------------------------------------------------------------------------| --- |
 | `paths` | An array of paths to search for packages.                                                                  | `["src"]` |
| `depth` | The depth of folders to search for packages.                                                               | `1` |
| `symlink` | Whether to symlink the packages or copy them. <br />See https://getcomposer.org/doc/05-repositories.md#path | `true` |
 | `ignore` | An array of folder patterns to ignore.                                        | `[]` |


### Example

```json
// composer.json
{
     ...
     "extra": {
          "local-repo": {
                "paths": ["src", "packages"],
                "depth": 2,
                "symlink": false,
                "ignore": ["*_test", "**/test/*"]
          }
     }
}
```