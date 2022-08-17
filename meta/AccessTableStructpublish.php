<?php

namespace dokuwiki\plugin\structpublish\meta;

use dokuwiki\plugin\struct\meta\AccessTableSerial;

/**
 * Class AccessTableStructpublish
 *
 * Load and save serial data
 *
 * @package dokuwiki\plugin\struct\meta
 */
class AccessTableStructpublish extends AccessTableSerial
{
    public function __construct($table, $pid, $ts = 0, $rid = 0)
    {
        parent::__construct($table, $pid, $ts, $rid);
    }

    /**
     * @inheritDoc
     */
    protected function getSingleSql()
    {
        $cols = array_merge($this->getSingleNoninputCols(), $this->singleCols);
        $cols = join(',', $cols);
        $vals = array_merge($this->getSingleNoninputValues(), $this->singleValues);
        $rid = $this->getRid() ?: "(SELECT (COALESCE(MAX(rid), 0 ) + 1) FROM $this->stable)";

        return "REPLACE INTO $this->stable (rid, $cols)
                      VALUES ($rid," . trim(str_repeat('?,', count($vals)), ',') . ');';
    }

    /**
     * @inheritDoc
     */
    protected function getMultiSql()
    {
        return '';
    }

    /**
     * @return int|bool
     */
    protected function getLastRevisionTimestamp()
    {
        $table = 'data_structpublish';
        $where = "WHERE pid = ?";
        $opts = [$this->pid];
        if ($this->ts) {
            $where .= " AND REV > 0 AND rev <= ?";
            $opts[] = $this->ts;
        }

        /** @noinspection SqlResolve */
        $sql = "SELECT rev FROM $table $where ORDER BY rev DESC LIMIT 1";
        $res = $this->sqlite->query($sql, $opts);
        $ret = $this->sqlite->res2single($res);
        $this->sqlite->res_close($res);
        // make sure we don't cast empty result to 0 (serial data has rev = 0)
        if ($ret !== false) $ret = (int)$ret;
        return $ret;
    }
}
