<?php

namespace Ecxod\Tests;

use Ecxod\Apache\Apache;
use PHPUnit\Framework\TestCase;


class ApacheTest extends TestCase
{
    private $tempFile;

    private $apache;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'apache_config_');
        $this->apache = new Apache;
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testParseApacheMacroConfigLinear(): void
    {

        $keysArr = ["KEY1", "KEY2", "KEYn"];
        $configContent = <<<EOD
# This is a comment
VALUEa1    VALUEa2    \
    VALUEan
# VALUEb1    VALUEb2    VALUEbn
VALUEb1    VALUEb2    VALUEbn
 
VALUEc1    VALUEc2    VALUEcn
EOD;

        file_put_contents($this->tempFile, $configContent);

        $result = $this->apache->parseApacheMacroConfigLinear(filePath: $this->tempFile,keysArr: $keysArr);

        $this->assertIsArray($result);

        $this->assertCount(3, $result);

        // Check structure of the first row
        $this->assertArrayHasKey('KEY1', $result[0]);
        $this->assertArrayHasKey('KEY2', $result[0]);
        $this->assertArrayHasKey('KEYn', $result[0]);

        // Check values of each row
        $this->assertEquals('VALUEa1', $result[0]['KEY1']);
        $this->assertEquals('VALUEa2', $result[0]['KEY2']);
        $this->assertEquals('VALUEan', $result[0]['KEYn']);

        $this->assertEquals('VALUEb1', $result[1]['KEY1']);
        $this->assertEquals('VALUEb2', $result[1]['KEY2']);
        $this->assertEquals('VALUEbn', $result[1]['KEYn']);

        $this->assertEquals('VALUEc1', $result[2]['KEY1']);
        $this->assertEquals('VALUEc2', $result[2]['KEY2']);
        $this->assertEquals('VALUEcn', $result[2]['KEYn']);
    }

    public function testParseApacheMacroConfigLinear_WithNonExistentFile(): void
    {
        $result = $this->apache->parseApacheMacroConfigLinear('non_existent_file_linear.txt');
        $this->assertFalse($result);
    }

    public function testParseApacheMacroConfigLinear_WithEmptyFilePath(): void
    {
        $result = $this->apache->parseApacheMacroConfigLinear();
        $this->assertFalse($result);
    }
}
