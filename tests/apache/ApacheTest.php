<?php

namespace Ecxod\Tests;

use Ecxod\Apache\Apache;
use DOMDocument;
use PHPUnit\Framework\TestCase;


class ApacheTest extends TestCase
{
    private $tempFile;
    private $testDir;

    private $apache;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(directory: sys_get_temp_dir(), prefix: 'apache_config_');
        $this->testDir = sys_get_temp_dir() . '/test_apache_conf';
        mkdir($this->testDir);
        $this->apache = new Apache;
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);

        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }


    /** removint Test Directory and everything inside.
     * @param mixed $dir 
     * @return void 
     */
    private function removeDirectory($dir)
    {
        if (is_dir(filename: $dir)) {
            $objects = scandir(directory: $dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir(filename: $dir . "/" . $object)) {
                        $this->removeDirectory($dir . "/" . $object);
                    } else {
                        unlink(filename: $dir . "/" . $object);
                    }
                }
            }
            rmdir(directory: $dir);
        }
    }

    public function testGetMacroDefinitions()
    {
        // Test-Dateien erstellen
        file_put_contents("{$this->testDir}/test1.conf", "<Macro SSLHost1 \$domain \$port \$docroot \$allowed");
        file_put_contents("{$this->testDir}/test2.conf", "<Macro SSLHost2 \$domain \$port \$docroot");

        $result =  $this->apache->getMacroDefinitions(directory: $this->testDir);

        $expected = [
            'SSLHost1' => ['$domain', '$port', '$docroot', '$allowed'],
            'SSLHost2' => ['$domain', '$port', '$docroot']
        ];

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function testNonExistentDirectory()
    {
        $result =  $this->apache->getMacroDefinitions(directory: '/non/existent/directory');
        $this->assertEmpty(actual: $result);
    }

    public function testEmptyDirectory()
    {
        $result =  $this->apache->getMacroDefinitions(directory: $this->testDir);
        $this->assertEmpty(actual: $result);
    }

    public function testNoMacroDefinitions()
    {
        file_put_contents(filename: "{$this->testDir}/test.conf", data: "Some content without macro");
        $result =  $this->apache->getMacroDefinitions($this->testDir);
        $this->assertEmpty(actual: $result);
    }





    public function testParseApacheMacroConfigLinear()
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

        $result = $this->apache->parseApacheMacroConfigLinear(filePath: $this->tempFile, keysArr: $keysArr);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

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





    /**
     * //TODO: Test schreiben für testReadConfigFile
     * 
     */
    public function testReadConfigFile()
    {
        $this->assertTrue(condition: true, message: 'WARNING : testReadConfigFile not written yet!');
    }

    /**
     * //TODO: Test schreiben für testArrayToXml
     * 
     */
    public function testArrayToXml()
    {
        $this->assertTrue(condition: true, message: 'WARNING : testArrayToXml not written yet!');
    }

    /**
     * //TODO: Test schreiben für testArrayToJson
     * 
     */
    public function testArrayToJson()
    {
        $this->assertTrue(condition: true, message: 'WARNING : testArrayToJson not written yet!');
    }

    /**
     * //TODO: Test schreiben für testProcessConfig
     * 
     */
    public function testProcessConfig()
    {
        $this->assertTrue(condition: true, message: 'WARNING : testProcessConfig not written yet!');
    }


    /**
     * //TODO: Test schreiben für testwalkThrueFolderAndReturnArrayOfFileNames
     * 
     */
    public function testwalkThrueFolderAndReturnArrayOfFileNames()
    {
        $this->assertTrue(condition: true, message: 'WARNING : testwalkThrueFolderAndReturnArrayOfFileNames not written yet!');
    }


    /**
     * //TODO: Test schreiben für testWalkThrueFolderAndReturnFilesInAArray
     * 
     */
    public function testWalkThrueFolderAndReturnFilesInAArray()
    {
        $this->assertTrue(condition: true, message: 'WARNING : testWalkThrueFolderAndReturnFilesInAArray not written yet!');
    }
}
