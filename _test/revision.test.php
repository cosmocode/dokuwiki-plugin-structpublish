<?php

use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Revision tests for the structpublish plugin
 *
 * @group plugin_structpublishh
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
        $USERINFO['grps'] = ['user, approver, publisher'];

        // our database migrations
        /** @var action_plugin_structpublish_migration $migration */
        $migration = plugin_load('action', 'structpublish_migration');
        $data = '';
        $migration->handleMigrations(new Doku_Event('DUMMY_EVENT', $data));

        // assignments
        $assignments = \dokuwiki\plugin\structpublish\meta\Assignments::getInstance(true);
        $this->sqlite = $assignments->getSqlite();
        $assignments->addPattern('public', '@approver', 'approve');
        $assignments->addPattern('public', '@publisher', 'publish');
    }

    /**
     * Test draft creation
     *
     * @return void
     */
    public function test_create_draft()
    {
        $pid = 'public:structpublish';
        $text = 'lorem ipsum';
        saveWikiText($pid, $text, 'Save first draft');

        $revision = new Revision($this->sqlite, $pid, time());

        $user = $revision->getUser();
        $status = $revision->getStatus();
        $version = $revision->getVersion();

        $this->assertEquals('', $user);
        $this->assertEquals(Constants::STATUS_DRAFT, $status);
        $this->assertEquals('', $version);
    }
}
