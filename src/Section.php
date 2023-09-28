<?php

declare(strict_types=1);

namespace Bakame\HtmlTable;

enum Section: string
{
    case thead = 'thead';
    case tbody = 'tbody';
    case tfoot = 'tfoot';
    case tr = 'tr';

    /**
     * @param int<0, max> $offset
     */
    public function xpathRow(int $offset = 0): string
    {
        if ($offset < 0) { /* @phpstan-ignore-line */
            throw new ParserError('The table header row offset must be a positive integer or 0.');
        }

        ++$offset;
        return match ($this) {
            self::tr => '(//table/tr)['.$offset.']',
            default => '(//table/'.$this->name.'/tr)['.$offset.']',
        };
    }
}
