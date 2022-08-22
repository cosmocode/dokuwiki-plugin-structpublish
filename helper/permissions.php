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
}
