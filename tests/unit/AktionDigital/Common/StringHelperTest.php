<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\StringHelper;

class StringHelperTest extends BaseTestCase {

    /**
     * This code will run before each test executes
     * @return void
     */
    protected function setUp(): void {

    }

    /**
     * This code will run after each test executes
     * @return void
     */
    protected function tearDown(): void {

    }

    /**
     * @covers Colibri\Common\StringHelper::ToLower
     **/
    public function testStringHelperToLower() {
        // code test functionality here
        $test1 = 'Vahan';
        $test2 = 'Ваган';
        $test3 = 'Ваган Grigoryan';

        $this->assertEquals('vahan', StringHelper::ToLower($test1));
        $this->assertEquals('ваган', StringHelper::ToLower($test2));
        $this->assertEquals('ваган grigoryan', StringHelper::ToLower($test3));

    }

    /**
     * @covers Colibri\Common\StringHelper::ToUpper
     **/
    public function testStringHelperToUpper() {
        $test1 = 'Vahan';
        $test2 = 'Ваган';
        $test3 = 'Ваган Grigoryan';

        $this->assertEquals('VAHAN', StringHelper::ToUpper($test1));
        $this->assertEquals('ВАГАН', StringHelper::ToUpper($test2));
        $this->assertEquals('ВАГАН GRIGORYAN', StringHelper::ToUpper($test3));
    }

    /**
     * @covers Colibri\Common\StringHelper::IsUpper
     **/
    public function testStringHelperIsUpper() {
        // code test functionality here
        $test1 = 'Vahan';
        $test2 = 'Ваган';
        $test3 = 'Ваган Grigoryan';
        $test4 = 'VAHAN';
        $test5 = 'ВАГАН';
        $test6 = 'ВАГАН GRIGORYAN';


        $this->assertFalse(StringHelper::IsUpper($test1));
        $this->assertFalse(StringHelper::IsUpper($test2));
        $this->assertFalse(StringHelper::IsUpper($test3));

        $this->assertTrue(StringHelper::IsUpper($test4));
        $this->assertTrue(StringHelper::IsUpper($test5));
        $this->assertTrue(StringHelper::IsUpper($test6));

        $this->assertFalse(StringHelper::IsUpper(1));
        $this->assertFalse(StringHelper::IsUpper(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::IsLower
     **/
    public function testStringHelperIsLower() {
        // code test functionality here
        $test1 = 'vahan';
        $test2 = 'ваган';
        $test3 = 'ваган grigoryan';
        $test4 = 'VAHAN';
        $test5 = 'ВАГАН';
        $test6 = 'ВАГАН GRIGORYAN';


        $this->assertTrue(StringHelper::IsLower($test1));
        $this->assertTrue(StringHelper::IsLower($test2));
        $this->assertTrue(StringHelper::IsLower($test3));

        $this->assertFalse(StringHelper::IsLower($test4));
        $this->assertFalse(StringHelper::IsLower($test5));
        $this->assertFalse(StringHelper::IsLower($test6));

        $this->assertFalse(StringHelper::IsLower(1));
        $this->assertFalse(StringHelper::IsLower(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::ToUpperFirst
     **/
    public function testStringHelperToUpperFirst() {
        // code test functionality here
        $test1 = 'vahan';
        $test2 = 'ваган';
        $test3 = 'ваган grigoryan';


        $this->assertEquals('Vahan', StringHelper::ToUpperFirst($test1));
        $this->assertEquals('Ваган', StringHelper::ToUpperFirst($test2));
        $this->assertEquals('Ваган grigoryan', StringHelper::ToUpperFirst($test3));

        $this->assertFalse(StringHelper::ToUpperFirst(1));
        $this->assertFalse(StringHelper::ToUpperFirst(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::ToCamelCaseAttr
     **/
    public function testStringHelperToCamelCaseAttr() {

        $test1 = 'vahan-petrosovich-grigoryan';
        $test2 = 'ваган';
        $test3 = 'ваган-grigoryan';


        $this->assertEquals('vahanPetrosovichGrigoryan', StringHelper::ToCamelCaseAttr($test1, false));
        $this->assertEquals('VahanPetrosovichGrigoryan', StringHelper::ToCamelCaseAttr($test1, true));
        $this->assertEquals('ваган', StringHelper::ToCamelCaseAttr($test2));
        $this->assertEquals('ваганGrigoryan', StringHelper::ToCamelCaseAttr($test3));

        $this->assertFalse(StringHelper::ToCamelCaseAttr(1));
        $this->assertFalse(StringHelper::ToCamelCaseAttr(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::FromCamelCaseAttr
     **/
    public function testStringHelperFromCamelCaseAttr() {

        $test1 = 'VahanPetrosovichGrigoryan';
        $test2 = 'ваган';
        $test3 = 'ВаганGrigoryan';


        $this->assertEquals('vahan-petrosovich-grigoryan', StringHelper::FromCamelCaseAttr($test1));
        $this->assertEquals('ваган', StringHelper::FromCamelCaseAttr($test2));
        $this->assertEquals('Ваган-grigoryan', StringHelper::FromCamelCaseAttr($test3));

        $this->assertFalse(StringHelper::FromCamelCaseAttr(1));
        $this->assertFalse(StringHelper::FromCamelCaseAttr(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::ToCamelCaseVar
     **/
    public function testStringHelperToCamelCaseVar() {

        $test1 = 'vahan_petrosovich_grigoryan';
        $test2 = 'ваган';
        $test3 = 'Ваган_grigoryan';


        $this->assertEquals('VahanPetrosovichGrigoryan', StringHelper::ToCamelCaseVar($test1, true));
        $this->assertEquals('vahanPetrosovichGrigoryan', StringHelper::ToCamelCaseVar($test1, false));
        $this->assertEquals('ваган', StringHelper::ToCamelCaseVar($test2));
        $this->assertEquals('ВаганGrigoryan', StringHelper::ToCamelCaseVar($test3, false));

        $this->assertFalse(StringHelper::ToCamelCaseVar(1));
        $this->assertFalse(StringHelper::ToCamelCaseVar(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::FromCamelCaseVar
     **/
    public function testStringHelperFromCamelCaseVar() {
        
        $test1 = 'VahanPetrosovichGrigoryan';
        $test2 = 'ваган';
        $test3 = 'ВаганGrigoryan';


        $this->assertEquals('vahan_petrosovich_grigoryan', StringHelper::FromCamelCaseVar($test1));
        $this->assertEquals('ваган', StringHelper::FromCamelCaseVar($test2));
        $this->assertEquals('Ваган_grigoryan', StringHelper::FromCamelCaseVar($test3, false));

        $this->assertFalse(StringHelper::FromCamelCaseVar(1));
        $this->assertFalse(StringHelper::FromCamelCaseVar(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::IsEmail
     **/
    public function testStringHelperIsEmail() {
        // code test functionality here
        $test1 = 'VahanPetrosovichGrigoryan@test.com';
        $test2 = 'ваган@test.com';
        $test3 = 'Grigo ryan@test.com';


        $this->assertTrue(StringHelper::IsEmail($test1));
        $this->assertFalse(StringHelper::IsEmail($test2));
        $this->assertFalse(StringHelper::IsEmail($test3));

        $this->assertFalse(StringHelper::IsEmail(1));
        $this->assertFalse(StringHelper::IsEmail(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::IsUrl
     **/
    public function testStringHelperIsUrl() {
        // code test functionality here
        $test1 = 'http://VahanPetrosovichGrigoryan.test.com';
        $test2 = 'ftp://test.com/adfasdfasdf/adsfasdf/asdfasdfasdf/';
        $test3 = 'https://asdfasdfasdf.com/asdfasdfasdf';
        $test4 = 'https:// asdfasdfadsf.com';


        $this->assertTrue(StringHelper::IsUrl($test1));
        $this->assertTrue(StringHelper::IsUrl($test2));
        $this->assertTrue(StringHelper::IsUrl($test3));

        $this->assertFalse(StringHelper::IsUrl($test4));

        $this->assertFalse(StringHelper::IsUrl(1));
        $this->assertFalse(StringHelper::IsUrl(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::EndsWith
     **/
    public function testStringHelperEndsWith() {
        // code test functionality here
        $test1 = 'http://VahanPetrosovichGrigoryan.test.com';
        $test2 = 'ftp://test.com/adfasdfasdf/adsfasdf/asdfasdfasdf/';
        $test3 = 'https://asdfasdfasdf.com/asdfasdfasdf';
        $test4 = 'https:// asdfasdfadsf.com';


        $this->assertTrue(StringHelper::EndsWith($test1, 'test.com'));
        $this->assertTrue(StringHelper::EndsWith($test2, 'sdf/'));
        $this->assertTrue(StringHelper::EndsWith($test3, 'asdf'));

        $this->assertFalse(StringHelper::EndsWith($test4, 'com2'));

        $this->assertFalse(StringHelper::EndsWith(1, '123'));
        $this->assertFalse(StringHelper::EndsWith(null, '123'));
    }

    /**
     * @covers Colibri\Common\StringHelper::StartsWith
     **/
    public function testStringHelperStartsWith() {
        // code test functionality here
        $test1 = 'http://VahanPetrosovichGrigoryan.test.com';
        $test2 = 'ftp://test.com/adfasdfasdf/adsfasdf/asdfasdfasdf/';
        $test3 = 'https://asdfasdfasdf.com/asdfasdfasdf';
        $test4 = 'https:// asdfasdfadsf.com';


        $this->assertTrue(StringHelper::StartsWith($test1, 'http'));
        $this->assertTrue(StringHelper::StartsWith($test2, 'ftp://'));
        $this->assertTrue(StringHelper::StartsWith($test3, 'https://'));

        $this->assertFalse(StringHelper::StartsWith($test4, 'bebebe'));

        $this->assertFalse(StringHelper::StartsWith(1, '123'));
        $this->assertFalse(StringHelper::StartsWith(null, '123'));
    }

    /**
     * @covers Colibri\Common\StringHelper::UrlToNamespace
     **/
    public function testStringHelperUrlToNamespace() {
        $test1 = '/test-brbrbr/brbrbr-test/';
        $test2 = 'lllll/bbbb/dddd';
        $test3 = 'asdfasdfasdf';
        $test4 = 'Jljhalskjdhas dALkjhlkjhkljh';


        $this->assertEquals('TestBrbrbr\BrbrbrTest', StringHelper::UrlToNamespace($test1));
        $this->assertEquals('Lllll\Bbbb\Dddd', StringHelper::UrlToNamespace($test2));
        $this->assertEquals('Asdfasdfasdf', StringHelper::UrlToNamespace($test3));
        $this->assertEquals('Jljhalskjdhas dALkjhlkjhkljh', StringHelper::UrlToNamespace($test4));

        $this->assertFalse(StringHelper::UrlToNamespace(1));
        $this->assertFalse(StringHelper::UrlToNamespace(null));
    }

    /**
     * @covers Colibri\Common\StringHelper::AddToQueryString
     **/
    public function testStringHelperAddToQueryString() {
        // code test functionality here
        $test1 = '/test-brbrbr/brbrbr-test/';
        $test2 = 'lllll/bbbb/dddd?b=2';
        $test3 = 'asdfasdfasdf';
        $test4 = 'Jljhalskjdhas dALkjhlkjhkljh';

        $this->assertEquals('/test-brbrbr/brbrbr-test/?b=1', StringHelper::AddToQueryString($test1, ['b' => '1']));
        $this->assertEquals('lllll/bbbb/dddd?b=1', StringHelper::AddToQueryString($test2, ['b' => 1]));
        $this->assertEquals('asdfasdfasdf?b=1', StringHelper::AddToQueryString($test3, ['b' => 1]));
        $this->assertEquals('Jljhalskjdhas dALkjhlkjhkljh?b=1', StringHelper::AddToQueryString($test4, ['b' => 1]));

        $this->assertFalse(StringHelper::AddToQueryString(1, ['b' => 1]));
        $this->assertFalse(StringHelper::AddToQueryString(null, ['b' => 1]));
    }

    /**
     * @covers Colibri\Common\StringHelper::Randomize
     **/
    public function testStringHelperRandomize() {

        $res = StringHelper::Randomize(20);
        $this->assertEquals(20, strlen($res));

    }

    /**
     * @covers Colibri\Common\StringHelper::PrepareAttribute
     **/
    public function testStringHelperPrepareAttribute() {
        // code test functionality here
        $res = StringHelper::PrepareAttribute('задсфасдфасдф&;"\'');
        $this->assertEquals('задсфасдфасдф&amp;;&quot;\'', $res);

        $res = StringHelper::PrepareAttribute('задсфасдфасдф&;"\'', true);
        $this->assertEquals('задсфасдфасдф&amp;;&quot;&amp;rsquo;', $res);
    }

    /**
     * @covers Colibri\Common\StringHelper::Unescape
     **/
    public function testStringHelperUnescape() {
        // code test functionality here
        $res = StringHelper::Unescape(urldecode('задсфасдфасдф&;"\'=&'));
        $this->assertEquals('задсфасдфасдф&;"\'=&', $res);
    }

    /**
     * @covers Colibri\Common\StringHelper::StripHTML
     **/
    public function testStringHelperStripHTML() {
        // code test functionality here
        $res = StringHelper::StripHTML('<html>adfa sdf<div class="23">adsf asdfasd<span style="adsfasdf;asdfasdf;adfsasdfasd;"></span>fad</div></html>');
        $this->assertEquals('adfa sdfadsf asdfasdfad', $res);
    }

    /**
     * @covers Colibri\Common\StringHelper::Substring
     **/
    public function testStringHelperSubstring() {
        // code test functionality here
        $res = StringHelper::Substring('<html>adfa sdf<div class="23">adsf asdfasd<span style="adsfasdf;asdfasdf;adfsasdfasd;"></span>fad</div></html>', 0, 10);
        $this->assertEquals('<html>adfa', $res);
    }

    /**
     * @covers Colibri\Common\StringHelper::Length
     **/
    public function testStringHelperLength() {
        // code test functionality here
        $res = StringHelper::Length('<html>adfa sdf<div class="23">adsf asdfasd<span style="adsfasdf;asdfasdf;adfsasdfasd;"></span>fad</div></html>', 0, 10);
        $this->assertEquals(110, $res);
    }

    /**
     * @covers Colibri\Common\StringHelper::FormatSequence
     **/
    public function testStringHelperFormatSequence() {
        // code test functionality here
        $res = StringHelper::FormatSequence(1024);
        $this->assertEquals('года', $res);

        $res = StringHelper::FormatSequence(1024, ['t1', 't2', 't3'], true);
        $this->assertEquals('1024 t2', $res);

        $res = StringHelper::FormatSequence(10, ['t1', 't2', 't3'], true);
        $this->assertEquals('10 t3', $res);

        $res = StringHelper::FormatSequence(12, ['t1', 't2', 't3'], true);
        $this->assertEquals('12 t3', $res);

    }

    /**
     * @covers Colibri\Common\StringHelper::FormatFileSize
     **/
    public function testStringHelperFormatFileSize() {
        $res = StringHelper::FormatFileSize(1024);
        $this->assertEquals('1024 bytes', $res);

        $res = StringHelper::FormatFileSize(1500);
        $this->assertEquals('1.46 Kb', $res);
        $res = StringHelper::FormatFileSize(5000);
        $this->assertEquals('4.88 Kb', $res);
        $res = StringHelper::FormatFileSize(100000000000000);
        $this->assertEquals('90.95 Tb', $res);

        $res = StringHelper::FormatFileSize(null);
        $this->assertEquals('0 bytes', $res);

        $res = StringHelper::FormatFileSize('adsfasdfasdef');
        $this->assertEquals('0 bytes', $res);
    }

    /**
     * @covers Colibri\Common\StringHelper::TrimLength
     **/
    public function testStringHelperTrimLength() {
        // code test functionality here
        $res = StringHelper::TrimLength('асддфасдфасдфасдф', 2, '...');
        $this->assertEquals('асддфасдфасдфасд...', $res);

        $res = StringHelper::TrimLength('асддфасдфасдфасдф', 10, '...');
        $this->assertEquals('асддфас...', $res);

        $res = StringHelper::TrimLength(1, 'adfasdfasdf', null);
        $this->assertFalse($res);
    }

    /**
     * @covers Colibri\Common\StringHelper::Words
     **/
    public function testStringHelperWords() {
        // code test functionality here
        $res = StringHelper::Words('asjdkhlkjashdflkja, . ljashdf lkjahsdlfkjahs dlfkjha sdflkjha sdlfkjhas ldfkjha lsdfkjh', 2, '...');
        $this->assertEquals('asjdkhlkjashdflkja, ...', $res);

        $res = StringHelper::Words(1, 'asjdkhlkjashdflkja, . ljashdf lkjahsdlfkjahs dlfkjha sdflkjha sdlfkjhas ldfkjha lsdfkjh', null);
        $this->assertEquals(1, $res);
    }

    /**
     * @covers Colibri\Common\StringHelper::Expand
     **/
    public function testStringHelperExpand() {
        $res = StringHelper::Expand(1999999, 20, '0');
        $this->assertEquals('00000000000001999999', $res);

        $res = StringHelper::Expand('asdfasdfasdf', null, '0');
        $this->assertEquals('asdfasdfasdf', $res);
    }

    /**
     * @covers Colibri\Common\StringHelper::GUID
     **/
    public function testStringHelperGUID() {
        // code test functionality here
        $res = StringHelper::GUID();
        $this->assertEquals(36, strlen($res));
    }

    /**
     * @covers Colibri\Common\StringHelper::Explode
     **/
    public function testStringHelperExplode() {
        // code test functionality here
        // code test functionality here
        $res = StringHelper::Explode('lkjhlkjhlkjh,ksjhdfjsdfhksdjf.ksjdhfksjdfksjhdf;jkhskjdhfksjdhf;sdfsdf', [';', '.', ',']);
        $this->assertEquals(5, count($res));
        $res = StringHelper::Explode('lkjhlkjhlkjh,ksjhdfjsdfhksdjf.ksjdhfksjdfksjhdf;jkhskjdhfksjdhf;sdfsdf', [';', '.', ','], true);
        $this->assertEquals(9, count($res));
    }

    /**
     * @covers Colibri\Common\StringHelper::Implode
     **/
    public function testStringHelperImplode() {
        // code test functionality here
        $res = StringHelper::Implode(['1', '2', '3'], '=');
        $this->assertEquals('1=2=3', $res);
        $res = StringHelper::Implode('adsfasdfa', '=');
        $this->assertFalse($res);
    }
}
