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

    protected $schema;


    protected $id;
    protected $rev;
    protected $status;
    protected $version;
    protected $user;
    protected $date;
    /**
     * @var bool|\dokuwiki\plugin\struct\meta\Column
     */
    protected $statusCol;
    protected $versionCol;
    protected $userCol;
    protected $dateCol;
    protected $revisionCol;

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

        $this->schema = new Schema('structpublish');
        $this->statusCol = $this->schema->findColumn('status');
        $this->versionCol = $this->schema->findColumn('version');
        $this->userCol = $this->schema->findColumn('user');
        $this->dateCol = $this->schema->findColumn('date');
        $this->revisionCol = $this->schema->findColumn('revision');

        /** @var Value[] $values */
        $values = $this->getCoreData('revision=' . $this->rev);

        if (!empty($values)) {
            $this->status = $values[$this->statusCol->getColref() - 1]->getRawValue();
            $this->version = $values[$this->versionCol->getColref() - 1]->getRawValue();
            $this->user = $values[$this->userCol->getColref() - 1]->getRawValue();
            $this->date = $values[$this->dateCol->getColref() - 1]->getRawValue();
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

    public function getCoreData($andFilter = '')
    {
        $lines = [
            'schema: structpublish',
            'cols: *',
            'filter: %pageid% = $ID$'
        ];

        if ($andFilter) {
            $lines[] = 'filter: ' . $andFilter;
        }

        $parser = new ConfigParser($lines);
        $config = $parser->getConfig();
        $search = new SearchConfig($config, $this->sqlite);
        $data = $search->execute();
        if (!empty($data)) {
            // FIXME
            return $data[array_key_last($data)];
        }
        return [];
    }

    public function getLatestPublished()
    {
        return $this->getCoreData('status=' . self::STATUS_PUBLISHED);
    }

    public function getLatestPublishedRev()
    {
        return $this->getLatestPublished()[$this->revisionCol->getColref() - 1]->getRawValue();
    }
}
