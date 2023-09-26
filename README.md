# HTML Table

[![Author](http://img.shields.io/badge/author-@nyamsprod-blue.svg?style=flat-square)](https://twitter.com/nyamsprod)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build](https://github.com/bakame-php/html-table/workflows/build/badge.svg)](https://github.com/bakame-php/html-table/actions?query=workflow%3A%22build%22)
[![Latest Version](https://img.shields.io/github/release/bakame-php/html-table.svg?style=flat-square)](https://github.com/bakame-php/html-table/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/bakame/html-table.svg?style=flat-square)](https://packagist.org/packages/bakame/html-table)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://github.com/sponsors/nyamsprod)

`bakame/html-table` is a small PHP package that allows you to parse, import tabular data represented as
HTML Table. Once installed you will be able to do the following:

```php
use Bakame\HtmlTable\Parser;

$table = Parser::new()
    ->tableHeader(['rank', 'move', 'team', 'player', 'won', 'drawn', 'lost', 'for', 'against', 'gd', 'points'])
    ->parseFile('https://www.bbc.com/sport/football/tables');

$table
    ->filter(fn (array $row) => (int) $row['points'] >= 10)
    ->sorted(fn (array $rowA, array $rowB) => (int) $rowB['for'] <=> (int) $rowA['for'])
    ->fetchPairs('team', 'for');

// returns 
// [
//  "Brighton" => "15"
//  "Man City" => "14"
//  "Tottenham" => "13"
//  "Liverpool" => "12"
//  "West Ham" => "10"
//  "Arsenal" => "9"
// ]
```

The Package is responsible for the parsing of the HTML, the manipulation methods used
are part of the `league\csv` package. Please refer to
[its documentation](https://csv.thephpleague.com) for more information.

## System Requirements

**PHP8.1+ and league\csv >= 9.9.0** library is required.

## Installation

Use composer:

```
composer require bakame/html-table
```

## Documentation

The `Parser` can convert a file (a PHP stream or a Path with an optional context like `fopen`)
or an HTML document into a `League\Csv\TabularData` implementing object. Once converted you
can use all the methods and feature made available by this interface
(see [ResultSet](https://csv.thephpleague.com/9.0/reader/resultset/)) for more information.

The `Parser` is immutable, whenever you change a configuration a new instance is returned.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new();

$table = $parser->parseHtml('<table>...</table>');
$table = $parser->parseFile('path/to/html/file.html');
```

It is possible to configure the parser to improve HTML table resolution:

### tablePosition

Tells which table to parse in the HTML page: If the single argument is

- a string; it will represent the value of the table "id" attribute.
- a positive integer or `0`; it will represent the table offset.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->tablePosition('table-id'); // parse the <table id='table-id>
$parser = Parser::new()->tablePosition(3);          // parse the 4th table of the page
```

### ignoreTableHeader and resolveTableHeader

Tells the parser to attempt or not table header resolution.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->ignoreTableHeader();   // no header table will be calculated
$parser = Parser::new()->resolveTableHeader(3); // will attempt to resolve the table header
```

### tableHeaderPosition

Tells where to locate and resolve the table header

```php
use Bakame\HtmlTable\Parser;
use Bakame\HtmlTable\Section;

$parser = Parser::new()->tableHeaderPosition(Section::thead, 3); // no header table will be calculated
```

use the `Bakame\HtmlTable\Section` enum to designate which table section to use to resolve the header

```php
use Bakame\HtmlTable\Section;

enum Section
{
    case thead;
    case tbody;
    case tfoot;
    case none;
}
```

If `Section::none` is used, `tr` tags will be used independently of their section.
The second argument is the table header offset; it defaults to `0` (ie: the first row).

### tableHeader

You can specify directly the header of your table and override any other table header
related configuration with this one

```php
use Bakame\HtmlTable\Parser;
use Bakame\HtmlTable\Section;

$parser = Parser::new()->tableHeader(['rank', 'team', 'winner']); // no header table will be calculated
```

**If you specify a non empty array as the table header, it will take precedence over any other table header related options.**

### includeTableFooter and excludeTableFooter

Tells whether the footer should be included when parsing the table content.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->includeTableFooter();   // tfoot is included during parsing
$parser = Parser::new()->excludeTableFooter(3); // tfoot is excluded during parsing
```

### ignoreXmlErrors and failOnXmlErrors

Tells whether the parser should ignore or throw in case of malformed HTML content.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->ignoreXmlErrors();   // ignore the XML errors
$parser = Parser::new()->failOnXmlErrors(3); // throw on XML errors
```

### withFormatter and withoutFormatter

Adds or remove a record formatter applied to the data extracted from the table before you
can access it. The header is not affected by the formatter if it is defined.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->withFormatter($formatter); // attach a formatter to the parser
$parser = Parser::new()->withoutFormatter();        // removed the attached formatter if it exists
```

The formatter closure signature should be:

```php
function (array $record): array;
```

If a header was defined or specified, the submitted record will have the header definition set,
otherwise an array list is provided.

### Default behaviour

By default, when calling the `Parser::new()` named constructor you will:

- try to parse the first table found in the page
- expect the table header row to be the first `tr` found in the `thead` section of your table
- include the table `tfoot` section
- ignore XML errors.
- no formatter is attached to the parser.

## Testing

The library:

- has a [PHPUnit](https://phpunit.de) test suite
- has a coding style compliance test suite using [PHP CS Fixer](https://cs.sensiolabs.org/).
- has a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).

To run the tests, run the following command from the project folder.

``` bash
composer test
```

## Security

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

## Credits

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/html-table/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
