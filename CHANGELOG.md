# Changelog

All Notable changes to `bakame/html-table` will be documented in this file.

# [Next](https://github.com/bakame-php/html-table/compare/0.5.0...main) - TBD

* **BC BREAK:** the `Table` class now implements the `TabularDataProvider` instead of the `TabularDataReader` interface.
* **BC BREAK:** the `ParserError` class now extends the `Exception` instead of the `InvalidArgumentException` exception class.

# [0.5.0](https://github.com/bakame-php/html-table/compare/0.4.0...0.5.0) - 2025-07-06

## What's Changed

* Upgrade dependencies on `aide-error` to version `0.2.0`
* fix use statement by @tacman in https://github.com/bakame-php/html-table/pull/6

## New Contributors

* @tacman made their first contribution in https://github.com/bakame-php/html-table/pull/6

**Full Changelog**: https://github.com/bakame-php/html-table/compare/0.4.0...0.5.0

# [0.4.0](https://github.com/bakame-php/html-table/compare/0.3.0...0.4.0) - 2025-05-11

## What's Changed

* updates namespace in docs by @danieldevine in https://github.com/bakame-php/html-table/pull/3
* Add support for PHP8.4 by @nyamsprod
* But league/csv requirement to version 9.23.0 by @nyamsprod

## New Contributors

* @danieldevine made their first contribution in https://github.com/bakame-php/html-table/pull/3

**Full Changelog**: https://github.com/bakame-php/html-table/compare/0.3.0...0.4.0

## [0.3.0](https://github.com/bakame-php/html-table/compare/0.2.0...0.3.0) - 2023-09-29

### Added

- `Parser::tableXpathPosition`
- `Parser::tableCaption`
- `Table` class which implements the `TabularDataReader` interface.
- `Parser::includeSections` and `Parser::excludeSections` to improve section parsing.

### Fixed

- Improve identifier validation for `Parser::tablePosition`
- Remove the `$tableOffset` property.
- `tableHeader` can now re-arrange the table column and remove any unwanted column.

### Deprecated

- None

### Removed

- `Parser::(in|ex)cludeTableFooter` replaced by `Parser::(in|ex)cludeSections`

## [0.2.0](https://github.com/bakame-php/html-table/compare/0.1.0...0.2.0) - 2023-09-26

### Added

- `Parser::withFormatter`
- `Parser::withoutFormatter`
- `ParserError` to replace `Error` exception

### Fixed

- Add support for table `rowspan` attribute
- Renamed `Section` enum values. The Enum is no longer a backed enum.
- Renamed `Parser::parseHTML` into `Parser::parseHtml` for consistency

### Deprecated

- None

### Removed

- `Error` exception is renamed `ParserError`

## [0.1.0] - 2023-09-22

**Initial release!**
