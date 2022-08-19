<?php

use dokuwiki\plugin\structpublish\meta\Assignments;

class helper_plugin_structpublish_db extends helper_plugin_struct_db
{
    /**
     * Get list of all pages known to the plugin
     * @return array
     */
    public function getPages($pid = null)
    {
        $sql = 'SELECT pid FROM data_structpublish';
        if ($pid) {
            $sql .= ' WHERE pid = ?';
        }
        $res = $this->sqlite->query($sql, $pid);
        $list = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
        return $list;
    }

    /**
     * Overwrites dummy IS_PUBLISHER from struct plugin
     * Required argument: pid
     * Expected arguments: user, grps; default to current user and their groups
     *
     * Returns true if user/group matches the 'publish' rule for the given page
     *
     * @return bool
     */
    public function IS_PUBLISHER() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        global $USERINFO;
        global $INPUT;

        $args = func_get_args();
        $pid = $args[0];
        $userId = $args[1] ?? $INPUT->server->str('REMOTE_USER');
        $grps = $args[2] ?? ($USERINFO['grps'] ?? []);

        $role = helper_plugin_structpublish_permissions::ACTION_PUBLISH;

        return $this->isRoleAllowed($role, $pid, $userId, $grps);
    }

    /**
     * Check for approver role
     * @see \helper_plugin_structpublish_db::IS_PUBLISHER
     * @FIXME This is method is not used yet.
     *
     * Required argument: pid
     * Expected arguments: user, grps; default to current user and their groups
     *
     * Returns true if user/group matches the 'approve' rule for the given page
     *
     * @return bool
     */
    public function IS_APPROVER() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        global $USERINFO;
        global $INPUT;

        $args = func_get_args();
        $pid = $args[0];
        $userId = $args[1] ?? $INPUT->server->str('REMOTE_USER');
        $grps = $args[2] ?? ($USERINFO['grps'] ?? []);

        $role = helper_plugin_structpublish_permissions::ACTION_APPROVE;

        return $this->isRoleAllowed($role, $pid, $userId, $grps);
    }

    protected function isRoleAllowed($role, $pid, $userId, $grps)
    {
        $assignments = Assignments::getInstance();
        $rules = $assignments->getPageAssignments($pid);

        if (isset($rules[$role])) {
            $users = $rules[helper_plugin_structpublish_permissions::ACTION_PUBLISH];
            if (auth_isMember(implode(',', $users), $userId, $grps)) {
                return true;
            }
        }

        return false;
    }
}
