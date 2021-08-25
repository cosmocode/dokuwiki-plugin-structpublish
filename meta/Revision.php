<?php

namespace dokuwiki\plugin\structpublish\meta;

class Revision
{
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';

    protected $sqlite;
    protected $id;
    protected $rev;
    protected $status;
    protected $version;
    protected $user;

    /**
     * @param $sqlite
     * @param $id
     * @param null $rev
     */
    public function __construct($sqlite, $id, $rev = null)
    {
        $this->sqlite = $sqlite;
        $this->id = $id;
        $this->rev = $rev;

        // FIXME check revision too
        $sql = 'SELECT * FROM structpublish_revisions WHERE id = ? ORDER BY rev LIMIT 1';
        $res = $sqlite->query($sql, $id);
        $vals = $sqlite->res2row($res);
        $this->rev = $vals['rev'] ?? null;
        $this->status = $vals['status'] ?? null;
        $this->version = $vals['version'] ?? 0;
        $this->user = $vals['user'] ?? null;
    }

    public function save()
    {
        $sql = 'INSERT INTO structpublish_revisions (id, rev, status, version, user) VALUES (?,?,?,?,?)';
        $res = $this->sqlite->query(
            $sql,
            $this->id,
            $this->rev,
            $this->status,
            $this->version,
            $this->user
        );
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version): void
    {
        $this->version = $version;
    }

    /**
     * @return mixed|null
     */
    public function getRev()
    {
        return $this->rev;
    }

    /**
     * @param mixed|null $rev
     */
    public function setRev($rev): void
    {
        $this->rev = $rev;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status ?? self::STATUS_DRAFT;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }
}
