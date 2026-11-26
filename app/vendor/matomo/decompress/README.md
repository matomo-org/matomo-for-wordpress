# Matomo/Decompress

Component providing several adapters to decompress files.

[![PHPUnit](https://github.com/matomo-org/component-decompress/actions/workflows/phpunit.yml/badge.svg)](https://github.com/matomo-org/component-decompress/actions/workflows/phpunit.yml)

It supports the following compression formats:

- Zip
- Gzip
- Bzip
- Tar (gzip or bzip)

With the following adapters:

- `PclZip`, based on the [PclZip library](http://www.phpconcept.net/pclzip/)
- `ZipArchive`, based on PHP's [Zip extension](http://fr.php.net/manual/en/book.zip.php)
- `Gzip`, based on PHP's native Gzip functions
- `Bzip`, based on PHP's native Bzip functions
- `Tar`, based on the [Archive_Tar library](https://github.com/pear/Archive_Tar) from PEAR

## Installation

With Composer:

```json
{
    "require": {
        "matomo/decompress": "^2.1"
    }
}
```

## Usage

All adapters have the same API as they implement `Matomo\Decompress\DecompressInterface`:

```php
// Extracting Gzip file
$extractor = new \Matomo\Decompress\Gzip('file.gz');

$extractedFiles = $extractor->extract('some/directory');

if ($extractedFiles === 0) {
    echo $extractor->errorInfo();
}

// Extracting Bzip file
$extractor = new \Matomo\Decompress\Bzip('file.bz');

$extractedFiles = $extractor->extract('some/directory');

if ($extractedFiles === 0) {
    echo $extractor->errorInfo();
}

// Extracting Zip file with ZipArchive
$extractor = new \Matomo\Decompress\ZipArchive('file.zip');

$extractedFiles = $extractor->extract('some/directory');

if ($extractedFiles === 0) {
    echo $extractor->errorInfo();
}

// Extracting Zip file with PclZip
$extractor = new \Matomo\Decompress\PclZip('file.zip');

$extractedFiles = $extractor->extract('some/directory');

if ($extractedFiles === 0) {
    echo $extractor->errorInfo();
}

// Extracting .tar.bz2 file
$extractor = new \Matomo\Decompress\Tar('file.tar.bz2', 'bz2');

$extractedFiles = $extractor->extract('some/directory');

if ($extractedFiles === 0) {
    echo $extractor->errorInfo();
}

// Extracting .tar.gz file
$extractor = new \Matomo\Decompress\Tar('file.tar.gz', 'gz');

$extractedFiles = $extractor->extract('some/directory');

if ($extractedFiles === 0) {
    echo $extractor->errorInfo();
}
```

## License

The Decompress component is released under the [LGPL v3.0](https://choosealicense.com/licenses/lgpl-3.0/).
