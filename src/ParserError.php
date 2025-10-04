<?php

declare(strict_types=1);

namespace Bakame\TabularData\HtmlTable;

use Exception;
use LibXMLError;

use function array_count_values;
use function array_filter;
use function array_keys;
use function array_map;
use function implode;
use function sprintf;

use const PHP_EOL;

class ParserError extends Exception
{
    /** @var array<string>  */
    private array $duplicateColumnNames = [];

    /**
     * @param array<LibXMLError> $errors
     */
    public static function dueToLibXmlErrors(array $errors): self
    {
        $formatter = static fn (LibXMLError $error): string => sprintf('libxml error: %s in %s at line %d', $error->message, $error->file, $error->line);

        return new self(implode(PHP_EOL, array_map($formatter, $errors)));
    }

    /**
     * @return array<string>
     */
    public function duplicateColumnNames(): array
    {
        return $this->duplicateColumnNames;
    }

    /**
     * @param array<string> $header
     */
    public static function dueToDuplicateHeaderColumnNames(array $header): self
    {
        $duplicateColumnNames = array_keys(array_filter(array_count_values($header), fn (int $value): bool => $value > 1));

        $instance = new self('The header record contains duplicate column names: `'.implode('`, `', $duplicateColumnNames).'`.');
        $instance->duplicateColumnNames = $duplicateColumnNames;

        return $instance;
    }
}
