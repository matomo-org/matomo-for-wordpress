<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Translation\Loader;

use Matomo\Cache\Lazy;
/**
 * Caches the translations loaded by another loader.
 */
class LoaderCache implements \Piwik\Translation\Loader\LoaderInterface
{
    /**
     * @var LoaderInterface
     */
    private $loader;
    /**
     * @var Lazy
     */
    private $cache;
    public function __construct(\Piwik\Translation\Loader\LoaderInterface $loader, Lazy $cache)
    {
        $this->loader = $loader;
        $this->cache = $cache;
    }
    /**
     * {@inheritdoc}
     */
    public function load($language, array $directories)
    {
        if (empty($language)) {
            return array();
        }
        $cacheKey = $this->getCacheKey($language, $directories);
        $translations = $this->cache->fetch($cacheKey);
        if (empty($translations) || !is_array($translations)) {
            $translations = $this->loader->load($language, $directories);
            $this->cache->save($cacheKey, $translations, 43200);
            // ttl=12hours
        }
        return $translations;
    }
    private function getCacheKey($language, array $directories)
    {
        $cacheKey = 'Translations-' . $language . '-';
        // in case loaded plugins change (ie Tests vs Tracker vs UI etc)
        $cacheKey .= sha1(implode('', $directories));
        return $cacheKey;
    }
}
