<?php

use dokuwiki\plugin\structpublish\meta\Assignments;

class helper_plugin_structpublish_db extends DokuWiki_Plugin
{
    protected $initialized = false;

    /**
     * Access the struct database
     *
     * Registers our own IS_PUBLISHER function with sqlite
     *
     * @return \dokuwiki\plugin\sqlite\SQLiteDB|null
     */
    public function getDB()
    {
        /**
 * @var helper_plugin_struct_db $struct 
*/
        $struct = plugin_load('helper', 'struct_db');
        if (!$struct) {
            // FIXME show message?
            return null;
        }
        $sqlite = $struct->getDB(false);
        if (!$sqlite) {
            return null;
        }

        // on init
        if (!$this->initialized) {
            $sqlite->getPdo()->sqliteCreateFunction('IS_PUBLISHER', [$this, 'isPublisher'], -1);
            $this->initialized = true;
        }

        return $sqlite;
    }

    /**
     * Get list of all pages known to the plugin
     *
     * @return array
     */
    public function getPages()
    {
        $sqlite = $this->getDB();
        if (!$sqlite) {
            return [];
        }

        $sql = 'SELECT pid FROM titles';
        $list = $sqlite->queryAll($sql);
        return array_column($list, 'pid');
    }

    /**
     * Returns true if the given page is included in publishing workflows.
     * If no pid is given, check current page.
     *
     * @return bool
     */
    public function isPublishable($pid = null)
    {
        global $ID;
        $sqlite = $this->getDB();
        if (!$sqlite) {
            return false;
        }

        if (!$pid) {
            $pid = $ID;
        }

        $sql = 'SELECT pid FROM structpublish_assignments WHERE pid = ? AND assigned = 1';
        return (bool) $sqlite->queryAll($sql, $pid);
    }

    /**
     * Check if the current user has the given roles on the current page
     *
     * @param  string   $pid   The page ID to check access for
     * @param  string[] $roles Roles needed. Empty for any role
     * @return bool
     */
    public function checkAccess($pid, $roles = [])
    {
        return self::userHasRole($pid, '', [], $roles);
    }

    /**
     * Function registered in SQLite
     *
     * Params are read via function args
     *
     * @param  ...string $pid, $userId, $groups...
     * @return int Return an integer instead of boolean for better sqlite compatibility
     */
    public function isPublisher()
    {

        global $USERINFO;
        global $INPUT;

        $args = func_get_args();
        $pid = $args[0];

        if (!$pid || !$this->isPublishable($pid)) {
            return 1;
        }

        $userId = $args[1] ?? $INPUT->server->str('REMOTE_USER');
        $grps = $args[2] ?? ($USERINFO['grps'] ?? []);

        return (int)$this->userHasRole(
            $pid,
            $userId,
            $grps
        );
    }

    /**
     * Check if a given user has role assignment for a given page
     *
     * @param  string   $pid    Page to check
     * @param  string   $userId User login name, current user if empty
     * @param  string[] $grps   Groups the user has, current user's groups if empty user
     * @param  string[] $roles  Roles the user should have, empty for any role
     * @return bool
     */
    public static function userHasRole($pid, $userId = '', $grps = [], $roles = [])
    {
        global $INPUT;
        global $USERINFO;

        if (blank($userId)) {
            $userId = $INPUT->server->str('REMOTE_USER');
            $grps = $USERINFO['grps'] ?? [];
        }

        $assignments = Assignments::getInstance();
        $rules = $assignments->getPageAssignments($pid);

        // if no roles are given, any role is fine
        if (empty($roles)) {
            return auth_isMember(
                implode(',', array_merge(...array_values($rules))),
                $userId,
                $grps
            );
        }

        foreach ($roles as $role) {
            if (isset($rules[$role])) {
                $users = $rules[$role];
                if (auth_isMember(implode(',', $users), $userId, $grps)) {
                    return true;
                }
            }
        }

        return false;
    }
}
