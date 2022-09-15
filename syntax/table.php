<?php

use dokuwiki\plugin\struct\meta\AggregationTable;

/**
 * DokuWiki Plugin structpublish (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */
class syntax_plugin_structpublish_table extends syntax_plugin_struct_serial
{
    protected $tableclass = AggregationTable::class;

    /** @inheritdoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('----+ *structpublish *-+\n.*?\n?----+', $mode, 'plugin_structpublish_table');
    }

    /** @inheritdoc */
    protected function addTypeFilter($config)
    {
        $config['schemas'][] = ['structpublish', 'structpublish'];
        array_unshift($config['cols'], '%pageid%');
        $config['filter'][] = [
            '%rowid%',
            '!=',
            (string) \dokuwiki\plugin\struct\meta\AccessTablePage::DEFAULT_PAGE_RID,
            'AND'
        ];
        $config['withpid'] = 1; // flag for the editor to distinguish data types
        return $config;
    }
}

