<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\SEO\Metric;

use Piwik\Cache;
use Matomo\Cache\Lazy;
/**
 * Caches another provider.
 */
class ProviderCache implements \Piwik\Plugins\SEO\Metric\MetricsProvider
{
    /**
     * @var MetricsProvider
     */
    private $provider;
    /**
     * @var Lazy
     */
    private $cache;
    public function __construct(\Piwik\Plugins\SEO\Metric\MetricsProvider $provider)
    {
        $this->provider = $provider;
        $this->cache = Cache::getLazyCache();
    }
    public function getMetrics($domain)
    {
        $cacheId = 'SEO_getRank_' . md5($domain ?? '');
        $metrics = $this->cache->fetch($cacheId);
        if (!is_array($metrics)) {
            $metrics = $this->provider->getMetrics($domain);
            $this->cache->save($cacheId, $metrics, 60 * 60 * 6);
        }
        return $metrics;
    }
}
