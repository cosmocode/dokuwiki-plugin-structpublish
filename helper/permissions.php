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
    const ACTION_REVIEW = 'review';
    const ACTION_PUBLISH = 'publish';

    /** @var helper_plugin_sqlite|null  */
    protected $sqlite;

    protected static $assignments = [
        'test:plugins:structpublish' => '@user',
        'test:plugins:structpublish2' => '@user'
    ];

    public function __construct()
    {
        /** @var \helper_plugin_structpublish_db $helper */
        $helper = plugin_load('helper', 'structpublish_db');
        $this->sqlite = $helper->getDB();
    }

    public function getDb()
    {
        return $this->sqlite;
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

        $sql = 'SELECT * FROM structpublish_assignments WHERE pid = ? AND assigned = 1';
        $res = $this->sqlite->query($sql, $ID);
        if ($res && $this->sqlite->res2count($res)) {
            return true;
        }
        return false;
    }

    /**
     * Get the latest published revision
     *
     * @param string $id
     * @return int
     */
    public function getLatestPublished($id)
    {
        $sql = 'SELECT MAX(rev) FROM structpublish_revisions WHERE id = ? AND status = ?';
        $res = $this->sqlite->query($sql, $id, Revision::STATUS_PUBLISHED);
        if (!$res) return 0;

        return $this->sqlite->res2single($res);
    }
}
