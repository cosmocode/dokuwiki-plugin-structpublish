<?php

class action_plugin_structpublish_migration extends DokuWiki_Action_Plugin
{
    /**
     * @inheritDoc
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PLUGIN_SQLITE_DATABASE_UPGRADE', 'BEFORE', $this, 'handleMigrations');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleMigrations');
    }

    /**
     * Call our custom migrations when defined
     *
     * @param Doku_Event $event
     * @return bool
     */
    public function handleMigrations(Doku_Event $event)
    {
        /** @var \helper_plugin_struct_db $helper */
        $helper = plugin_load('helper', 'struct_db');
        $sqlite = $helper->getDB();

        $ok = true;

        // check whether we are already up-to-date
        list($dbVersionStruct, $dbVersionStructpublish) = $this->getDbVersions($sqlite);
        if (isset($dbVersionStructpublish) && (string)$dbVersionStructpublish == (string)$dbVersionStruct) {
            return $ok;
        }

        // check whether we have any pending migrations for the current version of struct db
        $pending = array_filter(array_map(function ($version) use ($dbVersionStruct) {
            return $version >= $dbVersionStruct &&
            is_callable([$this, "migration$version"]) ? $version : null;
        }, $this->diffVersions($dbVersionStruct, $dbVersionStructpublish)));
        if (empty($pending)) {
            return $ok;
        }

        // execute the migrations
        foreach ($pending as $version) {
            $call = 'migration' . $version;
            $ok = $ok && $this->$call($sqlite);
        }

        return $ok;
    }

    /**
     * Detect which migrations should be executed. Start conservatively with version 1.
     *
     * @param int $dbVersionStruct Current version of struct DB as found in 'opts' table
     * @param int|null $dbVersionStructpublish Current version in 'opts', may not exist yet
     * @return int[]
     */
    protected function diffVersions($dbVersionStruct, $dbVersionStructpublish)
    {
        $pluginDbVersion = $dbVersionStructpublish ?: 1;
        return range($pluginDbVersion, $dbVersionStruct);
    }

    /**
     * @param $sqlite
     * @return array
     */
    protected function getDbVersions($sqlite)
    {
        $dbVersionStruct = null;
        $dbVersionStructpublish = null;

        $sql = 'SELECT opt, val FROM opts WHERE opt=? OR opt=?';
        $res = $sqlite->query($sql, 'dbversion', 'dbversion_structpublish');
        $vals = $sqlite->res2arr($res);

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
     * Database setup, required struct db version is 19
     *
     * @param helper_plugin_sqlite $sqlite
     * @return bool
     */
    protected function migration19($sqlite)
    {
        $sql = io_readFile(DOKU_PLUGIN . 'structpublish/db/struct/update0019.sql', false);

        $sql = $sqlite->SQLstring2array($sql);
        array_unshift($sql, 'BEGIN TRANSACTION');
        array_push($sql, "INSERT OR REPLACE INTO opts (val,opt) VALUES (19,'dbversion_structpublish')");
        array_push($sql, "COMMIT TRANSACTION");
        $ok =  $sqlite->doTransaction($sql);

        if ($ok) {
            $file = __DIR__ . "../db/json/structpublish_19.struct.json";
            $schemaJson = file_get_contents($file);
            $importer = new \dokuwiki\plugin\struct\meta\SchemaImporter('structpublish', $schemaJson);
            $ok = (bool)$importer->build();
        }

        return $ok;
    }
}
