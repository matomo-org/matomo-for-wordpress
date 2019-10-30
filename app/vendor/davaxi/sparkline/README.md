# Sparkline
PHP Class (using GD) to generate sparklines

[![Build Status](https://travis-ci.org/davaxi/Sparkline.svg)](https://travis-ci.org/davaxi/Sparkline)
[![Latest Stable Version](https://poser.pugx.org/davaxi/sparkline/v/stable)](https://packagist.org/packages/davaxi/sparkline) 
[![Total Downloads](https://poser.pugx.org/davaxi/sparkline/downloads)](https://packagist.org/packages/davaxi/sparkline) 
[![Latest Unstable Version](https://poser.pugx.org/davaxi/sparkline/v/unstable)](https://packagist.org/packages/davaxi/sparkline) 
[![License](https://poser.pugx.org/davaxi/sparkline/license)](https://packagist.org/packages/davaxi/sparkline)
[![Maintainability](https://api.codeclimate.com/v1/badges/9a5da533685204c53989/maintainability)](https://codeclimate.com/github/davaxi/Sparkline/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/9a5da533685204c53989/test_coverage)](https://codeclimate.com/github/davaxi/Sparkline/test_coverage)
[![Issue Count](https://codeclimate.com/github/davaxi/Sparkline/badges/issue_count.svg)](https://codeclimate.com/github/davaxi/Sparkline)

## Installation

This page contains information about installing the Library for PHP.

### Requirements

- PHP version 5.3.0 or greater
- The GD PHP extension

### Obtaining the client library

There are two options for obtaining the files for the client library.

#### Using Composer

You can install the library by adding it as a dependency to your composer.json.

```json
  "require": {
    "davaxi/sparkline": "^1.1"
  }
```

#### Cloning from GitHub

The library is available on [GitHub](https://github.com/davaxi/Sparkline). You can clone it into a local repository with the git clone command.

```sh
git clone https://github.com/davaxi/Sparkline.git
```

### What to do with the files

After obtaining the files, ensure they are available to your code. If you're using Composer, this is handled for you automatically. If not, you will need to add the `autoload.php` file inside the client library.

```php
require '/path/to/sparkline/folder/autoload.php';
```

## Usage

Example: 

![Sparkline](https://raw.githubusercontent.com/davaxi/Sparkline/master/tests/data/testGenerate2-mockup.png)

```php
<?php

require '/path/to/sparkline/folder/autoload.php';

$sparkline = new Davaxi\Sparkline();
$sparkline->setData(array(2,4,5,6,10,7,8,5,7,7,11,8,6,9,11,9,13,14,12,16));
$sparkline->display();

?>
```

## Documentation

```php
$sparkline = new Davaxi\Sparkline();

// Change format (Default value 80x20)
$sparkline->setFormat('100x40');
// or 
$sparkline->setWidth(100);
$sparkline->setHeight(40);

// Apply padding
$sparkline->setPadding('10'); // > top: 10 | right: 10 | bottom: 10 | left: 10
$sparkline->setPadding('10 20'); // > top: 10 | right: 20 | bottom: 10 | left: 20
$sparkline->setPadding('10 20 30'); // > top: 10 | right: 20 | bottom: 30 | left: 20
$sparkline->setPadding('10 20 30 40'); // > top: 10 | right: 20 | bottom: 30 | left: 40

// Change background color (Default value #FFFFFF)
$sparkline->setBackgroundColorHex('#0f354b');
// or
$sparkline->setBackgroundColorRGB(15, 53, 75);
// or
$sparkline->deactivateBackgroundColor();

// Change line color (Default value #1388db)
$sparkline->setLineColorHex('#1c628b');
// or
$sparkline->setLineColorRGB(28, 98, 139);

// Change line thickness (Default value 1.75 px)
$sparkline->setLineThickness(2.2);

// Change fill color (Default value #e6f2fa)
$sparkline->setFillColorHex('#8b1c2b');
// or
$sparkline->setFillColorRGB(139, 28, 43);
// or
$sparkline->deactivateFillColor();

$sparkline->setData(array(.....)); // Set data set
$sparkline->getData(); // Get seted data
$sparkline->generate(); // If ou want regenerate picture 

// Change base of height value (default max($data))
$sparkline->setBase(20);

// Change origin of chart value (yAxis) (default: 0)
$sparkline->setOriginValue(40);

// Add dot on first/last/minimal/maximal value
// Data set before used method
$sparkline->addPoint('minimum', 3, '#efefef');
$sparkline->addPoint('maximum', 3, '#efefef');
$sparkline->addPoint('first', 3, '#efefef');
$sparkline->addPoint('last', 3, '#efefef');

// Or by index
$sparkline->addPoint(1, 3, '#efefef');

// If display
$sparkline->setEtag('your hash'); // If you want add ETag header
$sparkline->setFilename('yourPictureName'); // For filenamen header
$sparkline->setExpire('+1 day'); // If you want add expire header
// or
$sparkline->setExpire(strtotime('+1 day'));
$sparkline->display(); // Display with correctly headers

// If save
$sparkline->save('/your/path/to/save/picture');

$sparkline->destroy(); // Destroy picture after generated / displayed / saved
```




