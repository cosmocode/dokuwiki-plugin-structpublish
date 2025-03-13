<?php

namespace dokuwiki\plugin\structpublish\meta;

use dokuwiki\plugin\sqlite\SQLiteDB;
use dokuwiki\plugin\struct\meta\ConfigParser;
use dokuwiki\plugin\struct\meta\Schema;
use dokuwiki\plugin\struct\meta\SearchConfig;
use dokuwiki\plugin\struct\meta\Value;

/**
 * Object representing a page revision and its properties
 */
class Revision
{
    /**
     * @var SQLiteDB 
     */
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
     * Constructor
     *
     * @param string $id  page id
     * @param int    $rev revision
     */
    public function __construct($id, $rev)
    {
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

        /**
 * @var Value[] $values 
*/
        $values = $this->getCoreData(['revision=' . $this->rev]);

        if (!empty($values)) {
            $this->status = $values[$this->statusCol->getColref() - 1]->getRawValue();
            $this->version = $values[$this->versionCol->getColref() - 1]->getRawValue();
            $this->user = $values[$this->userCol->getColref() - 1]->getRawValue();
            $this->datetime = $values[$this->datetimeCol->getColref() - 1]->getRawValue();
        }
    }

    /**
     * Store the currently set structpublish meta data in the database
     *
     * @return void
     */
    public function save()
    {
        if ($this->status === Constants::STATUS_PUBLISHED) {
            $this->published = 1;
        }

        $this->updateCoreData($this->id);
    }

    /**
     * Return the version of a published revision
     *
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the version of a published revision
     *
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * The revision timestamp
     *
     * @return int
     */
    public function getRev()
    {
        return $this->rev;
    }

    /**
     * Get the current status of this revision
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the current status of this revision
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get the user that changed the status of this revision
     *
     * Not available for drafts
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user that changed the revision status
     *
     * @param string $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * The datetime when the status of this revision was changed
     *
     * Uses ISO Format. Not available for drafts
     *
     * @return string|null
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * The timestamp of when the status of this revision was changed
     *
     * Not available for drafts
     *
     * @return int|null
     */
    public function getTimestamp()
    {
        if ($this->datetime === null) {
            return null;
        }
        return strtotime($this->datetime);
    }

    /**
     * Set the datetime when the status of this revision was changed
     *
     * Uses ISO Format
     *
     * @param string $time
     */
    public function setDatetime($time)
    {
        $this->datetime = $time;
    }

    /**
     * Set the timestamp of when the status of this revision was changed
     */
    public function setTimestamp($timestamp)
    {
        $this->datetime = date('Y-m-d\TH:i', $timestamp);
    }

    /**
     * The page ID this revision is for
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Update publish status in the core table
     *
     * @param string $pid
     * @param int    $rid
     */
    protected function updateCoreData($pid, $rid = 0)
    {
        $data = [
            'status' => $this->status,
            'user' => $this->user,
            'datetime' => $this->datetime,
            'revision' => $this->rev,
            'version' => $this->version,
        ];
        $schema = new Schema('structpublish', 0);
        $access = new AccessTableStructpublish($schema, $pid, 0, $rid);
        $access->setPublished($this->published);
        $access->saveData($data);
    }

    /**
     * Fetches data from the structpublish schema for the current page.
     * Returns an array of struct Value objects, not literal values.
     * $andFilters can be used to limit the search, e.g. by status or revision
     *
     * @see https://www.dokuwiki.org/plugin:struct:filters
     *
     * @param  array $andFilters
     * @return array|Value[]
     */
    public function getCoreData($andFilters = [])
    {
        $lines = [
            'schema: structpublish',
            'cols: *',
            'sort: revision',
            'filter: %pageid% = $ID$'
        ];

        if (!empty($andFilters)) {
            foreach ($andFilters as $filter) {
                $lines[] = 'filter: ' . $filter;
            }
        }

        $parser = new ConfigParser($lines);
        $config = $parser->getConfig();
        $search = new SearchConfig($config);
        // disable 'latest' flag in select query
        $search->setSelectLatest(false);
        $data = $search->execute();
        if (!empty($data)) {
            return array_pop($data);
        }
        return [];
    }

    /**
     * Return "latest" published revision of a given page.
     * If $rev is specified, "latest" means relative to the $rev revision.
     *
     * @param  int|null $rev
     * @return Revision|null
     */
    public function getLatestPublishedRevision($rev = null)
    {
        $andFilters[] = 'status=' . Constants::STATUS_PUBLISHED;
        if ($rev) {
            $andFilters[] .= 'revision<' . $rev;
        }
        $latestPublished = $this->getCoreData($andFilters);

        if (empty($latestPublished)) {
            return null;
        }

        $published = new Revision(
            $this->id,
            $latestPublished[$this->revisionCol->getColref() - 1]->getRawValue()
        );

        $published->setStatus($latestPublished[$this->statusCol->getColref() - 1]->getRawValue());
        $published->setUser($latestPublished[$this->userCol->getColref() - 1]->getRawValue());
        $published->setDatetime($latestPublished[$this->datetimeCol->getColref() - 1]->getRawValue());
        $published->setVersion($latestPublished[$this->versionCol->getColref() - 1]->getRawValue());

        return $published;
    }

    /**
     * Return "latest" approved revision of a given page.
     * If $rev is specified, "latest" means relative to the $rev revision.
     *
     * @param  int|null $rev
     * @return Revision|null
     */
    public function getLatestApprovedRevision($rev = null)
    {
        $andFilters[] = 'status=' . Constants::STATUS_APPROVED;
        if ($rev) {
            $andFilters[] .= 'revision=' . $rev;
        }
        $latestApproved = $this->getCoreData($andFilters);

        if (empty($latestApproved)) {
            return null;
        }

        $approved = new Revision(
            $this->id,
            $latestApproved[$this->revisionCol->getColref() - 1]->getRawValue()
        );

        $approved->setStatus($latestApproved[$this->statusCol->getColref() - 1]->getRawValue());
        $approved->setUser($latestApproved[$this->userCol->getColref() - 1]->getRawValue());
        $approved->setDatetime($latestApproved[$this->datetimeCol->getColref() - 1]->getRawValue());
        $approved->setVersion($latestApproved[$this->versionCol->getColref() - 1]->getRawValue());

        return $approved;
    }
}
