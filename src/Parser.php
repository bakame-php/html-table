<?php

declare(strict_types=1);

namespace Bakame\HtmlTable;

use ArrayIterator;
use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Iterator;
use League\Csv\ResultSet;
use League\Csv\SyntaxError;
use League\Csv\TabularDataReader;
use SimpleXMLElement;
use Stringable;

use function array_combine;
use function array_fill;
use function array_filter;
use function array_pad;
use function array_slice;
use function array_unique;
use function count;
use function fclose;
use function fopen;
use function in_array;
use function is_int;
use function is_resource;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function preg_match;
use function restore_error_handler;
use function set_error_handler;
use function stream_get_contents;
use function strtolower;

final class Parser
{
    private const CELL_TAGS = ['th', 'td'];
    private const HEADER_ROW_ATTRIBUTE_NAME = 'data-html-parser-select-header-row';

    /**
     * @param array<string> $tableHeader
     */
    private function __construct(
        private readonly string $expression,
        private readonly int $tableOffset,
        private readonly array $tableHeader,
        private readonly bool $ignoreTableHeader,
        private readonly Section $tableHeaderSection,
        private readonly int $tableHeaderOffset,
        private readonly bool $throwOnXmlErrors,
        private readonly bool $includeTableFooter,
        private readonly ?Closure $formatter = null,
    ) {
    }

    public static function new(): self
    {
        return new self(
            '//table',
            0,
            [],
            false,
            Section::Header,
            0,
            false,
            true,
            null,
        );
    }

    /**
     * @throws ParserError
     */
    public function tablePosition(int|string $positionOrId): self
    {
        $expression = '//table[@id="'.$positionOrId.'"]';

        return match (true) {
            $positionOrId === $this->tableOffset,
            $expression === $this->expression => $this,
            is_int($positionOrId) => match (true) {
                $positionOrId > -1 => new self(
                    '//table',
                    $positionOrId,
                    $this->tableHeader,
                    $this->ignoreTableHeader,
                    $this->tableHeaderSection,
                    $this->tableHeaderOffset,
                    $this->throwOnXmlErrors,
                    $this->includeTableFooter,
                    $this->formatter,
                ),
                default => throw new ParserError('the table offset must be a positive integer or the table id attribute value.'),
            },
            1 === preg_match(",\s,", $positionOrId) => throw new ParserError("The id attribute's value must not contain whitespace (spaces, tabs etc.)"),
            default => new self(
                $expression,
                0,
                $this->tableHeader,
                $this->ignoreTableHeader,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                $this->throwOnXmlErrors,
                $this->includeTableFooter,
                $this->formatter,
            ),
        };
    }

    /**
     * @param array<string> $headerRow
     *
     * @throws ParserError
     */
    public function tableHeader(array $headerRow): self
    {
        return match (true) {
            $headerRow === $this->tableHeader => $this,
            $headerRow !== ($filteredHeader = array_filter($headerRow, is_string(...))) => throw new ParserError('The header record contains non string colum names.'),
            $headerRow !== array_unique($filteredHeader) => throw ParserError::dueToDuplicateHeaderColumnNames($headerRow),
            default => new self(
                $this->expression,
                $this->tableOffset,
                $headerRow,
                $this->ignoreTableHeader,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                $this->throwOnXmlErrors,
                $this->includeTableFooter,
                $this->formatter,
            ),
        };
    }

    public function ignoreTableHeader(): self
    {
        return match ($this->ignoreTableHeader) {
            true => $this,
            false => new self(
                $this->expression,
                $this->tableOffset,
                $this->tableHeader,
                true,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                $this->throwOnXmlErrors,
                $this->includeTableFooter,
                $this->formatter,
            ),
        };
    }

    public function resolveTableHeader(): self
    {
        return match ($this->ignoreTableHeader) {
            false => $this,
            true => new self(
                $this->expression,
                $this->tableOffset,
                $this->tableHeader,
                false,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                $this->throwOnXmlErrors,
                $this->includeTableFooter,
                $this->formatter,
            ),
        };
    }

    /**
     * @param int<0, max> $offset
     */
    public function tableHeaderPosition(Section $section, int $offset = 0): self
    {
        return match (true) {
            $section === $this->tableHeaderSection && $offset === $this->tableHeaderOffset => $this,
            $offset < 0 => throw new ParserError('The table header row offset must be a positive integer or 0.'), /* @phpstan-ignore-line */
            default => new self(
                $this->expression,
                $this->tableOffset,
                $this->tableHeader,
                $this->ignoreTableHeader,
                $section,
                $offset,
                $this->throwOnXmlErrors,
                $this->includeTableFooter,
                $this->formatter,
            ),
        };
    }

    public function includeTableFooter(): self
    {
        return match ($this->includeTableFooter) {
            true => $this,
            false => new self(
                $this->expression,
                $this->tableOffset,
                $this->tableHeader,
                $this->ignoreTableHeader,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                $this->throwOnXmlErrors,
                true,
                $this->formatter,
            ),
        };
    }

    public function excludeTableFooter(): self
    {
        return match ($this->includeTableFooter) {
            false => $this,
            true => new self(
                $this->expression,
                $this->tableOffset,
                $this->tableHeader,
                $this->ignoreTableHeader,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                $this->throwOnXmlErrors,
                false,
                $this->formatter,
            ),
        };
    }

    public function failOnXmlErrors(): self
    {
        return match ($this->throwOnXmlErrors) {
            true => $this,
            false => new self(
                $this->expression,
                $this->tableOffset,
                $this->tableHeader,
                $this->ignoreTableHeader,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                true,
                $this->includeTableFooter,
                $this->formatter,
            ),
        };
    }

    public function ignoreXmlErrors(): self
    {
        return match ($this->throwOnXmlErrors) {
            false => $this,
            true => new self(
                $this->expression,
                $this->tableOffset,
                $this->tableHeader,
                $this->ignoreTableHeader,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                false,
                $this->includeTableFooter,
                $this->formatter,
            ),
        };
    }

    public function withFormatter(Closure $formatter): self
    {
        return new self(
            $this->expression,
            $this->tableOffset,
            $this->tableHeader,
            $this->ignoreTableHeader,
            $this->tableHeaderSection,
            $this->tableHeaderOffset,
            $this->throwOnXmlErrors,
            $this->includeTableFooter,
            $formatter,
        );
    }

    public function withoutFormatter(): self
    {
        return match (null) {
            $this->formatter => $this,
            default => new self(
                $this->expression,
                $this->tableOffset,
                $this->tableHeader,
                $this->ignoreTableHeader,
                $this->tableHeaderSection,
                $this->tableHeaderOffset,
                $this->throwOnXmlErrors,
                $this->includeTableFooter,
                null,
            ),
        };
    }

    /**
     * @param resource|string $filenameOrStream
     * @param resource|null $filenameContext
     *
     * @throws ParserError
     * @throws SyntaxError
     */
    public function parseFile($filenameOrStream, $filenameContext = null): TabularDataReader
    {
        if (is_resource($filenameOrStream)) {
            return $this->parseHTML($this->streamToString($filenameOrStream));
        }

        set_error_handler(fn (int $errno, string $errstr, string $errfile, int $errline) => true);
        $resource = fopen(...match ($filenameContext) {
            null => [$filenameOrStream, 'r'],
            default => [$filenameOrStream, 'r', false, $filenameContext],
        });
        restore_error_handler();

        if (!is_resource($resource)) {
            throw new ParserError('`'.$filenameOrStream.'`: failed to open stream: No such file or directory.');
        }

        $html = $this->streamToString($resource);
        fclose($resource);

        return $this->parseHTML($html);
    }

    /**
     * @param resource $stream
     *
     * @throws ParserError
     */
    private function streamToString($stream): string
    {
        set_error_handler(fn (int $errno, string $errstr, string $errfile, int $errline) => true);
        $html = stream_get_contents($stream);
        restore_error_handler();

        return match (false) {
            $html => throw new ParserError('The resource could not be read.'),
            default => $html,
        };
    }

    /**
     * @throws ParserError
     * @throws SyntaxError
     */
    public function parseHTML(DOMDocument|DOMElement|SimpleXMLElement|Stringable|string $source): TabularDataReader
    {
        $xpath = new DOMXPath($this->sourceToDomDocument($source));
        /** @var DOMNodeList<DOMElement> $query */
        $query = $xpath->query($this->expression);
        $table = $query->item($this->tableOffset);

        return match (true) {
            $table instanceof DOMElement => $this->convert(new DOMXPath($this->sourceToDomDocument($table))),
            default => throw new ParserError('The HTML table could not be found in the submitted html.'),
        };
    }

    /**
     * @throws ParserError
     */
    private function sourceToDomDocument(
        DOMDocument|SimpleXMLElement|DOMElement|Stringable|string $document,
    ): DOMDocument {
        if ($document instanceof DOMDocument) {
            return $document;
        }

        $dom = new DOMDocument();
        if ($document instanceof DOMElement) {
            $dom->appendChild($dom->importNode($document, true));

            return $dom;
        }

        if ($document instanceof SimpleXMLElement) {
            $dom->appendChild($dom->importNode(dom_import_simplexml($document), true));

            return $dom;
        }

        libxml_use_internal_errors(true);
        $dom->loadHTML((string) $document);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        return match (true) {
            $this->throwOnXmlErrors && [] !== $errors => throw ParserError::dueToLibXmlErrors($errors),
            default => $dom,
        };
    }

    /**
     * @throws ParserError
     * @throws SyntaxError
     */
    private function convert(DOMXPath $xpath): TabularDataReader
    {
        $header = match (true) {
            [] !== $this->tableHeader => $this->tableHeader,
            $this->ignoreTableHeader => [],
            default => $this->extractTableHeader($xpath, $this->tableHeaderSection->xpath()),
        };

        return new ResultSet($this->extractTableContents($xpath, $header), $header);
    }

    /**
     * @return array<string>
     */
    private function extractTableHeader(DOMXPath $xpath, string $expression): array
    {
        /** @var DOMNodeList<DOMElement> $query */
        $query = $xpath->query($expression);
        /** @var DOMElement|null $tr */
        $tr = $query->item($this->tableHeaderOffset);

        return match (null) {
            $tr => [],
            default => $this->extractHeaderRow($tr),
        };
    }

    /**
     * @param array<string> $header
     */
    private function extractTableContents(DOMXPath $xpath, array $header): Iterator
    {
        /** @var DOMNodeList<DOMElement> $query */
        $query = $xpath->query('//table');
        /** @var DOMElement $table */
        $table = $query->item(0);
        $it = new ArrayIterator();
        $header =  $this->tableHeader($header)->tableHeader;
        foreach ($table->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            $nodeName = strtolower($childNode->nodeName);
            if ('tbody' === $nodeName || ('tfoot' === $nodeName && $this->includeTableFooter)) {
                foreach ($childNode->childNodes as $tr) {
                    if (null !== ($record = $this->filterRecord($tr))) {
                        $it->append($this->formatRecord($this->extractRecord($record), $header));
                    }
                }
            } elseif (null !== ($record = $this->filterRecord($childNode))) {
                $it->append($this->formatRecord($this->extractRecord($record), $header));
            }
        }

        return $it;
    }

    private function filterRecord(DOMNode $tr): ?DOMElement
    {
        return match (true) {
            !$tr instanceof DOMElement,
            'tr' !== strtolower($tr->nodeName),
            $tr->hasAttribute(self::HEADER_ROW_ATTRIBUTE_NAME) => null,
            default => $tr,
        };
    }

    /**
     * @return array<string>
     */
    private function extractHeaderRow(DOMElement $tr): array
    {
        $headerRow = $this->extractRecord($tr);
        if ([] !== $headerRow) {
            $tr->setAttribute(self::HEADER_ROW_ATTRIBUTE_NAME, 'true');
        }

        return $headerRow;
    }

    /**
     * @return array<string>
     */
    private function extractRecord(DOMElement $tr): array
    {
        $getSpanSize = function (DOMElement $node): int {
            $span = (int) $node->getAttribute('colspan');

            return match (true) {
                2 > $span, 1000 < $span => 1,
                default => $span,
            };
        };

        $row = [];
        foreach ($tr->childNodes as $node) {
            if ($node instanceof DOMElement && in_array(strtolower($node->nodeName), self::CELL_TAGS, true)) {
                $row = [...$row, ...array_fill(0, $getSpanSize($node), $node->nodeValue.'')];
            }
        }

        return $row;
    }

    /**
     * @param array<string> $record
     * @param array<string> $header
     *
     * @return array<string>
     */
    private function formatRecord(array $record, array $header): array
    {
        $cellCount = count($header);
        $record = match ($cellCount) {
            0 => $record,
            count($record) => array_combine($header, $record),
            default => array_combine($header, array_slice(array_pad($record, $cellCount, ''), 0, $cellCount)),
        };

        return match (null) {
            $this->formatter => $record,
            default => ($this->formatter)($record),
        };
    }
}
