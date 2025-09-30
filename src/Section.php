<?php

declare(strict_types=1);

namespace Bakame\TabularData\HtmlTable;

enum Section: string
{
    case Thead = 'thead';
    case Tbody = 'tbody';
    case Tfoot = 'tfoot';
    case Tr = 'tr';

    /**
     * @param int<0, max> $offset
     *
     * @throws ParserError
     */
    public function xpathRow(int $offset = 0): string
    {
        $offset > -1 || throw new ParserError('The table header row offset must be a positive integer or 0.'); /* @phpstan-ignore-line */

        ++$offset;
        return match ($this) {
            self::Tr => '(//table/tr)['.$offset.']',
            default => '(//table/'.$this->value.'/tr)['.$offset.']',
        };
    }
}
