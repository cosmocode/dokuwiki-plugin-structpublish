<?php

use dokuwiki\plugin\structpublish\meta\Assignments;

/**
 * @todo by extending helper_plugin_struct_db we break the singlton pattern and have two database connections
 */
class helper_plugin_structpublish_db extends helper_plugin_struct_db
{
    /** @inheritdoc */
    protected function init()
    {
        parent::init();
        if ($this->sqlite) {
            $this->sqlite->create_function('IS_PUBLISHER', [$this, 'isPublisher'], -1);
        }
    }

    /**
     * Get list of all pages known to the plugin
     * @return array
     */
    public function getPages()
    {
        $sql = 'SELECT pid FROM titles';
        $res = $this->sqlite->query($sql);
        $list = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        return array_column($list, 'pid');
    }

    /**
     * Returns true if the current page is included in publishing workflows
     *
     * @return bool
     */
    public function isPublishable()
    {
        global $ID;

        $sql = 'SELECT pid FROM structpublish_assignments WHERE pid = ? AND assigned = 1';
        $res = $this->sqlite->query($sql, $ID);
        if ($res && $this->sqlite->res2count($res)) {
            return true;
        }
        return false;
    }

    /**
     * Check if the current user has the given roles on the current page
     *
     * @param string $pid The page ID to check access for
     * @param string[] $roles Roles needed. Empty for any role
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
     * @param ...string $pid, $userId, $groups...
     * @return int Return an integer instead of boolean for better sqlite compatibility
     */
    public function isPublisher()
    {
        if (!$this->isPublishable()) return 1;

        global $USERINFO;
        global $INPUT;

        $args = func_get_args();
        $pid = $args[0];
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
     * @param string $pid Page to check
     * @param string $userId User login name, current user if empty
     * @param string[] $grps Groups the user has, current user's groups if empty user
     * @param string[] $roles Roles the user should have, empty for any role
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
