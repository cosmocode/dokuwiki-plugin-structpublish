<?php

use dokuwiki\plugin\struct\meta\Assignments;
use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * DokuWiki Plugin structpublish (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */
class helper_plugin_structpublish_publish extends DokuWiki_Plugin
{

    /** @var helper_plugin_structpublish_db  */
    protected $dbHelper;

    public function __construct()
    {
        $this->dbHelper = plugin_load('helper', 'structpublish_db');
    }

    /**
     * Save publish data
     *
     * @param string $action
     * @return void
     * @throws Exception
     */
    public function saveRevision($action, $newversion = '')
    {
        global $ID;
        global $INFO;

        if (
            !$this->dbHelper->checkAccess($ID, [$action])
        ) {
            throw new \Exception('User may not ' . $action);
        }

        $sqlite = $this->dbHelper->getDB();
        $revision = new Revision($sqlite, $ID, $INFO['currentrev']);

        if ($action === Constants::ACTION_PUBLISH) {
            $revision->setVersion($newversion);
        }
        $revision->setUser($_SERVER['REMOTE_USER']);
        $revision->setStatus(Constants::transitionBy($action));
        $revision->setTimestamp(time());
        $revision->save();

        if ($action === Constants::ACTION_PUBLISH) {
            $this->updateSchemaData();
        }
    }

    /**
     * Set "published" status in all assigned schemas
     *
     * @return void
     */
    protected function updateSchemaData()
    {
        global $ID;
        global $INFO;

        $schemaAssignments = Assignments::getInstance();
        $tables = $schemaAssignments->getPageAssignments($ID);

        if (empty($tables)) {
            return;
        }

        $sqlite = $this->dbHelper->getDB();

        foreach ($tables as $table) {
            // unpublish earlier revisions
            $sqlite->query("UPDATE data_$table SET published = 0 WHERE pid = ?", [$ID]);
            $sqlite->query("UPDATE multi_$table SET published = 0 WHERE pid = ?", [$ID]);

            // publish the current revision
            $sqlite->query("UPDATE data_$table SET published = 1 WHERE pid = ? AND rev = ?",
                [$ID, $INFO['currentrev']]);
            $sqlite->query("UPDATE multi_$table SET published = 1 WHERE pid = ? AND rev = ?",
                [$ID, $INFO['currentrev']]);
        }
    }
}
