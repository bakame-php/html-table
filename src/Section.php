<?php

declare(strict_types=1);

namespace Bakame\HtmlTable;

enum Section
{
    case thead;
    case tbody;
    case tfoot;
    case none;

    public function xpath(): string
    {
        return match ($this) {
            self::none => '//table/tr',
            default => '//table/'.$this->name.'/tr',
        };
    }
}
