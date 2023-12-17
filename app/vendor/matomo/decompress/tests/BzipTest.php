<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Decompress;

use Matomo\Decompress\Bzip;

class BzipTest extends BaseTest
{
    public function testBzipFile()
    {
        $extractFile = $this->tempDirectory . 'testbz.txt';

        $unzip = new Bzip($this->fixtureDirectory . '/test.bz2');
        $res = $unzip->extract($extractFile);
        $this->assertTrue($res);

        $this->assertFileContentsEquals('TESTSTRING', $extractFile);
    }

    public function testUnzipInvalidFile2()
    {
        $filename = $this->fixtureDirectory . '/NotExisting.zip';

        $unzip = new Bzip($filename);
        $res   = $unzip->extract($this->tempDirectory);
        $this->assertEquals(0, $res);

        $this->assertEquals('bzopen failed', $unzip->errorInfo());
    }
}
