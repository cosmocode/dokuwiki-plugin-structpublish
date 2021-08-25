<?php

use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * DokuWiki Plugin structpublish (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */

class helper_plugin_structpublish_permissions extends DokuWiki_Plugin
{
    const ACTION_APPROVE = 'approve';
    const ACTION_PUBLISH = 'publish';

    /** @var helper_plugin_sqlite|null  */
    protected $sqlite;

    public function __construct()
    {
        /** @var \helper_plugin_struct_db $helper */
        $helper = plugin_load('helper', 'struct_db');
        $this->sqlite = $helper->getDB();
    }

    public function getDb()
    {
        return $this->sqlite;
    }

    public function getActionLinks($revision)
    {
        if ($revision->getStatus() === Revision::STATUS_PUBLISHED) return [];

        global $ID;
        $links = [ self::ACTION_PUBLISH => wl($ID, ['structpublish' => self::ACTION_PUBLISH])];
        return $links;
    }

    /**
     * Return true if the current user may see and approve drafts of the current page.
     *
     * @param string $id
     * @return bool
     */
    public function isPublisher($id)
    {
        $user = $_SERVER['REMOTE_USER'];

        // TODO implement checks
        return true;
    }

    /**
     * Returns true if the current page is included in publishing workflows
     *
     * @return bool
     */
    public function isPublishable()
    {
        global $ID;

        // TODO implement real checks
        $sql = 'SELECT tbl FROM schema_assignments WHERE pid = ? AND assigned = 1';
        $res = $this->sqlite->query($sql, $ID);
        if ($res && $this->sqlite->res2count($res)) {
            return true;
        }
        return false;
    }
}
