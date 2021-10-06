<?php

use dokuwiki\plugin\struct\test\StructTest;

/**
 * Published tests for the structpublish plugin
 *
 * @group plugin_structpublish
 * @group plugins
 */
class published_plugin_structpublish_test extends StructTest
{

    protected $pluginsEnabled = ['struct', 'structpublish', 'sqlite'];

    public function setUp() : void {
        parent::setUp();

        $this->loadSchemaJSON('schema1');
        $this->loadSchemaJSON('schema2');
        $_SERVER['REMOTE_USER'] = 'testuser';

        $as = \dokuwiki\plugin\struct\test\mock\Assignments::getInstance();
        $page = 'page01';
        $as->assignPageSchema($page, 'schema1');
        $as->assignPageSchema($page, 'schema2');
        saveWikiText($page, "===== TestTitle =====\nabc", "Summary");
        p_get_metadata($page);
        $now = time();
        $this->saveData(
            $page,
            'schema1',
            array(
                'first' => 'first data',
                'second' => array('second data', 'more data', 'even more'),
                'third' => 'third data',
                'fourth' => 'fourth data'
            ),
            $now
        );
        $this->saveData(
            $page,
            'schema2',
            array(
                'afirst' => 'first data',
                'asecond' => array('second data', 'more data', 'even more'),
                'athird' => 'third data',
                'afourth' => 'fourth data'
            ),
            $now
        );

        $as->assignPageSchema('test:document', 'schema1');
        $as->assignPageSchema('test:document', 'schema2');
        $this->saveData(
            'test:document',
            'schema1',
            array(
                'first' => 'document first data',
                'second' => array('second', 'more'),
                'third' => '',
                'fourth' => 'fourth data'
            ),
            $now
        );
        $this->saveData(
            'test:document',
            'schema2',
            array(
                'afirst' => 'first data',
                'asecond' => array('second data', 'more data', 'even more'),
                'athird' => 'third data',
                'afourth' => 'fourth data'
            ),
            $now
        );

        for($i = 10; $i <= 20; $i++) {
            $this->saveData(
                "page$i",
                'schema2',
                array(
                    'afirst' => "page$i first data",
                    'asecond' => array("page$i second data"),
                    'athird' => "page$i third data",
                    'afourth' => "page$i fourth data"
                ),
                $now
            );
            $as->assignPageSchema("page$i", 'schema2');
        }
    }

    /**
     * @group mine
     */
    public function test_published()
    {
        $search = new \dokuwiki\plugin\struct\test\mock\Search();

//        $dbHelper = plugin_load('helper', 'structpublish_permissions', true);
//        $search->getDb()->create_function('IS_PUBLISHER', array($dbHelper, 'IS_PUBLISHER'), -1);

        $search->addSchema('schema1');
        $search->addColumn('%pageid%');
        $search->addColumn('first');
        $search->addColumn('second');

        /** @var meta\Value[][] $result */
        $result = $search->execute();

        $this->assertEquals(0, count($result), 'result rows');
    }
}
