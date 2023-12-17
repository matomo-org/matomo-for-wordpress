<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache;

use Matomo\Cache\Transient;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Transient
 */
class TransientTest extends TestCase
{
    /**
     * @var Transient
     */
    private $cache;

    private $cacheId = 'testid';
    private $cacheValue = 'exampleValue';

    protected function setUp()
    {
        $this->cache = new Transient();
        $this->cache->save($this->cacheId, $this->cacheValue);
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

    public function test_save_shouldBeAbleToSetObjects()
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

}
