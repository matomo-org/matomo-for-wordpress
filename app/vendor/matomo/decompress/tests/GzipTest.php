<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Decompress;

use Matomo\Decompress\Gzip;

class GzipTest extends BaseTest
{
    public function testGzipFile()
    {
        $extractFile = $this->tempDirectory . 'testgz.txt';

        $unzip = new Gzip($this->fixtureDirectory . '/test.gz');
        $res = $unzip->extract($extractFile);
        $this->assertTrue($res);

        $this->assertFileContentsEquals('TESTSTRING', $extractFile);
    }

    public function testUnzipInvalidFile2()
    {
        $filename = $this->fixtureDirectory . '/NotExisting.zip';

        $unzip = new Gzip($filename);
        $res   = $unzip->extract($this->tempDirectory);
        $this->assertEquals(0, $res);

        $this->assertEquals('gzopen failed', $unzip->errorInfo());
    }
}
