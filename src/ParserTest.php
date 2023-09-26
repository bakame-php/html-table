<?php

declare(strict_types=1);

namespace Bakame\HtmlTable;

use DOMDocument;
use DOMElement;
use League\Csv\TabularDataReader;
use LimitIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

final class ParserTest extends TestCase
{
    private const HTML = <<<TABLE
<table class="table-csv-data" id="test">
<thead>
<tr><th scope="col">prenoms</th><th scope="col">nombre</th><th scope="col">sexe</th><th scope="col">annee</th></tr>
</thead>
<tbody>
<tr data-record-offset="4"><td title="prenoms">Abdoulaye</td><td title="nombre">15</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="5"><td title="prenoms">Abel</td><td title="nombre">14</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="6"><td title="prenoms">Abiga</td><td title="nombre">6</td><td title="sexe">F</td><td title="annee">2004</td></tr>
<tr data-record-offset="7"><td title="prenoms">Aboubacar</td><td title="nombre">8</td><td title="sexe">M</td><td title="annee">2004</td></tr>
</tbody>
</table>

<table class="table-csv-data" id="testb">
<tr><th scope="col">prenoms</th><th scope="col">nombre</th><th scope="col">sexe</th><th scope="col">annee</th></tr>
<tr data-record-offset="4"><td title="prenoms">Abdoulaye</td><td title="nombre">15</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="5"><td title="prenoms">Abel</td><td title="nombre">14</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="6"><td title="prenoms">Abiga</td><td title="nombre">6</td><td title="sexe">F</td><td title="annee">2004</td></tr>
<tr data-record-offset="7"><td title="prenoms">Aboubacar</td><td title="nombre">8</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="8"><td title="prenoms">Aboubakar</td><td title="nombre">6</td><td title="sexe">M</td><td title="annee">2004</td></tr>
</table>
TABLE;

    #[Test]
    public function it_will_return_the_same_options(): void
    {
        $parser = Parser::new();

        self::assertSame(
            $parser,
            $parser
                ->tablePosition(0)
                ->tableHeaderPosition(Section::thead, 0)
                ->includeTableFooter()
                ->tableHeader([])
                ->resolveTableHeader()
                ->ignoreXmlErrors()
                ->withoutFormatter()
        );
    }

    #[Test]
    public function it_will_throw_if_the_header_contains_duplicate_values(): void
    {
        $headerRow = ['foo', 'foo', 'toto', 'toto', 'baz'];
        $this->expectException(ParserError::class);
        $this->expectExceptionMessage('The header record contains duplicate column names: `foo`, `toto`.');

        Parser::new()->tableHeader($headerRow);
    }

    #[Test]
    public function it_will_throw_if_the_header_does_not_only_contains_string(): void
    {
        $this->expectException(ParserError::class);

        Parser::new()->tableHeader(['foo', 1]); /* @phpstan-ignore-line */
    }

    #[Test]
    public function it_will_throw_if_the_identifier_is_invalid(): void
    {
        $this->expectException(ParserError::class);

        Parser::new()->tablePosition('foo bar');
    }

    #[Test]
    public function it_will_throw_if_the_identifier_is_a_negative_integer(): void
    {
        $this->expectException(ParserError::class);

        Parser::new()->tablePosition(-1);
    }

    #[Test]
    public function it_will_throw_if_the_table_header_row_offset_is_negative(): void
    {
        $this->expectException(ParserError::class);

        Parser::new()->tableHeaderPosition(Section::thead, -1); /* @phpstan-ignore-line */
    }

    #[Test]
    public function it_can_load_the_first_html_table_found_by_default(): void
    {
        $table = Parser::new()->parseHtml(self::HTML);

        self::assertSame(['prenoms', 'nombre', 'sexe', 'annee'], $table->getHeader());
        self::assertCount(4, $table);
        self::assertSame($this->getNthRecord($table), [
            'prenoms' => 'Abdoulaye',
            'nombre' => '15',
            'sexe' => 'M',
            'annee' => '2004',
        ]);
    }

    #[Test]
    public function it_can_load_the_first_html_table_found_by_default_without_the_header(): void
    {
        $table = Parser::new()->ignoreTableHeader()->parseHtml(self::HTML);

        self::assertSame([], $table->getHeader());
        self::assertCount(4, $table);
        self::assertSame($this->getNthRecord($table), [
            'Abdoulaye',
            '15',
            'M',
            '2004',
        ]);
    }

    #[Test]
    public function it_can_load_any_html_table_by_occurrence(): void
    {
        $table = Parser::new()
            ->tablePosition(1)
            ->parseFile(dirname(__DIR__).'/test_files/table.html');

        self::assertSame([], $table->getHeader());
        self::assertCount(6, $table);
    }

    #[Test]
    public function it_can_load_any_html_table_by_attribute_id(): void
    {
        $table = Parser::new()
            ->tablePosition('testb')
            ->parseFile(dirname(__DIR__).'/test_files/table.html');

        self::assertSame([], $table->getHeader());
        self::assertCount(6, $table);
    }

    #[Test]
    public function it_uses_the_table_first_tr_to_search_for_the_header(): void
    {
        /** @var resource $stream */
        $stream = fopen(dirname(__DIR__).'/test_files/table.html', 'r');
        $table = Parser::new()
            ->tablePosition('testb')
            ->tableHeaderPosition(Section::none)
            ->parseFile($stream);

        self::assertSame(['prenoms', 'nombre', 'sexe', 'annee'], $table->getHeader());
        self::assertCount(5, $table);
        self::assertSame([
            'prenoms' => 'Abdoulaye',
            'nombre' => '15',
            'sexe' => 'M',
            'annee' => '2004',
        ], $this->getNthRecord($table));

        fclose($stream);
    }

    #[Test]
    public function it_will_fail_to_load_a_missing_file(): void
    {
        $this->expectException(ParserError::class);

        Parser::new()->parseFile('/path/tp/my/heart.html');
    }

    #[Test]
    public function it_uses_the_table_first_tr_in_the_first_tbody_to_search_for_the_header(): void
    {
        $html = <<<TABLE
<table class="table-csv-data" id="testb">
<tbody>
<tr><th scope="col">prenoms</th><th scope="col">nombre</th><th scope="col">sexe</th><th scope="col">annee</th></tr>
<tr data-record-offset="4"><td title="prenoms">Abdoulaye</td><td title="nombre">15</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="5"><td title="prenoms">Abel</td><td title="nombre">14</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="6"><td title="prenoms">Abiga</td><td title="nombre">6</td><td title="sexe">F</td><td title="annee">2004</td></tr>
<tr data-record-offset="7"><td title="prenoms">Aboubacar</td><td title="nombre">8</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="8"><td title="prenoms">Aboubakar</td><td title="nombre">6</td><td title="sexe">M</td><td title="annee">2004</td></tr>
</tbody>
</table>
TABLE;

        $table = Parser::new()
            ->tableHeaderPosition(Section::tbody)
            ->parseHtml($html);

        self::assertSame(['prenoms', 'nombre', 'sexe', 'annee'], $table->getHeader());
        self::assertCount(5, $table);
        self::assertSame([
            'prenoms' => 'Abdoulaye',
            'nombre' => '15',
            'sexe' => 'M',
            'annee' => '2004',
        ], $this->getNthRecord($table));
    }

    #[Test]
    public function it_will_throw_if_the_html_is_malformed(): void
    {
        $this->expectExceptionObject(new ParserError('The HTML table could not be found in the submitted html.'));

        Parser::new()->parseHtml('vasdfadadf');
    }

    #[Test]
    public function it_will_throw_if_no_table_is_found(): void
    {
        $this->expectExceptionObject(new ParserError('The HTML table could not be found in the submitted html.'));

        Parser::new()->parseHtml('<ol><li>foo</li></ol>');
    }

    #[Test]
    public function it_will_use_the_submitted_headers(): void
    {
        $parser = Parser::new()
            ->tableHeader(['firstname', 'count', 'gender', 'year']);

        $table = $parser->parseHtml(self::HTML);

        self::assertSame(['firstname', 'count', 'gender', 'year'], $table->getHeader());
        self::assertSame($this->getNthRecord($table), [
            'firstname' => 'Abdoulaye',
            'count' => '15',
            'gender' => 'M',
            'year' => '2004',
        ]);
    }

    #[Test]
    public function it_will_duplicate_colspan_data(): void
    {
        $html = <<<TABLE
<table class="table-csv-data" id="testb">
<tr><th scope="col">prenoms</th><th scope="col">nombre</th><th scope="col">sexe</th><th scope="col">annee</th></tr>
<tr data-record-offset="4"><td title="prenoms" colspan="3">Abdoulaye</td><td title="annee">2004</td></tr>
<tr data-record-offset="5"><td title="prenoms">Abel</td><td title="nombre">14</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="6"><td title="prenoms">Abiga</td><td title="nombre">6</td><td title="sexe">F</td><td title="annee">2004</td></tr>
<tr data-record-offset="7"><td title="prenoms">Aboubacar</td><td title="nombre">8</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="8"><td title="prenoms">Aboubakar</td><td title="nombre">6</td><td title="sexe">M</td><td title="annee">2004</td></tr>
</table>
TABLE;

        $table = Parser::new()->parseHtml($html);

        self::assertSame($this->getNthRecord($table, 1), ['Abdoulaye', 'Abdoulaye', 'Abdoulaye', '2004']);
        self::assertSame($this->getNthRecord($table), ['prenoms', 'nombre', 'sexe', 'annee']);
    }

    #[Test]
    public function it_will_ignore_the_malformed_header_by_deault(): void
    {
        $html = <<<TABLE
<table class="table-csv-data" id="testb">
<thead></thead>
<tr data-record-offset="4"><td title="prenoms" colspan="3">Abdoulaye</td><td title="annee">2004</td></tr>
<tr data-record-offset="5"><td title="prenoms">Abel</td><td title="nombre">14</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="6"><td title="prenoms">Abiga</td><td title="nombre">6</td><td title="sexe">F</td><td title="annee">2004</td></tr>
<tr data-record-offset="7"><td title="prenoms">Aboubacar</td><td title="nombre">8</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="8"><td title="prenoms">Aboubakar</td><td title="nombre">6</td><td title="sexe">M</td><td title="annee">2004</td></tr>
</table>
TABLE;

        $dom = new DOMDocument();
        $dom->loadHTML($html);

        $table = Parser::new()->parseHtml($dom);

        self::assertSame([], $table->getHeader());
        self::assertSame($this->getNthRecord($table), ['Abdoulaye', 'Abdoulaye', 'Abdoulaye', '2004']);
        self::assertSame($this->getNthRecord($table, 1), ['Abel', '14', 'M', '2004']);
    }

    #[Test]
    public function it_will_fails_on_malformed_html(): void
    {
        $html = <<<TABLE
df<body></p>sghfd
TABLE;

        $this->expectException(ParserError::class);

        Parser::new()
            ->failOnXmlErrors()
            ->parseHtml($html);
    }

    #[Test]
    public function it_will_fail_to_load_other_html_tag(): void
    {
        $this->expectException(ParserError::class);

        Parser::new()->parseHtml(new DOMElement('p', 'I know who you are'));
    }

    #[Test]
    public function it_will_found_no_header(): void
    {
        $html = <<<TABLE
<table class="table-csv-data" id="testb">
<thead><tr><th>I</th><th>exists</th></tr></thead>
<tbody><p>yolo</p></tbody>
</table>
TABLE;

        /** @var SimpleXMLElement $simpleXML */
        $simpleXML = simplexml_load_string($html);

        $table = Parser::new()
            ->tableHeaderPosition(Section::tbody)
            ->parseHtml($simpleXML);

        self::assertSame([], $table->getHeader());
    }

    #[Test]
    public function it_will_found_no_header_in_any_section(): void
    {
        $html = <<<TABLE
<table class="table-csv-data" id="testb">
<thead><p>yolo</p></thead>
<tbody><p>yolo</p></tbody>
<tfoot><p>yolo</p></tfoot>
<div></div>
</table>
TABLE;

        $table = Parser::new()
            ->tableHeaderPosition(Section::none)
            ->parseHtml($html);

        self::assertSame([], $table->getHeader());
    }


    #[Test]
    public function it_will_use_the_table_footer(): void
    {
        $html = <<<TABLE
<table class="table-csv-data" id="testb">
<tfoot>
<tr data-record-offset="4"><td title="prenoms" colspan="3">Abdoulaye</td><td title="annee">2004</td></tr>
<tr data-record-offset="5"><td title="prenoms">Abel</td><td title="nombre">14</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="6"><td title="prenoms">Abiga</td><td title="nombre">6</td><td title="sexe">F</td><td title="annee">2004</td></tr>
<tr data-record-offset="7"><td title="prenoms">Aboubacar</td><td title="nombre">8</td><td title="sexe">M</td><td title="annee">2004</td></tr>
<tr data-record-offset="8"><td title="prenoms">Aboubakar</td><td title="nombre">6</td><td title="sexe">M</td><td title="annee">2004</td></tr>
</tfoot>
</table>
TABLE;

        $table = Parser::new()
            ->excludeTableFooter()
            ->parseHtml($html);

        self::assertSame([], $table->getHeader());
        self::assertSame([], $this->getNthRecord($table));
    }

    #[Test]
    public function it_uses_the_parser_formatter(): void
    {
        /** @var resource $stream */
        $stream = fopen(dirname(__DIR__).'/test_files/table.html', 'r');
        $table = Parser::new()
            ->tablePosition('testb')
            ->tableHeaderPosition(Section::none)
            ->withFormatter(function (array $record): array {
                $record = array_map(strtoupper(...), $record);
                $record['nombre'] = (int) $record['nombre'];
                $record['annee'] = (int) $record['annee'];

                return $record;
            })
            ->parseFile($stream);

        self::assertSame(['prenoms', 'nombre', 'sexe', 'annee'], $table->getHeader());
        self::assertCount(5, $table);
        self::assertSame([
            'prenoms' => 'ABDOULAYE',
            'nombre' => 15,
            'sexe' => 'M',
            'annee' => 2004,
        ], $this->getNthRecord($table));

        fclose($stream);
    }

    #[Test]
    public function it_can_handle_rowspan_and_colspan(): void
    {
        $table = <<<TABLE
<table>
    <thead>
        <tr>
            <th>Col 1</th>
            <th>Col 2</th>
            <th>Col 3</th>
            <th>Col 4</th>
            <th>Col 5</th>
        </tr>
    </thead>
    <tbody>
    <tr>
        <th>Col 1</th>
        <th colspan="2">colspan</th>
        <th>Col 4</th>
        <th>Col 5</th>
    </tr>
    <tr>
        <th>Col 1</th>
        <th>Col 2</th>
        <th colspan="3" rowspan="2">colspan+rowspan</th>
    </tr>
    <tr>
        <th>Col 1</th>
        <th>Col 2</th>
    </tr>
    <tr>
        <th>Col 1</th>
        <th rowspan="2">rowspan</th>
        <th>Col 3</th>
        <th>Col 4</th>
        <th>Col 5</th>
    </tr>
    <tr>
        <th>Col 1</th>
        <th>Col 3</th>
        <th>Col 4</th>
        <th>Col 5</th>
    </tr>
    </tbody>
</table>
TABLE;

        $reducer = fn (TabularDataReader $reader, string $value): int => array_reduce([...$reader], fn (int $carry, array $record): int => $carry + (array_count_values($record)[$value] ?? 0), 0);
        $table = Parser::new()->parseHtml($table);

        self::assertSame(2, $reducer($table, 'colspan'));
        self::assertSame(2, $reducer($table, 'rowspan'));
        self::assertSame(6, $reducer($table, 'colspan+rowspan'));
    }

    /** @return array<string> */
    private function getNthRecord(TabularDataReader $reader, int $offset = 0): array
    {
        $iterator = new LimitIterator($reader->getRecords(), $offset, 1);
        $iterator->rewind();

        /** @var array<string>|null $result */
        $result = $iterator->current();

        return $result ?? [];
    }
}
