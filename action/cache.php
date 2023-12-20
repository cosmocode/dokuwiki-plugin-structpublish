<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;
use dokuwiki\Cache\CacheParser;

/**
 * Double caching of pages containing struct aggregations:
 * one for regular users, one for publishers/approvers
 *
 * @see action_plugin_struct_cache
 */
class action_plugin_structpublish_cache extends ActionPlugin
{
    /**
     * Registers a callback function for a given event
     *
     * @param EventHandler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handleCacheAggregation');
    }

    /**
     * For pages containing an aggregation, add structpublish flag to cache key
     * to differentiate between caches for regular and privileged users
     *
     * @param Event $event event object by reference
     * @return bool
     */
    public function handleCacheAggregation(Event $event)
    {
        /** @var CacheParser $cache */
        $cache = $event->data;
        if ($cache->mode != 'xhtml') return true;
        if (!$cache->page) return true; // not a page cache

        $meta = p_get_metadata($cache->page, 'plugin struct');
        if (isset($meta['hasaggregation'])) {
            // separate caches for publishers/approvers and others
             $cache->key .= ';' . helper_plugin_structpublish_db::userHasRole($cache->page, '', []);

            // rebuild cache name
            $cache->cache = getCacheName($cache->key, $cache->ext);
        }

        return true;
    }
}
