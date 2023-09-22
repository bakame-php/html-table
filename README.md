# HTML Table as Tabular Data parser

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

Parser::new()
    ->tableHeader(['rank', 'move', 'team', 'player', 'won', 'drawn', 'lost', 'for', 'against', 'gd', 'points'])
    ->parseFile('https://www.bbc.com/sport/football/tables')
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

## System Requirements

**PHP >= 8.1** and **league\csv** library are required.

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

$table = Parser::new()->parseHTML('<table>...</table>');
$table = Parser::new()->parseFile('path/to/html/file.html');
```

It is possible to configure the parser to improve HTML table resolution:

- `tablePosition` : tells which table to parse in the HTML page can be the table.
- `tableHeaderPosition`: tells where to find the table header row.
- `tableHeader`: submit your own table header
- `includeTableFooter`: include the table footer when parsing the table.
- `excludeTableFooter`: exclude the table footer when parsing the table.
- `ignoreTableHeader`: does not attempt to resolve the table header
- `resolveTableHeader`: Attempt to resolve the table header
- `ignoreXmlErrors`: ignore XML errors while loading the HTML source
- `failOnXmlErrors`: throws an exception in case of XML errors while loading the HTML source

Table section are defined via an Enum and are used to tell the parser where to look to find 
the table header row.

```php
use Bakame\HtmlTable\Section;

enum Section: string
{
    case Header = 'thead';
    case Body = 'tbody';
    case Footer = 'tfoot';
    case None = '';
}
```

By default, if you try to parse an HTML page and you did not change any configuration the `Parser` will:

- try to parse the first table found in the page
- expect the table header row to be the first `tr` found in the `thead` section of your table
- include the table `tfoot` section
- ignore XML errors.

## Testing

The library:

- has a [PHPUnit](https://phpunit.de) test suite
- has a coding style compliance test suite using [PHP CS Fixer](https://cs.sensiolabs.org/).
- has a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).
- is compliant with [the language agnostic HTTP Structured Fields Test suite](https://github.com/httpwg/structured-field-tests).

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
