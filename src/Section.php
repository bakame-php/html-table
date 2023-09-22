<?php

declare(strict_types=1);

namespace Bakame\HtmlTable;

enum Section: string
{
    case Header = 'thead';
    case Body = 'tbody';
    case Footer = 'tfoot';
    case None = '';

    public function xpath(): string
    {
        return match ($this) {
            self::None => '//table/tr',
            default => '//table/'.$this->value.'/tr',
        };
    }
}
