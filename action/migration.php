<?php

use dokuwiki\plugin\sqlite\Tools;

class action_plugin_structpublish_migration extends DokuWiki_Action_Plugin
{
    public const MIN_DB_STRUCT = 19;

    /**
     * @var string  
     */
    protected $table = 'data_structpublish';

    /**
     * @inheritDoc
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleMigrations');
    }

    /**
     * Call our custom migrations. We do not use our own database,
     * so we cannot use the mechanism in sqlite init()
     * which processes updateXXXX.sql files
     *
     * @param  Doku_Event $event
     * @return bool
     * @throws Exception
     */
    public function handleMigrations(Doku_Event $event)
    {
        /**
 * @var \helper_plugin_struct_db $helper 
*/
        $helper = plugin_load('helper', 'struct_db');

        // abort if struct is not installed
        if (!$helper) {
            throw new Exception('Plugin struct is required!');
        }

        $sqlite = $helper->getDB();

        list($dbVersionStruct, $dbVersionStructpublish) = $this->getDbVersions($sqlite);

        // check if struct has required version
        if ($dbVersionStruct < self::MIN_DB_STRUCT) {
            throw new Exception(
                'Plugin struct is outdated. Minimum required database version is ' . self::MIN_DB_STRUCT
            );
        }

        // check whether we are already up-to-date
        $latestVersion = $this->getLatestVersion();
        if (isset($dbVersionStructpublish) && (int) $dbVersionStructpublish >= $latestVersion) {
            return true;
        }

        // check whether we have any pending migrations
        $pending = range(($dbVersionStructpublish ?: 0) + 1, $latestVersion);
        if (empty($pending)) {
            return true;
        }

        // execute the migrations
        $ok = true;

        foreach ($pending as $version) {
            $call = 'migration' . $version;
            $ok = $ok && $this->$call($sqlite);
        }

        // update migration status in struct database
        if ($ok) {
            $sql = "REPLACE INTO opts (val,opt) VALUES ($version,'dbversion_structpublish')";
            $ok = $ok && $sqlite->query($sql);
        }

        return $ok;
    }

    /**
     * Read the current versions for struct and struct publish from the database
     *
     * @param  \dokuwiki\plugin\sqlite\SQLiteDB $sqlite
     * @return array [structversion, structpublishversion]
     */
    protected function getDbVersions($sqlite)
    {
        $dbVersionStruct = null;
        $dbVersionStructpublish = null;

        $sql = 'SELECT opt, val FROM opts WHERE opt=? OR opt=?';
        $vals = $sqlite->queryAll($sql, ['dbversion', 'dbversion_structpublish']);

        foreach ($vals as $val) {
            if ($val['opt'] === 'dbversion') {
                $dbVersionStruct = $val['val'];
            }
            if ($val['opt'] === 'dbversion_structpublish') {
                $dbVersionStructpublish = $val['val'];
            }
        }
        return [$dbVersionStruct, $dbVersionStructpublish];
    }

    /**
     * @return int
     */
    protected function getLatestVersion()
    {
        return (int) trim(file_get_contents(DOKU_PLUGIN . 'structpublish/db/latest.version', false));
    }

    /**
     * Database setup
     *
     * @param  \dokuwiki\plugin\sqlite\SQLiteDB $sqlite
     * @return bool
     */
    protected function migration1($sqlite)
    {
        $file = DOKU_PLUGIN . 'structpublish/db/json/structpublish0001.struct.json';
        $schemaJson = file_get_contents($file);
        $importer = new \dokuwiki\plugin\struct\meta\SchemaImporter('structpublish', $schemaJson);
        $ok = (bool) $importer->build();

        if ($ok) {
            $sql = io_readFile(DOKU_PLUGIN . 'structpublish/db/update0001.sql', false);
            $sqlArr = Tools::SQLstring2array($sql);
            foreach ($sqlArr as $sql) {
                $ok = $ok && $sqlite->query($sql);
            }
        }

        return $ok;
    }

    /**
     * Reset 'latest' flag to 0 for all rows except actually latest ones
     * for each pid / status combination.
     *
     * @param  \dokuwiki\plugin\sqlite\SQLiteDB $sqlite
     * @return bool
     */
    protected function migration2($sqlite)
    {
        $sql = "SELECT rid, pid, latest, col1, max(col4) FROM $this->table GROUP BY pid, col1";
        $latest = $sqlite->queryAll($sql);
        $rids = array_column($latest, 'rid');

        $sql = "UPDATE $this->table SET latest = 0 WHERE rid NOT IN (" . implode(', ', $rids) . ')';

        return (bool) $sqlite->query($sql);
    }

    /**
     * Set 'latest' flag to 0 for all rows except actually latest ones
     * for each page,
     *
     * @param  \dokuwiki\plugin\sqlite\SQLiteDB $sqlite
     * @return bool
     */
    protected function migration3($sqlite)
    {
        $sql = "WITH cte AS (
            SELECT rid, pid, col1 AS status, col4 as rev,
                   rank() OVER ( PARTITION BY pid
                       ORDER BY col4 DESC, col1 = 'draft', col1 = 'approved', col1 = 'published'
                   ) AS r
            FROM data_structpublish
        )
        SELECT rid, pid, status, rev
        FROM cte
        WHERE r  = 1
        ORDER BY pid ASC;";

        $latest = $sqlite->queryAll($sql);
        $rids = array_column($latest, 'rid');

        $sql = "UPDATE $this->table SET latest = 0 WHERE rid NOT IN (" . implode(', ', $rids) . ')';

        return (bool) $sqlite->query($sql);
    }
}
