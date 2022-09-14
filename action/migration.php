<?php

class action_plugin_structpublish_migration extends DokuWiki_Action_Plugin
{
    const MIN_DB_STRUCT = 19;

    /**
     * @inheritDoc
     */
    public function register(Doku_Event_Handler $controller)
    {
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

        // abort if struct is not installed
        if (!$helper) {
            throw new Exception('Plugin struct is required!');
        }

        $sqlite = $helper->getDB();

        $ok = true;

        list($dbVersionStruct, $dbVersionStructpublish) = $this->getDbVersions($sqlite);

        // check if struct has required version
        if ($dbVersionStruct < self::MIN_DB_STRUCT) {
            throw new Exception('Plugins struct is outdated. Minimum required database version is ' . self::MIN_DB_STRUCT);
        }

        // check whether we are already up-to-date
        $latestVersion = $this->getLatestVersion();
        if (isset($dbVersionStructpublish) && (int)$dbVersionStructpublish >= $latestVersion) {
            return $ok;
        }

        // check whether we have any pending migrations
        $pending = range($dbVersionStructpublish ?: 1, $latestVersion);
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
     * @return int
     */
    protected function getLatestVersion()
    {
        return (int)trim(file_get_contents(DOKU_PLUGIN . 'structpublish/db/latest.version', false));
    }

    /**
     * Database setup
     *
     * @param helper_plugin_sqlite $sqlite
     * @return bool
     */
    protected function migration1($sqlite)
    {
        $sql = io_readFile(DOKU_PLUGIN . 'structpublish/db/update0001.sql', false);

        $sql = $sqlite->SQLstring2array($sql);
        array_unshift($sql, 'BEGIN TRANSACTION');
        array_push($sql, "INSERT OR REPLACE INTO opts (val,opt) VALUES (1,'dbversion_structpublish')");
        array_push($sql, "COMMIT TRANSACTION");
        $ok =  $sqlite->doTransaction($sql);

        if ($ok) {
            $file = DOKU_PLUGIN . 'structpublish/db/json/structpublish0001.struct.json';
            $schemaJson = file_get_contents($file);
            $importer = new \dokuwiki\plugin\struct\meta\SchemaImporter('structpublish', $schemaJson);
            $ok = (bool)$importer->build();
        }

        return $ok;
    }
}
