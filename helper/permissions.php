<?php
/**
 * DokuWiki Plugin structpublish (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */

class helper_plugin_structpublish_permissions extends DokuWiki_Plugin
{
    /**
     * Return true if the current user may see and approve drafts.
     *
     * @param string $id
     * @param string $user
     * @return bool
     */
    public function isPublisher($id, $user)
    {
        return true;
    }
}
