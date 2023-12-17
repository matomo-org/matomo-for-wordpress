<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Decompress;

use Matomo\Decompress\ZipArchive;

class ZipArchiveTest extends BaseTest
{
    protected function setUp()
    {
        parent::setUp();

        if (! class_exists('ZipArchive')) {
            $this->markTestSkipped('The PHP zip extension is not installed, skipping ZipArchive tests');
        }
    }

    public function testRelativePath()
    {
        $test = 'relative';
        $filename = $this->fixtureDirectory . $test . '.zip';

        $unzip = new ZipArchive($filename);
        $res = $unzip->extract($this->tempDirectory);
        $this->assertCount(1, $res);
        $this->assertFileExists($this->tempDirectory . $test . '.txt');
        $this->assertFileNotExists(__DIR__ . '/' . $test . '.txt');
        $this->assertFileNotExists(__DIR__ . '/../../tests/' . $test . '.txt');
        unlink($this->tempDirectory . $test . '.txt');
    }

    public function testRelativePathAttack()
    {
        $test = 'zaatt';
        $filename = $this->fixtureDirectory . $test . '.zip';

        $unzip = new ZipArchive($filename);
        $res = $unzip->extract($this->tempDirectory);
        $this->assertEquals(0, $res);
        $this->assertFileNotExists($this->tempDirectory . $test . '.txt');
        $this->assertFileNotExists($this->tempDirectory . '../' . $test . '.txt');
        $this->assertFileNotExists(__DIR__ . '/' . $test . '.txt');
        $this->assertFileNotExists(__DIR__ . '/../' . $test . '.txt');
        $this->assertFileNotExists(__DIR__ . '/../../' . $test . '.txt');
    }

    public function testAbsolutePathAttack()
    {
        $test = 'zaabs';
        $filename = $this->fixtureDirectory . $test . '.zip';

        $unzip = new ZipArchive($filename);
        $res = $unzip->extract($this->tempDirectory);
        $this->assertEquals(0, $res);
        $this->assertFileNotExists($this->tempDirectory . $test . '.txt');
        $this->assertFileNotExists(__DIR__ . '/' . $test . '.txt');
    }

    public function testUnzipErrorInfo()
    {
        $filename = $this->fixtureDirectory . '/zaabs.zip';

        $unzip = new ZipArchive($filename);
        $this->assertContains('No error', $unzip->errorInfo());
    }

    public function testUnzipEmptyFile()
    {
        $filename = $this->fixtureDirectory . '/empty.zip';

        $unzip = new ZipArchive($filename);
        $res = $unzip->extract($this->tempDirectory);
        $this->assertEquals(0, $res);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Error opening
     */
    public function testUnzipNotExistingFile()
    {
        new ZipArchive($this->fixtureDirectory . '/NotExisting.zip');
    }
}
