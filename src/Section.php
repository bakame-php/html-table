<?php

declare(strict_types=1);

namespace Bakame\HtmlTable;

enum Section: string
{
    case thead = 'thead';
    case tbody = 'tbody';
    case tfoot = 'tfoot';
    case tr = 'tr';

    public function xpath(): string
    {
        return match ($this) {
            self::tr => '//table/tr',
            default => '//table/'.$this->name.'/tr',
        };
    }
}
