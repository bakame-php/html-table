# HTML Table

[![Author](http://img.shields.io/badge/author-@nyamsprod-blue.svg?style=flat-square)](https://twitter.com/nyamsprod)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build](https://github.com/bakame-php/html-table/workflows/build/badge.svg)](https://github.com/bakame-php/html-table/actions?query=workflow%3A%22build%22)
[![Latest Version](https://img.shields.io/github/release/bakame-php/html-table.svg?style=flat-square)](https://github.com/bakame-php/html-table/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/bakame/html-table.svg?style=flat-square)](https://packagist.org/packages/bakame/html-table)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://github.com/sponsors/nyamsprod)

`bakame/html-table` is a small PHP package that allows you to parse, import and manipulate
tabular data represented as HTML Table. Once installed you will be able to do the following:

```php
use Bakame\TabularData\HtmlTable\Parser;

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

## System Requirements

**league\csv 9.23.0** library is required. (since version 0.4.0).

## Installation

Use composer:

```
composer require bakame/html-table
```

## Documentation

The `Parser` can convert a file (a PHP stream or a Path with an optional context like `fopen`)
or an HTML document into a `League\Csv\TabularData` implementing object. Once converted you
can use all the methods and feature made available by the interface (see [ResultSet](https://csv.thephpleague.com/9.0/reader/resultset/))
for more information.

**The `Parser` itself is immutable, whenever you change a configuration option a new instance is returned.**

**The `Parser` constructor is private to instantiate the object you are required to use the `new` method instead**

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()
    ->ignoreTableHeader()
    ->ignoreXmlErrors()
    ->withoutFormatter()
    ->tableCaption('This is a beautiful table');
```

### parseHtml and parseFile

To extract and parse your table use either the `parseHtml` or `parseFile` methods.
If parsing is not possible a `ParseError` exception will be thrown.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new();

$table = $parser->parseHtml('<table>...</table>');
$table = $parser->parseFile('path/to/html/file.html');
```

`parseHtml` parses an HTML page represented by:

- a `string`,
- a `Stringable` object,
- a `DOMDocument`,
- a `DOMElement`,
- or a `SimpleXMLElement`

whereas `parseFile` works with:

- a filepath,
- or a PHP readable stream.

Both methods return a `Table` instance which implements the `League\Csv\TabularDataReader`
interface and also give access to the table caption if present via the `getCaption` method.

```php
use Bakame\HtmlTable\Parser;

$html = <<<HTML
<div>
<table>
    <caption>Songs</caption>
    <thead>
        <tr>
            <th>Title</th>
            <th>Singer</th>
            <th>Country</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Nakei Nairobi</td>
            <td>Mbilia Bel</td>
            <td rowspan="3">DR Congo</td>
        </tr>
        <tr>
            <td>Muvaro</td>
            <td>Zaiko Langa Langa</td>
        </tr>
        <tr>
            <td>Nzinzi</td>
            <td>Emeneya</td>
        </tr>
    </tbody>
</table>
</div>
HTML;

$table = Parser::new()->parseHtml($html);
$table->getCaption(); //returns 'Songs'
$table->getHeader();  //returns ['Title','Singer', 'Country']
$table->nth(2); //returns ["Title" => "Nzinzi", "Singer" => "Emeneya", "Country" => "DR Congo"]
json_encode($table->slice(0, 1));
//{"caption":"Songs","header":["Title","Singer","Country"],"rows":[{"Title":"Nakei Nairobi","Singer":"Mbilia Bel","Country":"DR Congo"}]}
```

#### Default configuration

By default, when calling the `Parser::new()` named constructor the parser will:

- try to parse the first table found in the page
- expect the table header row to be the first `tr` found in the `thead` section of your table
- exclude the table `thead` section when extracting the table content.
- ignore XML errors.
- have no formatter attached.
- have no default caption to used if none is present in the table.

Each of the following settings can be changed to improve the conversion against your business rules:

### tablePosition and tableXpathPosition

Selecting the table to parse in the HTML page can be done using two (2) methods
`Parser::tablePosition` and `Parser::tableXpathPosition`

If you know the table position in the page in relation with its integer offset or if
you know it's `id` attribute value you should use `Parser::tablePosition` otherwise
favor `Parser::tableXpathPosition` which expects an `xpath` expression.
If the expression is valid, and a list of table is found, the first result will be returned.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->tablePosition('table-id'); // parses the <table id='table-id'>
$parser = Parser::new()->tablePosition(3); // parses the 4th table of the page
$parser = Parser::new()->tableXPathPosition("//main/div/table");
//parse the first table that matches the xpath expression
```

**`Parser::tableXpathPosition` and `Parser::tablePosition` override each other. It is 
recommended to use one or the other but not both at the same time.**

### tableCaption

You can optionally define a caption for your table if none is present or found during parsing.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->tableCaption('this is a generated caption');
$parser = Parser::new()->tableCaption(null);  // remove any default caption set
```

### tableHeader, tableHeaderPosition, ignoreTableHeader and resolveTableHeader

The following settings configure the `Parser` in relation to the table header. By default,
the parser will try to parse the first `tr` tag found in the `thead` section of the table.
But you can override this behaviour using one of these settings:

#### tableHeaderPosition

Tells where to locate and resolve the table header

```php
use Bakame\HtmlTable\Parser;
use Bakame\HtmlTable\Section;

$parser = Parser::new()->tableHeaderPosition(Section::Thead, 3);
// header is the 4th row in the <thead> table section
```

The method uses the `Bakame\HtmlTable\Section` enum to designate which table section to use
to resolve the header

```php
use Bakame\HtmlTable\Section;

enum Section
{
    case thead;
    case tbody;
    case tfoot;
    case tr;
}
```

If `Section::tr` is used, `tr` tags will be used independently of their section.
The second argument is the table header `tr` offset; it defaults to `0` (ie: the first row).

#### ignoreTableHeader and resolveTableHeader

Instructs the parser to resolve or not the table header using `tableHeaderPosition` configuration.
If no resolution is done, no header will be included in the returned `Table` instance.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->ignoreTableHeader();  // no table header will be resolved
$parser = Parser::new()->resolveTableHeader(); // will attempt to resolve the table header
```

#### tableHeader

You can specify directly the header of your table and override any other table header
related configuration with this configuration

```php
use Bakame\HtmlTable\Parser;
use Bakame\HtmlTable\Section;

$parser = Parser::new()->tableHeader(['rank', 'team', 'winner']);
```

**If you specify a non-empty array as the table header, it will take precedence over any other table header related options.**

**Because it is a tabular data each cell MUST be unique otherwise an exception will be thrown**

You can skip or re-arrange the source columns by skipping them by their offsets and/or by
re-ordering the offsets.

```php
use Bakame\HtmlTable\Parser;
use Bakame\HtmlTable\Section;

$parser = Parser::new()->tableHeader([3 => 'rank',  7 => 'winner', 5 => 'team']);
// only 3 column will be extracted the 4th, 6th and 8th columns
// and re-arrange as 'rank' first and 'team' last
// if a column is missing its value will be PHP `null` type
```

### includeSection and excludeSection

Tells which section should be parsed based on the `Section` enum

```php
use Bakame\HtmlTable\Parser;
use Bakame\HtmlTable\Section;

$parser = Parser::new()->includeSection(Section::Tbody);  // thead and tfoot are included during parsing
$parser = Parser::new()->excludeSection(Section::Tr, Section::Tfoot); // table direct tr children and tfoot are not included during parsing
```

**By default, the `thead` section is not parse. If a `thead` row is selected to be the header, it will
be parsed independently of this setting.**

**⚠️Tips:** to be sure of which sections will be modified, first remove all previous setting
before applying your configuration as shown below:

```diff
- Parser::new()->includeSection(Section::tbody);
+ Parser::new()->excludeSection(...Section::cases())->includeSection(Section::tbody);
```

The first call will still include the `tfoot` and the `tr` sections, whereas the second call
remove any previous setting guaranting that only the `tbody` if present will be parsed.

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

The following formatter will work on any table content as long as it is defined as a string.

```php
$formatter = fn (array $record): array => array_map(strtolower(...), $record);
// the following formatter will convert all the fields from your table to lowercase.
```

The following formatter will only work if the table has a header attached to it with
a column named `count`.

```php
$formatter = function (array $record): array {
   $record['count'] = (int) $record['count'];
   
   return $record;
}
// the following formatter will convert the data of all count column into integer..
```

### ignoreXmlErrors and failOnXmlErrors

Tells whether the parser should ignore or throw in case of malformed HTML content.

```php
use Bakame\HtmlTable\Parser;

$parser = Parser::new()->ignoreXmlErrors();   // ignore the XML errors
$parser = Parser::new()->failOnXmlErrors(3); // throw on XML errors
```

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
