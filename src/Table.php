<?php

declare(strict_types=1);

namespace Bakame\TabularData\HtmlTable;

use Closure;
use Iterator;
use JsonSerializable;
use League\Csv\TabularDataReader;

/**
 * @template TValue of array<array-key, mixed>
 *
 * @implements TabularDataReader<array<array-key, mixed>>
 */
final class Table implements TabularDataReader, JsonSerializable
{
    /**
     * @param TabularDataReader<array<array-key, mixed>> $tabularDataReader
     */
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
            'rows' => array_values([...$this->tabularDataReader]),
        ];
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

    /**
     *
     * @return Table<array<array-key, mixed>>
     */
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

    /**
     * @return Table<array<array-key, mixed>>
     */
    public function slice(int $offset, ?int $length = null): TabularDataReader
    {
        return new self($this->tabularDataReader->slice($offset, $length), $this->caption);
    }

    /**
     * @return Table<array<array-key, mixed>>
     */
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
     * @param array<int, string> $header
     */
    public function getObjects(string $className, array $header = []): Iterator
    {
        return $this->tabularDataReader->getObjects($className, $header);
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

    /**
     * @return TabularDataReader<array<array-key, mixed>>
     */
    public function select(string|int ...$columnOffsetOrName): TabularDataReader
    {
        return $this->tabularDataReader->select(...$columnOffsetOrName);
    }

    /** @return iterable<TabularDataReader<array<array-key, mixed>>> */
    public function matching(string $expression): iterable
    {
        return $this->tabularDataReader->matching($expression);
    }

    /**
     *
     * @return ?TabularDataReader<array<array-key, mixed>>
     */
    public function matchingFirst(string $expression): ?TabularDataReader
    {
        return $this->tabularDataReader->matchingFirst($expression);
    }

    /**
     *
     * @return TabularDataReader<array<array-key, mixed>>
     */
    public function matchingFirstOrFail(string $expression): TabularDataReader
    {
        return $this->tabularDataReader->matchingFirstOrFail($expression);
    }

    public function value(int|string $column = 0): mixed
    {
        return $this->tabularDataReader->value($column);
    }
}
