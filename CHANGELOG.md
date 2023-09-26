# Changelog

All Notable changes to `bakame/html-table` will be documented in this file.

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
