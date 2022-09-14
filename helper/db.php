<?php

use dokuwiki\plugin\structpublish\meta\Assignments;

class helper_plugin_structpublish_db extends helper_plugin_struct_db
{

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
        return action_plugin_structpublish_sqlitefunction::userHasRole($pid, '', [], $roles);
    }

}
