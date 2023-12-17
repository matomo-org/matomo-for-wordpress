<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache\Backend;

use Matomo\Cache\Backend\ArrayCache;
use Matomo\Cache\Backend\Chained;
use Matomo\Cache\Backend\DefaultTimeoutDecorated;
use Matomo\Cache\Backend\Factory;
use Matomo\Cache\Backend\File;
use Matomo\Cache\Backend\KeyPrefixDecorated;
use Matomo\Cache\Backend\NullCache;
use Matomo\Cache\Backend\Redis;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Backend\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new Factory();
    }

    public function test_buildArrayCache_ShouldReturnInstanceOfArray()
    {
        $cache = $this->factory->buildArrayCache();
        $this->assertInstanceOf(ArrayCache::class, $cache);
    }

    public function test_buildNullCache_ShouldReturnInstanceOfNull()
    {
        $cache = $this->factory->buildNullCache();
        $this->assertInstanceOf(NullCache::class, $cache);
    }

    public function test_buildFileCache_ShouldReturnInstanceOfFile()
    {
        $cache = $this->factory->buildFileCache(array('directory' => __DIR__));
        $this->assertInstanceOf(File::class, $cache);
    }

    public function test_buildChainedCache_ShouldReturnInstanceOfChained()
    {
        $cache = $this->factory->buildChainedCache(array('backends' => array()));
        $this->assertInstanceOf(Chained::class, $cache);
    }

    public function test_buildBackend_Chained_ShouldActuallyCreateInstancesOfNestedBackends()
    {
        $options = array(
            'backends' => array('array', 'file'),
            'file'     => array('directory' => __DIR__),
            'array'    => array()
        );

        /** @var Chained $cache */
        $cache = $this->factory->buildBackend('chained', $options);

        $backends = $cache->getBackends();

        $this->assertInstanceOf(ArrayCache::class, $backends[0]);
        $this->assertInstanceOf(File::class, $backends[1]);
    }

    public function test_buildRedisCache_ShouldReturnInstanceOfRedis()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $cache = $this->factory->buildRedisCache(array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 0.0));
        $this->assertInstanceOf(Redis::class, $cache);
    }

    public function test_buildBackend_Redis_ShouldReturnInstanceOfRedis()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $options = array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 0.0);

        $cache = $this->factory->buildBackend('redis', $options);
        $this->assertInstanceOf(Redis::class, $cache);
    }

    public function test_buildBackend_Redis_ShouldForwardOptionsToRedisInstance()
    {
        $this->skipTestIfRedisIsNotInstalled();

        $options = array('host' => '127.0.0.1', 'port' => '6379', 'timeout' => 4.2, 'database' => 5);

        /** @var Redis $cache */
        $cache = $this->factory->buildBackend('redis', $options);
        $redis = $cache->getRedis();

        $this->assertEquals('127.0.0.1', $redis->getHost());
        $this->assertEquals(6379, $redis->getPort());
        $this->assertEquals(4.2, $redis->getTimeout());
        $this->assertEquals(5, $redis->getDBNum());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage RedisCache is not configured
     */
    public function test_buildRedisCache_ShouldFail_IfPortIsMissing()
    {
        $this->factory->buildRedisCache(array('host' => '127.0.0.1'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage RedisCache is not configured
     */
    public function test_buildRedisCache_ShouldFail_IfHostIsMissing()
    {
        $this->factory->buildRedisCache(array('port' => '6379'));
    }

    public function test_buildBackend_ArrayCache_ShouldReturnInstanceOfArray()
    {
        $cache = $this->factory->buildBackend('array', array());
        $this->assertInstanceOf(ArrayCache::class, $cache);
    }

    public function test_buildBackend_NullCache_ShouldReturnInstanceOfNull()
    {
        $cache = $this->factory->buildBackend('null', array());
        $this->assertInstanceOf(NullCache::class, $cache);
    }

    public function test_buildBackend_FileCache_ShouldReturnInstanceOfFile()
    {
        $cache = $this->factory->buildBackend('file', array('directory' => __DIR__));
        $this->assertInstanceOf(File::class, $cache);
    }

    public function test_buildBackend_Chained_ShouldReturnInstanceOfChained()
    {
        $cache = $this->factory->buildBackend('chained', array('backends' => array()));
        $this->assertInstanceOf(Chained::class, $cache);
    }

    /**
     * @expectedException \Matomo\Cache\Backend\Factory\BackendNotFoundException
     */
    public function test_buildBackend_ShouldThrowException_IfInvalidTypeGiven()
    {
        $this->factory->buildBackend('noTValId', array());
    }

    private function skipTestIfRedisIsNotInstalled()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The test ' . __METHOD__ . ' requires the use of redis');
        }
    }


    public function test_buildBackend_Chained_ShouldCreateInstancesOfNestedDecorators()
    {}

    public function test_buildBackend_Decorated_DefaultTimeoutDecorated_ShouldActuallyCreateInstanceOfNestedBackend()
    {
        $options = array(
            'backend' => 'array',
            'array'    => array(),
            'defaultTimeout' => 555
        );

        /** @var DefaultTimeoutDecorated $cache */
        $cache = $this->factory->buildBackend('defaultTimeout', $options);


        $backend = $cache->getBackend();
        $this->assertInstanceOf(ArrayCache::class, $backend);
    }

    public function test_buildBackend_Decorated_KeyPrefixDecorated_ShouldActuallyCreateInstanceOfNestedBackend()
    {
        $options = array(
            'backend' => 'array',
            'array'    => array(),
            'keyPrefix' => '555'
        );

        /** @var KeyPrefixDecorated $cache */
        $cache = $this->factory->buildBackend('keyPrefix', $options);


        $backend = $cache->getBackend();
        $this->assertInstanceOf(ArrayCache::class, $backend);
    }
}
