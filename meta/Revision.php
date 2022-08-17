<?php

namespace dokuwiki\plugin\structpublish\meta;

use dokuwiki\plugin\struct\meta\ConfigParser;
use dokuwiki\plugin\struct\meta\Schema;
use dokuwiki\plugin\struct\meta\SearchConfig;
use dokuwiki\plugin\struct\meta\Value;

class Revision
{
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';

    /** @var \helper_plugin_sqlite */
    protected $sqlite;
    protected $schemas;
    protected $id;
    protected $rev;
    protected $status;
    protected $version;
    protected $user;
    protected $date;

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

        $schema = new Schema('structpublish');
        $statusCol = $schema->findColumn('status');
        $versionCol = $schema->findColumn('version');
        $userCol = $schema->findColumn('user');
        $dateCol = $schema->findColumn('date');

        /** @var Value[] $values */
        $values = $this->getCoreData($id);

        if (!empty($values)) {
            $this->status = $values[$statusCol->getColref() - 1]->getRawValue();
            $this->version = $values[$versionCol->getColref() - 1]->getRawValue();
            $this->user = $values[$userCol->getColref() - 1]->getRawValue();
            $this->date = $values[$dateCol->getColref() - 1]->getRawValue();
        }
    }

    public function save()
    {
        // drafts reference the latest version
        if ($this->status === self::STATUS_DRAFT) {
            //FIXME no rev yet
            $this->setVersion($this->getVersion());
        }

        // TODO reset publish status of older revisions

            $this->updateCoreData($this->id);
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return (int)$this->version;
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
        return $this->status;
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

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($time)
    {
        $this->date = date('Y-m-d', $time);
    }

    /**
     * Update publish status in the core table
     */
    protected function updateCoreData($pid, $rid = 0)
    {
        $data = [
            'status' => $this->status,
            'user' => $this->user,
            'date' => $this->date,
            'revision' => $this->rev,
            'version' => $this->version,
        ];
        $schema = new Schema('structpublish', 0);
        $access = new AccessTableStructpublish($schema, $pid, 0, $rid);
        $access->saveData($data);
    }

    public function getCoreData($id)
    {
        $lines = [
            'schema: structpublish',
            'cols: *',
            'filter: %pageid% = $ID$'
        ];
        $parser = new ConfigParser($lines);
        $config = $parser->getConfig();
        $search = new SearchConfig($config);
        $data = $search->execute();
        if (!empty($data)) {
            return $data[array_key_last($data)];
        }
        return [];
    }
}
