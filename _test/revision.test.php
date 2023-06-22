<?php

use dokuwiki\plugin\structpublish\meta\Assignments;
use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Revision tests for the structpublish plugin
 *
 * @group plugin_structpublish
 * @group plugins
 */
class revision_plugin_structpublish_test extends DokuWikiTest
{
    /** @inheritdoc **/
    protected $pluginsEnabled = ['sqlite', 'struct', 'structpublish'];

    /**
     * @var \helper_plugin_sqlite
     */
    protected $sqlite;

    public function setUp(): void
    {
        parent::setUp();

        global $USERINFO;

        // user
        $_SERVER['REMOTE_USER'] = 'publisher';
        $USERINFO['grps'] = ['user', 'approver', 'publisher'];

        // our database migrations
        /** @var action_plugin_structpublish_migration $migration */
        $migration = plugin_load('action', 'structpublish_migration');
        $data = '';
        $migration->handleMigrations(new Doku_Event('DUMMY_EVENT', $data));

        // assignments
        $assignments = Assignments::getInstance(true);
        $this->sqlite = $assignments->getSqlite();
        $assignments->addPattern('public:**', '@approver', 'approve');
        $assignments->addPattern('public:**', '@publisher', 'publish');
    }

    /**
     * Test publish workflow
     *
     * @return void
     */
    public function test_full_workflow()
    {
        global $ID;
        global $INFO;

        $pid = 'public:structpublish';
        $ID = $pid;
        $INFO['id'] = $pid;

        $text = 'lorem ipsum';

        saveWikiText($pid, $text, 'Save first draft');

        $currentrev = time();
        $INFO['currentrev'] = $currentrev;

        $revision = new Revision($pid, $currentrev);

        $user = $revision->getUser();
        $status = $revision->getStatus();
        $version = $revision->getVersion();

        $this->assertEquals('', $user);
        $this->assertEquals(Constants::STATUS_DRAFT, $status);
        $this->assertEquals('', $version);

        $helper = plugin_load('helper', 'structpublish_publish');

        // approve
        $helper->saveRevision(Constants::ACTION_APPROVE);

        $revision = new Revision($ID, $currentrev);
        $status = $revision->getStatus();
        $this->assertEquals(Constants::STATUS_APPROVED, $status);

        // publish
        $helper->saveRevision(Constants::ACTION_PUBLISH, '1');

        $revision = new Revision($ID, $currentrev);
        $status = $revision->getStatus();
        $user = $revision->getUser();
        $this->assertEquals(Constants::STATUS_PUBLISHED, $status);
        $this->assertEquals('publisher', $user);

        sleep(3);

        saveWikiText($pid, "$text $text", 'Save second draft');

        $currentrev = time();
        $INFO['currentrev'] = $currentrev;

        $helper->saveRevision(Constants::ACTION_PUBLISH, '2');

        $revision = new Revision($ID, $currentrev);
        $previous = $revision->getLatestPublishedRevision($currentrev);
        $this->assertEquals('2', $revision->getVersion());
        $this->assertEquals('1', $previous->getVersion());
    }
}
