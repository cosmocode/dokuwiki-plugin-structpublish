<?php

namespace dokuwiki\plugin\structpublish\meta;

/**
 * Class Assignments
 *
 * Manages the assignment of users to pages and namespaces
 * This is a singleton. Assignment data is only loaded once per request.
 *
 * @see \dokuwiki\plugin\struct\meta\Assignments
 */
class Assignments
{
    /** @var \helper_plugin_sqlite|null */
    protected $sqlite;

    /** @var  array All the assignments patterns */
    protected $patterns;

    /** @var Assignments */
    protected static $instance = null;

    /**
     * Get the singleton instance of the Assignments
     *
     * @param bool $forcereload create a new instace to reload the assignment data
     * @return Assignments
     */
    public static function getInstance($forcereload = false)
    {
        if (is_null(self::$instance) or $forcereload) {
            $class = get_called_class();
            self::$instance = new $class();
        }
        return self::$instance;
    }

    /**
     * Assignments constructor.
     *
     * Not public. Use Assignments::getInstance() instead
     */
    protected function __construct()
    {
        /** @var \helper_plugin_structpublish_db $helper */
        $helper = plugin_load('helper', 'struct_db');
        $this->sqlite = $helper->getDB();

        $this->loadPatterns();
    }

    /**
     * Load existing assignment patterns
     */
    protected function loadPatterns()
    {
        $sql = 'SELECT * FROM structpublish_assignments_patterns ORDER BY pattern';
        $res = $this->sqlite->query($sql);
        $this->patterns = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);
    }

    /**
     * Add a new assignment pattern to the pattern table
     *
     * @param string $pattern
     * @param string $user
     * @param string $status
     * @return bool
     */
    public function addPattern($pattern, $user, $status)
    {
        // add the pattern
        $sql = 'REPLACE INTO structpublish_assignments_patterns (pattern, user, status) VALUES (?,?,?)';
        $ok = (bool) $this->sqlite->query($sql, [$pattern, $user, $status]);

        // reload patterns
        $this->loadPatterns();

        // update assignments
        // fetch known pages
        /** @var \helper_plugin_structpublish_db $dbHelper */
        $dbHelper = plugin_load('helper', 'structpublish_db');
        $pids = $dbHelper->getPages();

        // wrap in transaction
        $this->sqlite->query('BEGIN TRANSACTION');

        foreach ($pids as $pid) {
            $this->updatePageAssignments($pid);
        }

        $ok = $ok && $this->sqlite->query('COMMIT TRANSACTION');
        if (!$ok) {
            $this->sqlite->query('ROLLBACK TRANSACTION');
        }

        return $ok;
    }

    /**
     * Remove an existing assignment pattern from the pattern table
     *
     * @param string $pattern
     * @param string $user
     * @param string $status
     * @return bool
     */
    public function removePattern($pattern, $user, $status)
    {
        // remove the pattern
        $sql = 'DELETE FROM structpublish_assignments_patterns WHERE pattern = ? AND user = ? AND status = ?';
        $ok = (bool) $this->sqlite->query($sql, [$pattern, $user, $status]);

        // reload patterns
        $this->loadPatterns();

        // fetch possibly affected pages
        $sql = 'SELECT pid FROM structpublish_assignments WHERE user = ? AND status = ?';
        $res = $this->sqlite->query($sql, [$user, $status]);
        $pagerows = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);

        // remove page assignments matching the pattern being removed
        $ok = true;
        foreach ($pagerows as $row) {
            $ok = $ok && $this->deassignPage($row['pid'], $user, $status);
        }

        return $ok;
    }

    /**
     * Updates all assignments of a given page against the current patterns
     *
     * @param string $pid
     */
    public function updatePageAssignments($pid, $reload = false)
    {
        if ($reload) {
            $this->loadPatterns();
        }
        $rules = $this->getPageAssignments($pid, true);

        foreach ($rules as $status => $users) {
            foreach ($users as $user) {
                $this->assignPage($pid, $user, $status);
            }
        }

        // FIXME reevalute existing assignments for exclusion
    }

    /**
     * Clear all patterns - deassigns all pages
     *
     * This is mostly useful for testing and not used in the interface currently
     *
     * @param bool $full fully delete all previous assignments
     * @return bool
     */
    public function clear($full = false)
    {
        $sql = 'DELETE FROM structpublish_assignments_patterns';
        $ok = (bool) $this->sqlite->query($sql);

        if ($full) {
            $sql = 'DELETE FROM structpublish_assignments';
        } else {
            $sql = 'UPDATE structpublish_assignments SET assigned = 0';
        }
        $ok = $ok && (bool) $this->sqlite->query($sql);

        // reload patterns
        $this->loadPatterns();

        return $ok;
    }

    /**
     * Add page to assignments
     *
     * @param string $page
     * @param string $user
     * @param string $status
     * @return bool
     */
    public function assignPage($page, $user = null, $status = null)
    {
        $sql = 'REPLACE INTO structpublish_assignments (pid, user, status, assigned) VALUES (?, ?, ?, 1)';
        return (bool) $this->sqlite->query($sql, [$page, $user, $status]);
    }

    /**
     * Remove page from assignments
     *
     * @param string $page
     * @param string $user
     * @return bool
     */
    public function deassignPage($page, $user, $status)
    {
        $sql = 'UPDATE structpublish_assignments SET assigned = 0 WHERE pid = ? AND user = ? AND status = ?';
        return (bool) $this->sqlite->query($sql, [$page, $user, $status]);
    }

    /**
     * Get the whole pattern table
     *
     * @return array
     */
    public function getAllPatterns()
    {
        return $this->patterns;
    }

    /**
     * Returns a list of user/group string lists per status assigned to the given page
     *
     * @param string $page
     * @param bool $checkpatterns Should the current patterns be re-evaluated?
     * @return array users assigned [role => [user, ...], ...]
     */
    public function getPageAssignments($page, $checkpatterns = true)
    {
        $rules = [];
        $page = cleanID($page);

        if ($checkpatterns) {
            $helper = plugin_load('helper', 'structpublish_assignments');
            // evaluate patterns
            $pns = ':' . getNS($page) . ':';
            foreach ($this->patterns as $row) {
                if ($helper->matchPagePattern($row['pattern'], $page, $pns)) {
                    $rules[$row['status']][] = $row['user'];
                }
            }
        } else {
            // just select
            $sql = 'SELECT user, status FROM structpublish_assignments WHERE pid = ? AND assigned = 1';
            $res = $this->sqlite->query($sql, [$page]);
            $list = $this->sqlite->res2arr($res);
            $this->sqlite->res_close($res);
            foreach ($list as $row) {
                $rules[$row['status']][] = $row['user'];
            }
        }

        return $rules;
    }

    /**
     * Get the pages known to struct and their assignment state
     *
     * @param bool $assignedonly limit results to currently assigned only
     * @return array
     */
    public function getPages($assignedOnly = false)
    {
        $sql = 'SELECT pid, user, status, assigned FROM structpublish_assignments WHERE 1=1';

        $opts = array();

        if ($assignedOnly) {
            $sql .= ' AND assigned = 1';
        }

        $sql .= ' ORDER BY pid, user, status';

        $res = $this->sqlite->query($sql, $opts);
        $list = $this->sqlite->res2arr($res);
        $this->sqlite->res_close($res);

        $result = array();
        foreach ($list as $row) {
            $pid = $row['pid'];
            $user = $row['user'];
            $status = $row['status'];
            if (!isset($result[$pid])) {
                $result[$pid] = array();
            }
            $result[$pid][$user][$status] = (bool) $row['assigned'];
        }

        return $result;
    }

    /**
     * @return \helper_plugin_sqlite|null
     */
    public function getSqlite()
    {
        return $this->sqlite;
    }
}
