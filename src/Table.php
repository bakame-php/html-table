<?php

declare(strict_types=1);

namespace Bakame\HtmlTable;

use Closure;
use Iterator;
use League\Csv\TabularDataReader;

final class Table implements TabularDataReader
{
    public function __construct(
        private readonly TabularDataReader $tabularDataReader,
        private readonly ?string $caption = null
    ) {
    }

    public function count(): int
    {
        return $this->tabularDataReader->count();
    }

    public function getIterator(): Iterator
    {
        return $this->tabularDataReader->getIterator();
    }

    public function each(Closure $closure): bool
    {
        return $this->tabularDataReader->each($closure);
    }

    public function exists(Closure $closure): bool
    {
        return $this->tabularDataReader->exists($closure);
    }

    /**
     * @return array<string>
     */
    public function nth(int $nth_record): array
    {
        return $this->tabularDataReader->nth($nth_record);
    }

    /**
     * @return array<string>
     */
    public function first(): array
    {
        return $this->tabularDataReader->first();
    }

    public function filter(Closure $closure): TabularDataReader
    {
        return new self($this->tabularDataReader->filter($closure), $this->caption);
    }

    public function fetchColumnByName(string $name): Iterator
    {
        return $this->tabularDataReader->fetchColumnByName($name);
    }

    public function fetchColumnByOffset(int $offset): Iterator
    {
        return $this->tabularDataReader->fetchColumnByOffset($offset);
    }

    public function reduce(Closure $closure, mixed $initial = null): mixed
    {
        return $this->tabularDataReader->reduce($closure, $initial);
    }

    public function slice(int $offset, int $length = null): TabularDataReader
    {
        return new self($this->tabularDataReader->slice($offset, $length), $this->caption);
    }

    public function sorted(Closure $orderBy): TabularDataReader
    {
        return new self($this->tabularDataReader->sorted($orderBy), $this->caption);
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
        return $this->tabularDataReader->getHeader();
    }

    public function getRecords(array $header = []): Iterator
    {
        return $this->tabularDataReader->getRecords($header);
    }

    /**
     * @return array<string>
     */
    public function fetchOne(int $nth_record = 0): array
    {
        return $this->tabularDataReader->fetchOne($nth_record);
    }

    public function fetchPairs($offset_index = 0, $value_index = 1): Iterator
    {
        return $this->tabularDataReader->fetchPairs($offset_index, $value_index);
    }

    public function fetchColumn($index = 0): Iterator
    {
        return $this->tabularDataReader->fetchColumn($index);
    }
}
