<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache;

use Matomo\Cache\Backend\ArrayCache;
use Matomo\Cache\Eager;
use Matomo\Cache\Backend;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Eager
 */
class EagerTest extends TestCase
{
    /**
     * @var Eager
     */
    private $cache;

    /**
     * @var Backend
     */
    private $backend;

    private $storageId  = 'eagercache';
    private $cacheId    = 'testid';
    private $cacheValue = 'exampleValue';

    protected function setUp()
    {
        $this->backend = new ArrayCache();
        $this->backend->doSave($this->storageId, array($this->cacheId => $this->cacheValue));

        $this->cache = new Eager($this->backend, $this->storageId);
    }

    public function test_contains_shouldReturnFalse_IfNoSuchCacheIdExists()
    {
        $this->assertFalse($this->cache->contains('randomid'));
    }

    public function test_contains_shouldReturnTrue_IfSuchCacheIdExists()
    {
        $this->assertTrue($this->cache->contains($this->cacheId));
    }

    public function test_fetch_shouldReturnTheCachedValue_IfCacheIdExists()
    {
        $this->assertEquals($this->cacheValue, $this->cache->fetch($this->cacheId));
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

    public function test_delete_shouldReturnTrue_OnSuccess()
    {
        $this->assertTrue($this->cache->delete($this->cacheId));
    }

    public function test_delete_shouldReturnFalse_IfCacheIdDoesNotExist()
    {
        $this->assertFalse($this->cache->delete('IdoNotExisT'));
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

    public function test_flush_shouldRemoveAllCacheIds()
    {
        $this->assertHasCacheEntry($this->cacheId);
        $this->cache->save('mykey', 'myvalue');
        $this->assertHasCacheEntry('mykey');
        $this->assertTrue($this->backend->doContains($this->storageId));

        $this->cache->flushAll();

        $this->assertHasNotCacheEntry($this->cacheId);
        $this->assertHasNotCacheEntry('mykey');
        $this->assertFalse($this->backend->doContains($this->storageId)); // should also remove the storage entry
    }

    public function test_persistCacheIfNeeded_shouldActuallySaveValuesInBackend_IfThereWasSomethingSet()
    {
        $this->cache->save('mykey', 'myvalue');

        $expected = array($this->cacheId => $this->cacheValue);
        $this->assertEquals($expected, $this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $expected['mykey'] = 'myvalue';

        $this->assertEquals($expected, $this->getContentOfStorage());
    }

    public function test_persistCacheIfNeeded_shouldActuallySaveValuesInBackend_IfThereWasSomethingDelete()
    {
        $this->cache->delete($this->cacheId);

        $expected = array($this->cacheId => $this->cacheValue);
        $this->assertEquals($expected, $this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $this->assertEquals(array(), $this->getContentOfStorage());
    }

    public function test_persistCacheIfNeeded_shouldNotSaveAnyValuesInBackend_IfThereWasNoChange()
    {
        $this->backend->doDelete($this->storageId);
        $this->assertFalse($this->getContentOfStorage());

        $this->cache->persistCacheIfNeeded(400);

        $this->assertFalse($this->getContentOfStorage()); // should not have set the content of cache ($cacheId => $cacheValue)
    }

    private function getContentOfStorage()
    {
        return $this->backend->doFetch($this->storageId);
    }

    private function assertHasCacheEntry($cacheId)
    {
        $this->assertTrue($this->cache->contains($cacheId));
    }

    private function assertHasNotCacheEntry($cacheId)
    {
        $this->assertFalse($this->cache->contains($cacheId));
    }

}
