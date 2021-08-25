<?php

namespace dokuwiki\plugin\structpublish\meta;

class Revision
{
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';

    protected $sqlite;
    protected $schemas;
    protected $id;
    protected $rev;
    protected $status;
    protected $version = 0;
    protected $user;

    /**
     * @param $sqlite
     * @param string $id
     * @param int $rev
     */
    public function __construct($sqlite, $id, $rev)
    {
        $this->sqlite = $sqlite;
        $this->id = $id;
        $this->rev = $rev;

        $sql = 'SELECT * FROM structpublish_revisions WHERE id = ? AND rev = ?';
        $res = $sqlite->query($sql, $id, $rev);
        $vals = $sqlite->res2row($res);

        if (!empty($vals)) {
            $this->status = $vals['status'];
            $this->version = $vals['version'];
            $this->user = $vals['user'];
        }
    }

    public function save()
    {
        // TODO reset publish status of older revisions
        $sql = 'REPLACE INTO structpublish_revisions (id, rev, status, version, user) VALUES (?,?,?,?,?)';
        $res = $this->sqlite->query(
            $sql,
            $this->id,
            $this->rev,
            $this->status,
            $this->version,
            $this->user
        );

        if ($this->status === self::STATUS_PUBLISHED) {
            $this->updateCoreData();
        }
    }

    /**
     * Returns the latest version for a given id, or 0
     *
     * @return int
     */
    public function getLatestVersion()
    {
        $sql = 'SELECT MAX(version) AS latest FROM structpublish_revisions WHERE id = ?';
        $res = $this->sqlite->query($sql, $this->id);
        $res = $this->sqlite->res2arr($res);
        return $res['latest'] ?? 0;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version): void
    {
        $this->version = $version;
    }

    /**
     * @return int
     */
    public function getRev()
    {
        return $this->rev;
    }

    /**
     * @param int $rev
     */
    public function setRev($rev): void
    {
        $this->rev = $rev;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status ?? self::STATUS_DRAFT;
    }

    /**
     * @param string $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * Update publish status in the core table
     */
    protected function updateCoreData()
    {
        // FIXME we don't know anything about schemas yet!
//        $sql = 'UPDATE data_schema SET published = NULL WHERE id = ?';
//        $this->sqlite->query($sql, $this->id);
    }
}
