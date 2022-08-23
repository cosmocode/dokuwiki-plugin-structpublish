<?php

use dokuwiki\plugin\structpublish\meta\Assignments;

class helper_plugin_structpublish_db extends helper_plugin_struct_db
{
    const ACTION_APPROVE = 'approve';
    const ACTION_PUBLISH = 'publish';

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
     * Overwrites dummy IS_PUBLISHER from struct plugin
     * Required argument: pid
     * Expected arguments: user, grps; default to current user and their groups
     *
     * Returns true if user/group may see unpublished revisions of a page
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

        return $this->userHasRole(
            [self::ACTION_PUBLISH, self::ACTION_APPROVE],
            $userId,
            $grps,
            $pid
        );
    }

    protected function userHasRole($roles, $userId, $grps, $pid)
    {
        $assignments = Assignments::getInstance();
        $rules = $assignments->getPageAssignments($pid);

        foreach ($roles as $role) {
            if (isset($rules[$role])) {
                $users = $rules[self::ACTION_PUBLISH];
                if (auth_isMember(implode(',', $users), $userId, $grps)) {
                    return true;
                }
            }
        }

        return false;
    }
}
