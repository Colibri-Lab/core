<?php

namespace Colibri\Utils\Minifiers;

use PHPUnit\Framework\TestCase;

class JavascriptTest extends TestCase
{
    public function testMinify()
    {
        $jsContent = 'function test() { console.log("test"); }';
        $expectedMinifiedContent = 'function test() { _c_("test"); }';

        $minifiedContent = Javascript::Minify($jsContent);

        $this->assertEquals($expectedMinifiedContent, $minifiedContent);
    }

    public function testRunWithUglify()
    {
        // Mock configuration for uglify
        App::$config = (object)[
            'minifier' => (object)[
                'type' => 'uglify',
                'command' => 'uglifyjs %s -o %s'
            ],
            'runtime' => '/tmp/'
        ];

        $jsContent = 'function test() { console.log("test"); }';
        $minifiedContent = (new Javascript())->Run($jsContent);

        $this->assertNotEmpty($minifiedContent);
    }

    public function testRunWithWebpack()
    {
        // Mock configuration for webpack
        App::$config = (object)[
            'minifier' => (object)[
                'type' => 'webpack',
                'command' => 'webpack --entry %s --output-path %s --output-filename %s'
            ],
            'runtime' => '/tmp/'
        ];

        $jsContent = 'function test() { console.log("test"); }';
        $minifiedContent = (new Javascript())->Run($jsContent);

        $this->assertNotEmpty($minifiedContent);
    }

    public function testRunWithShrink()
    {
        // Mock configuration for shrink
        App::$config = (object)[
            'minifier' => (object)[
                'type' => 'shrink'
            ]
        ];

        $jsContent = 'function test() { console.log("test"); }';
        $minifiedContent = (new Javascript())->Run($jsContent);

        $this->assertNotEmpty($minifiedContent);
    }
}
