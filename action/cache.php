<?php

/**
 * Double caching of pages containing struct aggregations:
 * one for regular users, one for publishers/approvers
 * @see action_plugin_struct_cache
 */
class action_plugin_structpublish_cache extends DokuWiki_Action_Plugin
{
    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handleCacheAggregation');
    }

    /**
     * For pages containing an aggregation, add structpublish flag to cache key
     * to differentiate between caches for regular and privileged users
     *
     * @param Doku_Event $event event object by reference
     * @return bool
     */
    public function handleCacheAggregation(Doku_Event $event)
    {
        /** @var \dokuwiki\Cache\CacheParser $cache */
        $cache = $event->data;
        if ($cache->mode != 'xhtml') return true;
        if (!$cache->page) return true; // not a page cache

        $meta = p_get_metadata($cache->page, 'plugin struct');
        if (isset($meta['hasaggregation'])) {
            // separate caches for publishers/approvers and others
             $cache->key .= ';' . action_plugin_structpublish_sqlitefunction::userHasRole($cache->page, '', []);

            // rebuild cache name
            $cache->cache = getCacheName($cache->key, $cache->ext);
        }

        return true;
    }
}
