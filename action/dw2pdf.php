<?php

use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Action component providing (localized) dw2pdf replacements
 *
 * @STATUS@        draft / approved / published
 * @APPROVER@      user that approved the draft
 * @APPROVALDATE@  date of approval
 * @PUBLISHER@     user that published the page
 * @PUBLISHDATE@   date of publishing
 * @VERSION@       shown version 
 * @LATESTVERSION@ latest published version
 *
 * @author  Josquin Dehaene <jo@foobarjo.org>
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */

class action_plugin_structpublish_dw2pdf extends DokuWiki_Action_Plugin
{
    /**
     * @var \helper_plugin_structpublish_db 
     */
    protected $dbHelper;

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PLUGIN_DW2PDF_REPLACE', 'BEFORE', $this, 'provide_structpublish_replacements');
        $controller->register_hook('PLUGIN_DW2PDF_REPLACE', 'AFTER', $this, 'clean_structpublish_replacements');
    }

    /**
     * Provide StructPublish values for DW2PDF replacements
     */
    public function provide_structpublish_replacements(Doku_Event $event)
    {
        global $ID;
        global $INFO;
        global $REV;

        //force reload of the globals. usefull when coming from bookcreator
        $keep = $ID;
        $ID = $event->data['id']; 
        $INFO = pageinfo();
        $REV = null;

        $this->dbHelper = plugin_load('helper', 'structpublish_db');

        if (!$this->dbHelper->isPublishable()) {
            return;
        }

        // get revisions
        $newestRevision = new Revision($ID, $INFO['currentrev']);
        if ($REV) {
            $shownRevision = new Revision($ID, $REV);
        } else {
            $shownRevision = $newestRevision;
        }
        $latestpubRevision = $newestRevision->getLatestPublishedRevision();
        $prevpubRevision = $shownRevision->getLatestPublishedRevision($REV ?:  $INFO['currentrev']);
        $prevapprovedRevision = $shownRevision->getLatestApprovedRevision($REV ?: $INFO['currentrev']);

        //get redactor
        $pageMeta = p_get_metadata($ID);
        $event->data['replace']['@REDACTOR@'] = $pageMeta['last_change']['user'];

        // get last published version & revision
        if ($latestpubRevision != null) {
            $event->data['replace']['@LATESTVERSION@'] = $latestpubRevision->getVersion();
            $event->data['replace']['@LATESTVERSIONREVISION@'] = $latestpubRevision->getRev();
        }else{
            $event->data['replace']['@LATESTVERSION@'] = $this->getLang("status_na");
            $event->data['replace']['@LATESTVERSIONREVISION@'] = $this->getLang("status_na");;
        }

        // get status and revision
        $event->data['replace']['@STATUS@'] = $this->getLang("status_" . $shownRevision->getStatus());
        $event->data['replace']['@REVISION@'] = $shownRevision->getRev();

        // status draft
        if ($event->data['replace']['@STATUS@'] === $this->getLang("status_draft")) {
            $event->data['replace']['@VERSION@'] = $this->getLang("status_draft");
            $event->data['replace']['@APPROVER@'] = $this->getLang("status_na");
            $event->data['replace']['@APPROVALDATE@'] = $this->getLang("status_na");
            $event->data['replace']['@PUBLISHER@'] = $this->getLang("status_na");
            $event->data['replace']['@PUBLISHDATE@'] = $this->getLang("status_na");
        }

        // status approved
        if ($event->data['replace']['@STATUS@'] === $this->getLang("status_approved")) {
            $event->data['replace']['@VERSION@'] = $this->getLang("status_approved");
            $event->data['replace']['@APPROVER@'] = $shownRevision->getUser();
            $event->data['replace']['@APPROVALDATE@'] = $shownRevision->getDateTime();
            $event->data['replace']['@PUBLISHER@'] = $this->getLang("status_na");
            $event->data['replace']['@PUBLISHDATE@'] = $this->getLang("status_na");
        }

        // status published
        if ($event->data['replace']['@STATUS@'] === $this->getLang("status_published")) {
            $event->data['replace']['@VERSION@'] = $shownRevision->getVersion();
            $event->data['replace']['@APPROVER@'] = $prevapprovedRevision->getUser();
            $event->data['replace']['@APPROVALDATE@'] = $prevapprovedRevision->getDateTime();
            $event->data['replace']['@PUBLISHER@'] = $shownRevision->getUser();
            $event->data['replace']['@PUBLISHDATE@'] = $shownRevision->getDateTime();
        }
        $ID = $keep;
    }

    /**
     * Clean up replacements in DW2PDF content
     */
    public function clean_structpublish_replacements(Doku_Event $event)
    {
        $event->data['content'] = str_replace(
            ['@APPROVER@', '@APPROVALDATE@', '@PUBLISHER@', '@PUBLISHDATE@', '@VERSION@', '@STATUS@', '@REDACTOR@' , '@LATESTVERSION@'],
            ['', '', '', '', '', '', '', ''],
            $event->data['content']
        );
    }
}
