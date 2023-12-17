<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache\Backend;

use Matomo\Cache\Backend\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Backend\File
 */
class FileTest extends TestCase
{
    /**
     * @var File
     */
    private $cache;

    private $cacheId = 'testid';

    protected function setUp()
    {
        $this->cache = $this->createFileCache();
        $this->cache->doSave($this->cacheId, 'anyvalue', 100);
    }

    protected function tearDown()
    {
        $this->cache->flushAll();
    }

    private function createFileCache($namespace = '')
    {
        $path = $this->getPath($namespace);

        return new File($path);
    }

    private function getPath($namespace = '', $id = '')
    {
        $path = __DIR__ . '/../tmp';

        if (!empty($namespace)) {
            $path .= '/' . $namespace;
        }

        if (!empty($id)) {
            $path .= '/' . $id . '.php';
        }

        return $path;
    }

    public function test_doSave_shouldCreateDirectoryWith750Permission_IfWritingIntoNewDirectory()
    {
        $namespace = 'test';

        $file = $this->createFileCache($namespace);
        $file->doSave('myidtest', 'myvalue');

        $this->assertDirectoryExists($this->getPath($namespace));
        $file->flushAll();
    }

    public function test_doSave_shouldCreateFile()
    {
        $this->cache->doSave('myidtest', 'myvalue');

        $this->assertFileExists($this->getPath('', 'myidtest'));
    }

    public function test_doSave_shouldSetLifeTime()
    {
        $this->cache->doSave('myidtest', 'myvalue', 500);

        $path =  $this->getPath('', 'myidtest');

        $contents = include $path;

        $this->assertGreaterThan(time() + 450, $contents['lifetime']);
        $this->assertLessThan(time() + 550, $contents['lifetime']);
    }

    public function test_doFetch_ParseError()
    {
        $test = $this->cache->getFilename('foo');
        file_put_contents($test, '<?php echo $dat
    && foo();flelr');

        $this->assertFalse($this->cache->doFetch('foo'));
    }

    /**
     * @dataProvider getTestDataForGetFilename
     */
    public function test_getFilename_shouldConstructFilenameFromId($id, $expectedFilename)
    {
        $this->assertEquals($expectedFilename, $this->cache->getFilename($id));
    }

    public function getTestDataForGetFilename()
    {
        $dir = realpath($this->getPath());

        return [
            ['genericid', $dir . '/genericid.php'],
            ['id with space', $dir . '/id with space.php'],
            ['id \/ with :"?_ spe<>cial cha|rs', $dir . '/id  with _ special chars.php'],
            ['with % allowed & special chars', $dir . '/with % allowed & special chars.php'],
        ];
    }
}
