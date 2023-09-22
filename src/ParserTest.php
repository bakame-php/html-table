<?php

declare(strict_types=1);

namespace Bakame\HtmlTable;

use DOMDocument;
use DOMElement;
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
                ->tableHeaderPosition(Section::Header, 0)
                ->includeTableFooter()
                ->tableHeader([])
                ->resolveTableHeader()
                ->ignoreXmlErrors()
        );
    }

    #[Test]
    public function it_will_throw_if_the_header_contains_duplicate_values(): void
    {
        $headerRow = ['foo', 'foo', 'toto', 'toto', 'baz'];
        $this->expectException(Error::class);
        $this->expectExceptionMessage('The header record contains duplicate column names: `foo`, `toto`.');

        Parser::new()->tableHeader($headerRow);
    }

    #[Test]
    public function it_will_throw_if_the_header_does_not_only_contains_string(): void
    {
        $this->expectException(Error::class);

        Parser::new()->tableHeader(['foo', 1]); /* @phpstan-ignore-line */
    }

    #[Test]
    public function it_will_throw_if_the_identifier_is_invalid(): void
    {
        $this->expectException(Error::class);

        Parser::new()->tablePosition('foo bar');
    }

    #[Test]
    public function it_will_throw_if_the_identifier_is_a_negative_integer(): void
    {
        $this->expectException(Error::class);

        Parser::new()->tablePosition(-1);
    }

    #[Test]
    public function it_will_throw_if_the_table_header_row_offset_is_negative(): void
    {
        $this->expectException(Error::class);

        Parser::new()->tableHeaderPosition(Section::Header, -1); /* @phpstan-ignore-line */
    }

    #[Test]
    public function it_can_load_the_first_html_table_found_by_default(): void
    {
        $table = Parser::new()->parseHTML(self::HTML);

        self::assertSame(['prenoms', 'nombre', 'sexe', 'annee'], $table->getHeader());
        self::assertCount(4, $table);
        self::assertSame($table->first(), [
            'prenoms' => 'Abdoulaye',
            'nombre' => '15',
            'sexe' => 'M',
            'annee' => '2004',
        ]);
    }

    #[Test]
    public function it_can_load_the_first_html_table_found_by_default_without_the_header(): void
    {
        $table = Parser::new()->ignoreTableHeader()->parseHTML(self::HTML);

        self::assertSame([], $table->getHeader());
        self::assertCount(4, $table);
        self::assertSame($table->first(), [
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
            ->tableHeaderPosition(Section::None)
            ->parseFile($stream);

        self::assertSame(['prenoms', 'nombre', 'sexe', 'annee'], $table->getHeader());
        self::assertCount(5, $table);
        self::assertSame([
            'prenoms' => 'Abdoulaye',
            'nombre' => '15',
            'sexe' => 'M',
            'annee' => '2004',
        ], $table->first());

        fclose($stream);
    }

    #[Test]
    public function it_will_fail_to_load_a_missing_file(): void
    {
        $this->expectException(Error::class);

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
            ->tableHeaderPosition(Section::Body)
            ->parseHTML($html);

        self::assertSame(['prenoms', 'nombre', 'sexe', 'annee'], $table->getHeader());
        self::assertCount(5, $table);
        self::assertSame([
            'prenoms' => 'Abdoulaye',
            'nombre' => '15',
            'sexe' => 'M',
            'annee' => '2004',
        ], $table->first());
    }

    #[Test]
    public function it_will_throw_if_the_html_is_malformed(): void
    {
        $this->expectExceptionObject(new Error('The HTML table could not be found in the submitted html.'));

        Parser::new()->parseHTML('vasdfadadf');
    }

    #[Test]
    public function it_will_throw_if_no_table_is_found(): void
    {
        $this->expectExceptionObject(new Error('The HTML table could not be found in the submitted html.'));

        Parser::new()->parseHTML('<ol><li>foo</li></ol>');
    }

    #[Test]
    public function it_will_use_the_submitted_headers(): void
    {
        $parser = Parser::new()
            ->tableHeader(['firstname', 'count', 'gender', 'year']);

        $table = $parser->parseHTML(self::HTML);

        self::assertSame(['firstname', 'count', 'gender', 'year'], $table->getHeader());
        self::assertSame($table->first(), [
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

        $table = Parser::new()->parseHTML($html);

        self::assertSame($table->nth(1), ['Abdoulaye', 'Abdoulaye', 'Abdoulaye', '2004']);
        self::assertSame($table->nth(0), ['prenoms', 'nombre', 'sexe', 'annee']);
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

        $table = Parser::new()->parseHTML($dom);

        self::assertSame([], $table->getHeader());
        self::assertSame($table->nth(0), ['Abdoulaye', 'Abdoulaye', 'Abdoulaye', '2004']);
        self::assertSame($table->nth(1), ['Abel', '14', 'M', '2004']);
    }

    #[Test]
    public function it_will_fails_on_malformed_html(): void
    {
        $html = <<<TABLE
df<body></p>sghfd
TABLE;

        $this->expectException(Error::class);

        Parser::new()
            ->failOnXmlErrors()
            ->parseHTML($html);
    }

    #[Test]
    public function it_will_fail_to_load_other_html_tag(): void
    {
        $this->expectException(Error::class);

        Parser::new()->parseHTML(new DOMElement('p', 'I know who you are'));
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
            ->tableHeaderPosition(Section::Body)
            ->parseHTML($simpleXML);

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
            ->tableHeaderPosition(Section::None)
            ->parseHTML($html);

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
            ->parseHTML($html);

        self::assertSame([], $table->getHeader());
        self::assertSame([], $table->first());
    }
}