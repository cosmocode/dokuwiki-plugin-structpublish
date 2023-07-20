<?php

namespace dokuwiki\plugin\structpublish\meta;

use dokuwiki\plugin\struct\meta\AccessTable;
use dokuwiki\plugin\struct\meta\AccessTableSerial;

/**
 * Class AccessTableStructpublish
 *
 * Load and save publish data
 *
 * @package dokuwiki\plugin\struct\meta
 */
class AccessTableStructpublish extends AccessTableSerial
{
    protected $published = 0;

    /**
     * @param 0|1|bool $published
     * @return void
     */
    public function setPublished($published)
    {
        $this->published = (int) $published;
    }

    /** @inheritDoc */
    protected function getSingleSql()
    {
        $cols = array_merge($this->getSingleNoninputCols(), $this->singleCols);
        $cols = join(',', $cols);
        $vals = array_merge($this->getSingleNoninputValues(), $this->singleValues);
        $rid = $this->getRid() ?: "(SELECT (COALESCE(MAX(rid), 0 ) + 1) FROM $this->stable)";

        return "REPLACE INTO $this->stable (rid, $cols)
                      VALUES ($rid," . trim(str_repeat('?,', count($vals)), ',') . ');';
    }

    /** @inheritDoc */
    protected function getMultiSql()
    {
        return '';
    }

    /** @inheritDoc */
    protected function getSingleNoninputCols()
    {
        return ['pid', 'rev', 'latest', 'published'];
    }

    /** @inheritDoc */
    protected function getSingleNoninputValues()
    {
        return [$this->pid, AccessTable::DEFAULT_REV, AccessTable::DEFAULT_LATEST, $this->published];
    }

    /**
     * @inheritdoc
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
        $ret = $this->sqlite->queryValue($sql, $opts);

        // make sure we don't cast empty result to 0 (serial data has rev = 0)
        if ($ret !== false) {
            $ret = (int) $ret;
        }
        return $ret;
    }

    /**
     * Remove latest status from previous publish data
     * for the current page id
     */
    protected function beforeSave()
    {
        /** @noinspection SqlResolve */
        return $this->sqlite->query(
            "UPDATE $this->stable SET latest = 0 WHERE latest = 1 AND pid = ?",
            [$this->pid]
        );
    }
}
