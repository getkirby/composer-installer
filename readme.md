# Kirby Composer Installer

This is Kirby's custom [Composer installer](https://getcomposer.org/doc/articles/custom-installers.md) for the Kirby CMS.
It is responsible for automatically choosing the correct installation paths if you install the CMS via Composer.

## Default configuration

If you `require` the `getkirby/cms` package in your own `composer.json`, there is nothing else you need to do:

```js
{
  "require": {
    "getkirby/cms": "^3.0"
  },
  "repositories": [
    {
      "type": "composer",
      "url":  "https://composer.getkirby.com"
    }
  ]
}
```

Kirby's Composer installer (this repo) will run automatically and will install the CMS to the `kirby` directory.

## Custom installation path

You might want to use a different installation path. The path can be configured like this in your `composer.json`:

```js
{
  "require": {
    "getkirby/cms": "^3.0"
  },
  "repositories": [
    {
      "type": "composer",
      "url":  "https://composer.getkirby.com"
    }
  ],
  "extra": {
    "kirby-cms-path": "kirby" // change this to your custom path
  }
}
```

## License

<http://www.opensource.org/licenses/mit-license.php>

## Author

Lukas Bestle <https://getkirby.com>
