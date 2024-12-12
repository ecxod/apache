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

    public function testParseApacheMacroConfig(): void
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

        $result =  $this->apache->parseApacheMacroConfig($this->tempFile);

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertEquals('Value1', $result['KEY1']);
        $this->assertEquals('Value2 with spaces', $result['KEY2']);
        $this->assertEquals('Value3 continued on next line', $result['KEY3']);
        $this->assertEquals('Value4', $result['KEY4']);
    }

    public function testParseApacheMacroConfig_WithNonExistentFile(): void
    {
        $this->expectException(\Error::class);
        $result = $this->apache->parseApacheMacroConfig('non_existent_file.txt');
        $this->assertFalse($result);
    }

    public function testParseApacheMacroConfig_WithEmptyFilePath(): void
    {
        $this->expectException(\Error::class);
        $result = $this->apache->parseApacheMacroConfig('');
        $this->assertFalse($result);
    }





    public function testParseApacheMacroConfigLinear(): void
    {
        $configContent = <<<EOD
# This is a comment
KEY1          KEY2           KEYn
VALUEa1    VALUEa2    VALUEan
VALUEb1    VALUEb2    VALUEbn
VALUEc1    VALUEc2    VALUEcn
EOD;
    
        file_put_contents($this->tempFile, $configContent);
    
        $result = $this->apache->parseApacheMacroConfigLinear($this->tempFile);
    
        $this->assertIsArray($result);
        // $this->assertCount(3, $result);
    
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
        //$this->expectException(\Error::class);
        $result =$this->apache->parseApacheMacroConfigLinear('non_existent_file_linear.txt');
        $this->assertFalse($result);
    }

    public function testParseApacheMacroConfigLinear_WithEmptyFilePath(): void
    {
        //$this->expectException(\Error::class);
        $result =$this->apache->parseApacheMacroConfigLinear('');
        $this->assertFalse($result);
    }


}
