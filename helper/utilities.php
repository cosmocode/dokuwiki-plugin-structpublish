<?php

/**
 * DokuWiki Plugin structpublish (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */

class helper_plugin_structpublish_utilities extends DokuWiki_Plugin
{
    /**
     * Overwrites dummy IS_PUBLISHER from struct plugin
     *
     * @return bool
     */
    public function IS_PUBLISHER($pid) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // FIXME real check
        // FIXME assignments have to exist!
//        if (array_key_exists($pid, self::$assignments)) {
            return false;
//        }

//        return false;
    }
}
