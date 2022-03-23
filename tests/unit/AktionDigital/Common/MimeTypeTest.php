<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\MimeType;

class MimeTypeTest extends BaseTestCase {

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
     * @covers Colibri\Common\MimeType::GetType
     **/
    public function testMimeTypeGetType() {
        // code test functionality here

        $this->assertEquals('gif', MimeType::GetType('image/gif'));
    
    }

    /**
     * @covers Colibri\Common\MimeType::Create
     **/
    public function testMimeTypeCreate() {
        // code test functionality here

        $mime = MimeType::Create('application.gif');
        $this->assertEquals('gif', $mime->type);

        $mime = MimeType::Create('application.gif.jpeg');
        $this->assertEquals('jpeg', $mime->type);

    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyData
     **/
    public function testMimeTypeGetPropertyData() {
        // проверяем getPropertyData

        $mime = new MimeType('gif');
        $this->assertEquals('image/gif', $mime->data);

        $mime = new MimeType('jpeg');
        $this->assertEquals('image/jpeg', $mime->data);

        $mime = new MimeType('jpg');
        $this->assertEquals('image/jpeg', $mime->data);

    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyIsCapable
     **/
    public function testMimeTypeGetPropertyIsCapable() {
        // проверяем getPropertyIsCapable
        // "jpg", "png", "gif",
        // "swf",
        // "html", "htm",
        // "css", "js",
        // "xml", "xsl"
        $mime = new MimeType('gif');
        $this->assertTrue($mime->isCapable);

        $mime = new MimeType('jpeg');
        $this->assertFalse($mime->isCapable);

        $mime = new MimeType('jpg');
        $this->assertTrue($mime->isCapable);

        $mime = new MimeType('png');
        $this->assertTrue($mime->isCapable);

        $mime = new MimeType('swf');
        $this->assertTrue($mime->isCapable);

        $mime = new MimeType('html');
        $this->assertTrue($mime->isCapable);

        $mime = new MimeType('htm');
        $this->assertTrue($mime->isCapable);

        $mime = new MimeType('css');
        $this->assertTrue($mime->isCapable);

        $mime = new MimeType('js');
        $this->assertTrue($mime->isCapable);

        $mime = new MimeType('flv');
        $this->assertFalse($mime->isCapable);

        $mime = new MimeType('htm');
        $this->assertTrue($mime->isCapable);
    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyIsValid
     **/
    public function testMimeTypeGetPropertyIsValid() {
        // проверяем getPropertyIsValid
        // "gif", "jpeg", "jpg", "png", "bmp", "dib"
        $mime = new MimeType('gif');
        $this->assertTrue($mime->isValid);

        $mime = new MimeType('jpeg');
        $this->assertTrue($mime->isValid);

        $mime = new MimeType('jpg');
        $this->assertTrue($mime->isValid);

        $mime = new MimeType('png');
        $this->assertTrue($mime->isValid);

        $mime = new MimeType('bmp');
        $this->assertTrue($mime->isValid);

        $mime = new MimeType('dib');
        $this->assertFalse($mime->isValid);

        $mime = new MimeType('adsfasdfasdf');
        $this->assertFalse($mime->isValid);
    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyIsImage
     **/
    public function testMimeTypeGetPropertyIsImage() {
        // проверяем getPropertyIsImage
        // "gif", "jpeg", "jpg", "png", "bmp", "dib"
        $mime = new MimeType('gif');
        $this->assertTrue($mime->isImage);

        $mime = new MimeType('jpeg');
        $this->assertTrue($mime->isImage);

        $mime = new MimeType('jpg');
        $this->assertTrue($mime->isImage);

        $mime = new MimeType('png');
        $this->assertTrue($mime->isImage);

        $mime = new MimeType('bmp');
        $this->assertTrue($mime->isImage);

        $mime = new MimeType('dib');
        $this->assertTrue($mime->isImage);

        $mime = new MimeType('pdf');
        $this->assertFalse($mime->isImage);
    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyIsAudio
     **/
    public function testMimeTypeGetPropertyIsAudio() {
        // проверяем getPropertyIsAudio
        // "mid", "mp3", "au"
        $mime = new MimeType('mid');
        $this->assertTrue($mime->isAudio);

        $mime = new MimeType('mp3');
        $this->assertTrue($mime->isAudio);

        $mime = new MimeType('au');
        $this->assertTrue($mime->isAudio);

        $mime = new MimeType('pdf');
        $this->assertFalse($mime->isAudio);
    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyIsVideo
     **/
    public function testMimeTypeGetPropertyIsVideo() {

        // проверяем getPropertyIsVideo
        // "wmv", "mpg", "mp4", "m4v", "avi"
        $mime = new MimeType('wmv');
        $this->assertTrue($mime->isVideo);

        $mime = new MimeType('mpg');
        $this->assertTrue($mime->isVideo);

        $mime = new MimeType('mp4');
        $this->assertTrue($mime->isVideo);

        $mime = new MimeType('m4v');
        $this->assertTrue($mime->isVideo);

        $mime = new MimeType('avi');
        $this->assertTrue($mime->isVideo);

        $mime = new MimeType('pdf');
        $this->assertFalse($mime->isVideo);    
    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyIsViewable
     **/
    public function testMimeTypeGetPropertyIsViewable() {
        // проверяем getPropertyIsViewable
        // "gif", "jpg", "jpeg", "png", "swf"
        $mime = new MimeType('gif');
        $this->assertTrue($mime->isViewable);

        $mime = new MimeType('jpg');
        $this->assertTrue($mime->isViewable);

        $mime = new MimeType('jpeg');
        $this->assertTrue($mime->isViewable);

        $mime = new MimeType('png');
        $this->assertTrue($mime->isViewable);

        $mime = new MimeType('swf');
        $this->assertTrue($mime->isViewable);

        $mime = new MimeType('mp4');
        $this->assertFalse($mime->isViewable);    
    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyIsFlashVideo
     **/
    public function testMimeTypeGetPropertyIsFlashVideo() {
        
        // проверяем getPropertyIsFlashVideo
        $mime = new MimeType('flv');
        $this->assertTrue($mime->isFlashVideo);

        $mime = new MimeType('mp4');
        $this->assertFalse($mime->isFlashVideo);       
    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyIsFlash
     **/
    public function testMimeTypeGetPropertyIsFlash() {

        // проверяем getPropertyIsFlash
        $mime = new MimeType('swf');
        $this->assertTrue($mime->isFlash);

        $mime = new MimeType('mp4');
        $this->assertFalse($mime->isFlash);
        

    }

    /**
     * @covers Colibri\Common\MimeType::getPropertyType
     **/
    public function testMimeTypeGetPropertyType() {

        // проверяем getPropertyType
        $mime = new MimeType('zip');
        $this->assertEquals('zip', $mime->type);

    }
}
