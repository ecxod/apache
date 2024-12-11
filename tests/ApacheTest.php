<?php

namespace Ecxod\Tests;

use \PHPUnit\Framework\TestCase;

// use function array_keys;
// use function array_merge;
// use function assert;
// use function fclose;
// use function file_put_contents;
// use function fwrite;
// use function ini_get_all;
// use function is_array;
// use function is_resource;
// use function proc_close;
// use function proc_open;
// use function stream_get_contents;
// use function sys_get_temp_dir;
// use function tempnam;
// use function trim;
// use function unlink;
// use function xdebug_is_debugger_active;


class ApacheTest extends TestCase
{
    private $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'apache_config_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function parseApacheMacroConfig()
    {
        $configContent = <<<EOD
# This is a comment
KEY1 Value1
KEY2 Value2 with spaces
KEY3 Value3 \
     continued on next line
KEY4 Value4
EOD;

        file_put_contents($this->tempFile, $configContent);

        $result = parseApacheMacroConfig($this->tempFile);

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertEquals('Value1', $result['KEY1']);
        $this->assertEquals('Value2 with spaces', $result['KEY2']);
        $this->assertEquals('Value3 continued on next line', $result['KEY3']);
        $this->assertEquals('Value4', $result['KEY4']);
    }

    public function testParseApacheMacroConfigWithNonExistentFile()
    {
        $this->expectException(\Error::class);
        parseApacheMacroConfig('non_existent_file.txt');
    }

    public function testParseApacheMacroConfigWithEmptyFilePath()
    {
        $this->expectException(\Error::class);
        parseApacheMacroConfig('');
    }
}
