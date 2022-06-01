# Matomo-Icons

[![Build Status](https://api.travis-ci.com/matomo-org/matomo-icons.svg?branch=master)](https://travis-ci.com/matomo-org/matomo-icons)

This reposistory provides the source files for the icons in [matomo](https://github.com/matomo-org/matomo) and the scripts used to resize them to a common size.

## Contributing

An icon is missing or you have a better one? Create a [new issue](https://github.com/matomo-org/matomo-icons/issues/new) or, even better, open a pull request.
You can find a up-to-date list of all improvable icons on [Travis](https://app.travis-ci.com/github/matomo-org/matomo-icons).

All source files except those in `devices`, `flags`, `searchEngines` and `socials` need to have a second file called `iconname.ext.source` that mentions where the image is from.

### Naming conventions

| icon type | example | possible names |
| --------- | ------- | ----------- |
|brand|*Apple*| *Device detection* in Matomo Administration page|
|browsers|*FF*|https://github.com/matomo-org/device-detector/blob/master/Parser/Client/Browser.php#L37 |
|devices|*smartphone*| *Device detection* in Matomo Administration page|
|flags|*at*| all except *un* and *gb-** |
|os|*WIN*|https://github.com/matomo-org/device-detector/blob/master/Parser/OperatingSystem.php#L38 |
|plugins|*flash*|files in [plugins/DevicePlugins/Columns/](https://github.com/matomo-org/matomo/tree/4.x-dev/plugins/DevicePlugins/Columns) |
|searchEngines|*google.com*|https://github.com/matomo-org/searchengine-and-social-list/blob/master/SearchEngines.yml |
|SEO|*bing.com*|https://github.com/matomo-org/matomo/tree/4.x-dev/plugins/SEO |
|socials|*facebook.com*|https://github.com/matomo-org/searchengine-and-social-list/blob/master/Socials.yml |

### File Formats

Ideally all source files should be SVGs or high resolution (>100px) PNGs. As this is not always possible, JPGs, GIFs and (even multiresolution) ICOs are supported.
