<?php

declare(strict_types=1);

namespace Bakame\TabularData\HtmlTable;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParserErrorTest extends TestCase
{
    #[Test]
    public function it_will_return_the_duplicated_column_names(): void
    {
        $headerRow = ['foo', 'foo', 'toto', 'toto', 'baz'];
        $exception = ParserError::dueToDuplicateHeaderColumnNames($headerRow);

        self::assertSame('The header record contains duplicate column names: `foo`, `toto`.', $exception->getMessage());
        self::assertSame(['foo', 'toto'], $exception->duplicateColumnNames());
    }
}
