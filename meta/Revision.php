<?php

namespace dokuwiki\plugin\structpublish\meta;

use dokuwiki\plugin\struct\meta\ConfigParser;
use dokuwiki\plugin\struct\meta\Schema;
use dokuwiki\plugin\struct\meta\SearchConfig;
use dokuwiki\plugin\struct\meta\Value;

class Revision
{
    /** @var \helper_plugin_sqlite */
    protected $sqlite;

    protected $schema;

    protected $id;
    protected $rev;
    protected $published;
    protected $status;
    protected $version;
    protected $user;
    protected $datetime;
    /**
     * @var bool|\dokuwiki\plugin\struct\meta\Column
     */
    protected $statusCol;
    protected $versionCol;
    protected $userCol;
    protected $datetimeCol;
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
        $this->published = 0;
        $this->status = Constants::STATUS_DRAFT;

        $this->schema = new Schema('structpublish');
        $this->statusCol = $this->schema->findColumn('status');
        $this->versionCol = $this->schema->findColumn('version');
        $this->userCol = $this->schema->findColumn('user');
        $this->datetimeCol = $this->schema->findColumn('datetime');
        $this->revisionCol = $this->schema->findColumn('revision');

        /** @var Value[] $values */
        $values = $this->getCoreData('revision=' . $this->rev);

        if (!empty($values)) {
            $this->status = $values[$this->statusCol->getColref() - 1]->getRawValue();
            $this->version = $values[$this->versionCol->getColref() - 1]->getRawValue();
            $this->user = $values[$this->userCol->getColref() - 1]->getRawValue();
            $this->datetime = $values[$this->datetimeCol->getColref() - 1]->getRawValue();
        }
    }

    public function save()
    {
        if ($this->status === Constants::STATUS_PUBLISHED) {
            $this->published = 1;
        }

        $this->updateCoreData($this->id);
        // TODO reset publish status of older revisions

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

    public function getDatetime()
    {
        return $this->datetime;
    }

    public function setDatetime($time)
    {
        $this->datetime = date('Y-m-d H:i', $time);
    }

    /**
     * Update publish status in the core table
     */
    protected function updateCoreData($pid, $rid = 0)
    {
        $data = [
            'status' => $this->status,
            'user' => $this->user,
            'date' => $this->datetime,
            'revision' => $this->rev,
            'version' => $this->version,
        ];
        $schema = new Schema('structpublish', 0);
        $access = new AccessTableStructpublish($schema, $pid, 0, $rid);
        $access->setPublished($this->published);
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

    /**
     * Return "latest" published revision of a given page.
     * If $rev is specified, "latest" means relative to the $rev revision.
     *
     * @param int|null $rev
     * @return Revision|null
     */
    public function getLatestPublishedRevision($rev = null)
    {
        $andFilter = 'status=' . Constants::STATUS_PUBLISHED;
        if ($rev) {
            $andFilter .= ' AND revision < ' . $rev;
        }
        $latestPublished = $this->getCoreData($andFilter);

        if (empty($latestPublished)) {
            return null;
        }

        $published = new Revision($this->sqlite, $this->id, $latestPublished[$this->revisionCol->getColref() - 1]->getRawValue());

        $published->setStatus($latestPublished[$this->statusCol->getColref() - 1]->getRawValue());
        $published->setUser($latestPublished[$this->userCol->getColref() - 1]->getRawValue());
        $published->setDatetime($latestPublished[$this->datetimeCol->getColref() - 1]->getRawValue());
        $published->setVersion($latestPublished[$this->versionCol->getColref() - 1]->getRawValue());

        return $published;
    }
}
