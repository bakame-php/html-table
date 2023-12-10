<?php

declare(strict_types=1);

namespace Bakame\TabularData\HtmlTable;

use Bakame\Aide\Enum\Helper;

enum Section: string
{
    use Helper;

    case Thead = 'thead';
    case Tbody = 'tbody';
    case Tfoot = 'tfoot';
    case Tr = 'tr';

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
            self::Tr => '(//table/tr)['.$offset.']',
            default => '(//table/'.$this->value.'/tr)['.$offset.']',
        };
    }
}
