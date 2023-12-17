<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache;

use Matomo\Cache\Backend\ArrayCache;
use Matomo\Cache\Lazy;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Lazy
 */
class LazyTest extends TestCase
{
    /**
     * @var Lazy
     */
    private $cache;

    private $cacheId = 'testid';
    private $cacheValue = 'exampleValue';

    protected function setUp()
    {
        $backend = new ArrayCache();
        $this->cache = new Lazy($backend);
        $this->cache->save($this->cacheId, $this->cacheValue);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Empty cache id
     */
    public function test_fetch_shouldFail_IfCacheIdIsEmpty()
    {
        $this->cache->fetch('');
    }

    /**
     * @dataProvider getInvalidCacheIds
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid cache id
     */
    public function test_shouldFail_IfCacheIdIsInvalid($method, $id)
    {
        $this->executeMethodOnCache($method, $id);
    }

    /**
     * @dataProvider getValidCacheIds
     */
    public function test_shouldNotFail_IfCacheIdIsValid($method, $id)
    {
        $this->executeMethodOnCache($method, $id);
        $this->assertTrue(true);
    }

    private function executeMethodOnCache($method, $id)
    {
        if ('save' === $method) {
            $this->cache->$method($id, 'val');
        } else {
            $this->cache->$method($id);
        }
    }

    public function test_fetch_shouldReturnFalse_IfNoSuchCacheIdExists()
    {
        $this->assertFalse($this->cache->fetch('randomid'));
    }

    public function test_fetch_shouldReturnTheCachedValue_IfCacheIdExists()
    {
        $this->assertEquals($this->cacheValue, $this->cache->fetch($this->cacheId));
    }

    public function test_contains_shouldReturnFalse_IfNoSuchCacheIdExists()
    {
        $this->assertFalse($this->cache->contains('randomid'));
    }

    public function test_contains_shouldReturnTrue_IfCacheIdExists()
    {
        $this->assertTrue($this->cache->contains($this->cacheId));
    }

    public function test_delete_shouldReturnTrue_OnSuccess()
    {
        $this->assertTrue($this->cache->delete($this->cacheId));
    }

    public function test_delete_shouldActuallyDeleteCacheId()
    {
        $this->assertHasCacheEntry($this->cacheId);

        $this->cache->delete($this->cacheId);

        $this->assertHasNotCacheEntry($this->cacheId);
    }

    public function test_delete_shouldNotDeleteAnyOtherCacheIds()
    {
        $this->cache->save('anyother', 'myvalue');
        $this->assertHasCacheEntry($this->cacheId);

        $this->cache->delete($this->cacheId);

        $this->assertHasCacheEntry('anyother');
    }

    public function test_save_shouldOverwriteAnyValue_IfCacheIdAlreadyExists()
    {
        $this->assertHasCacheEntry($this->cacheId);

        $value = 'anyotherValuE';
        $this->cache->save($this->cacheId, $value);

        $this->assertSame($value, $this->cache->fetch($this->cacheId));
    }

    public function test_save_shouldBeAbleToSetArrays()
    {
        $value = array('anyotherE' => 'anyOtherValUE', 1 => array(2));
        $this->cache->save($this->cacheId, $value);

        $this->assertSame($value, $this->cache->fetch($this->cacheId));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage cannot use this cache to cache an object
     */
    public function test_save_shouldFail_IfTryingToSetAnObject()
    {
        $value = (object) array('anyotherE' => 'anyOtherValUE', 1 => array(2));
        $this->cache->save($this->cacheId, $value);

        $this->assertSame($value, $this->cache->fetch($this->cacheId));
    }

    public function test_save_shouldBeAbleToSetNumbers()
    {
        $value = 5.4;
        $this->cache->save($this->cacheId, $value);

        $this->assertSame($value, $this->cache->fetch($this->cacheId));
    }

    public function test_flush_shouldRemoveAllCacheIds()
    {
        $this->assertHasCacheEntry($this->cacheId);
        $this->cache->save('mykey', 'myvalue');
        $this->assertHasCacheEntry('mykey');

        $this->cache->flushAll();

        $this->assertHasNotCacheEntry($this->cacheId);
        $this->assertHasNotCacheEntry('mykey');
    }

    private function assertHasCacheEntry($cacheId)
    {
        $this->assertTrue($this->cache->contains($cacheId));
    }

    private function assertHasNotCacheEntry($cacheId)
    {
        $this->assertFalse($this->cache->contains($cacheId));
    }

    public function getInvalidCacheIds()
    {
        $ids = array();
        $methods = array('fetch', 'save', 'contains', 'delete');

        foreach ($methods as $method) {
            $ids[] = array($method, 'eteer#');
            $ids[] = array($method, '-test');
            $ids[] = array($method, '_test');
            $ids[] = array($method, '.test');
            $ids[] = array($method, 'test/test');
            $ids[] = array($method, '../test/');
            $ids[] = array($method, 'test0*');
            $ids[] = array($method, 'test\\test');
        }

        return $ids;
    }

    public function getValidCacheIds()
    {
        $ids = array();
        $methods = array('fetch', 'save', 'contains', 'delete');

        foreach ($methods as $method) {
            $ids[] = array($method, '012test');
            $ids[] = array($method, 'test012test');
            $ids[] = array($method, 't.est.012test');
            $ids[] = array($method, 't-est-test');
            $ids[] = array($method, 't_est_tes4t');
            $ids[] = array($method, 't_est.te-s2t');
            $ids[] = array($method, 't_est...te-s2t');
        }

        return $ids;
    }
}
