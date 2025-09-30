<?php

declare(strict_types=1);

namespace Bakame\TabularData\HtmlTable;

use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use League\Csv\TabularDataProvider;
use League\Csv\TabularDataReader;

/**
 * @template TValue of array<array-key, mixed>
 *
 * @implements IteratorAggregate<TValue>
 */
final class Table implements IteratorAggregate, Countable, JsonSerializable, TabularDataProvider
{
    /**
     * @param TabularDataReader<TValue> $tabularData
     */
    public function __construct(
        private readonly TabularDataReader $tabularData,
        private readonly ?string $caption = null
    ) {
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * @return array<string>
     */
    public function getHeader(): array
    {
        return $this->tabularData->getHeader();
    }

    /**
     * @return TabularDataReader<TValue> $tabularData
     */
    public function getTabularData(): TabularDataReader
    {
        return $this->tabularData;
    }

    public function count(): int
    {
        return $this->tabularData->count();
    }

    public function getIterator(): Iterator
    {
        return $this->tabularData->getIterator();
    }

    /**
     * @return array{
     *     caption: ?string,
     *     header: array<string>,
     *     rows:array<int, array<mixed>>
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'caption' => $this->caption,
            'header' => $this->getHeader(),
            'rows' => array_values([...$this->tabularData]),
        ];
    }
}
