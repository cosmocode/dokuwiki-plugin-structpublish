<?php

use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Action component responsible for the publish banner
 * attached to struct data of a page
 */
class action_plugin_structpublish_banner extends DokuWiki_Action_Plugin
{
    /** @var \helper_plugin_structpublish_db */
    protected $dbHelper;

    /** @inheritDoc */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'renderBanner');
    }

    /**
     * Add banner to pages under structpublish control
     */
    public function renderBanner(Doku_Event $event)
    {
        global $ID;
        global $INFO;
        global $REV;

        if ($event->data !== 'show') {
            return;
        }

        $this->dbHelper = plugin_load('helper', 'structpublish_db');

        if (!$this->dbHelper->isPublishable()) {
            return;
        }

        // get the possible revisions needed in the banner
        $newestRevision = new Revision($this->dbHelper->getDB(), $ID, $INFO['currentrev']);
        if ($REV) {
            $shownRevision = new Revision($this->dbHelper->getDB(), $ID, $REV);
        } else {
            $shownRevision = $newestRevision;
        }
        $latestpubRevision = $newestRevision->getLatestPublishedRevision();
        $prevpubRevision = $shownRevision->getLatestPublishedRevision();

        $banner = '<div class="plugin-structpublish-banner ' . $shownRevision->getStatus() . '">';

        // status of the shown revision
        $banner .= inlineSVG(__DIR__ . '/../ico/' . $shownRevision->getStatus() . '.svg');
        $banner .= $this->getBannerText('status_' . $shownRevision->getStatus(), $shownRevision);

        // link to previous or newest published version
        if ($latestpubRevision !== null && $shownRevision->getRev() < $latestpubRevision->getRev()) {
            $banner .= $this->getBannerText('latest_publish', $latestpubRevision);
        } else {
            $banner .= $this->getBannerText('previous_publish', $prevpubRevision);
        }

        // link to newest draft, if exists, is not shown already and user has a role
        if (
            $newestRevision->getRev() != $shownRevision->getRev() &&
            $newestRevision->getStatus() != Constants::STATUS_PUBLISHED &&
            $this->dbHelper->checkAccess($ID)
        ) {
            $banner .= $this->getBannerText('latest_draft', $newestRevision);
        }

        // action buttons
        if ($shownRevision->getRev() == $newestRevision->getRev()) {
            $banner .= $this->actionButtons(
                $shownRevision->getStatus(),
                $latestpubRevision ? $this->increaseVersion($latestpubRevision->getVersion()) : '1'
            );
        }

        $banner .= '</div>';
        echo $banner;
    }

    /**
     * Fills place holder texts with data from the given Revision
     *
     * @param string $text
     * @param Revision $rev
     * @return string
     */
    protected function getBannerText($text, $rev)
    {
        if ($rev === null) {
            return '';
        }

        $replace = [
            '{user}' => userlink($rev->getUser()),
            '{revision}' => $this->makeLink($rev->getId(), $rev->getRev(), dformat($rev->getRev())),
            '{datetime}' => $this->makeLink($rev->getId(), $rev->getRev(), dformat($rev->getTimestamp())),
            '{version}' => hsc($rev->getVersion()),
        ];

        $text = $this->getLang("banner_$text");
        $text = strtr($text, $replace);

        return "<p>$text</p>";
    }

    /**
     * Create a HTML link to a specific revision
     *
     * @param string $id page id
     * @param int $rev revision to link to
     * @param int $text the link text to use
     * @return string
     */
    protected function makeLink($id, $rev, $text)
    {
        $url = wl($id, ['rev' => $rev]);
        return '<a href="' . $url . '">' . hsc($text) . '</a>';
    }

    /**
     * Create the form for approval and publishing
     *
     * @param string $status current status
     * @param string $newVersion suggested new Version
     * @return string
     */
    protected function actionButtons($status, $newVersion)
    {
        global $ID;
        if ($status === Constants::STATUS_PUBLISHED) {
            return '';
        }

        $form = new dokuwiki\Form\Form();

        if (
            $status !== Constants::STATUS_APPROVED &&
            $this->dbHelper->checkAccess($ID, [Constants::ACTION_APPROVE])
        ) {
            $form->addButton(
                'structpublish[approve]',
                $this->getLang('action_' . Constants::ACTION_APPROVE)
            )->attr('type', 'submit');
        }

        if ($this->dbHelper->checkAccess($ID, [Constants::ACTION_PUBLISH])) {
            $form->addTextInput('version', $this->getLang('newversion'))->val($newVersion);
            $form->addButton(
                'structpublish[publish]',
                $this->getLang('action_' . Constants::ACTION_PUBLISH)
            )->attr('type', 'submit');
        }

        return $form->toHTML();
    }

    /**
     * Tries to increase a given version
     *
     * @param string $version
     * @return string
     */
    protected function increaseVersion($version)
    {
        $parts = explode('.', $version);
        $last = array_pop($parts);

        if (is_numeric($last)) {
            $last++;
        }
        $parts[] = $last;

        return join('.', $parts);
    }
}
