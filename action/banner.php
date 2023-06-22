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

    /** @var bool */
    protected $compactView;

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

        $this->compactView = (bool)$this->getConf('compact_view');

        // get the possible revisions needed in the banner
        $newestRevision = new Revision($ID, $INFO['currentrev']);
        if ($REV) {
            $shownRevision = new Revision($ID, $REV);
        } else {
            $shownRevision = $newestRevision;
        }
        $latestpubRevision = $newestRevision->getLatestPublishedRevision();
        $prevpubRevision = $shownRevision->getLatestPublishedRevision($REV ?:  $INFO['currentrev']);

        $compactClass = $this->compactView ? ' compact' : '';
        $banner = '<div class="plugin-structpublish-banner ' . $shownRevision->getStatus() . $compactClass . '">';

        // status of the shown revision
        $banner .= '<span class="icon">' .
            inlineSVG(__DIR__ . '/../ico/' . $shownRevision->getStatus() . '.svg') .
            '</span>';
        $banner .= $this->getBannerText('status_' . $shownRevision->getStatus(), $shownRevision);

        // link to previous or newest published version
        if ($latestpubRevision !== null && $shownRevision->getRev() < $latestpubRevision->getRev()) {
            $banner .= $this->getBannerText('latest_publish', $latestpubRevision, $shownRevision->getRev());
        } else {
            $banner .= $this->getBannerText('previous_publish', $prevpubRevision, $shownRevision->getRev());
        }

        // link to newest draft, if exists, is not shown already and user has a role
        if (
            $newestRevision->getRev() != $shownRevision->getRev() &&
            $newestRevision->getStatus() != Constants::STATUS_PUBLISHED &&
            $this->dbHelper->checkAccess($ID)
        ) {
            $banner .= $this->getBannerText('latest_draft', $newestRevision, $shownRevision->getRev());
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
     * @param string $name
     * @param Revision $rev
     * @return string
     */
    protected function getBannerText($name, $rev, $diff = '')
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

        $text = $this->getLang($this->compactView ? "compact_banner_$name" : "banner_$name");

        $text = strtr($text, $replace);

        // add link to diff view
        if ($diff && $diff !== $rev->getRev()) {
            $link = wl($rev->getId(), ['do' => 'diff', 'rev1' => $rev->getRev(), 'rev2' => $diff]);
            $icon = inlineSVG(__DIR__ . '/../ico/diff.svg');
            $text .= ' <a href="' . $link . '" title="' . $this->getLang('diff') . '">' . $icon . '</a>';
        }

        $tag = $this->compactView ? 'span' : 'p';

        return "<$tag class='$name'>$text</$tag>";
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
                'structpublish[' . Constants::ACTION_APPROVE . ']',
                $this->getLang('action_' . Constants::ACTION_APPROVE)
            )->attr('type', 'submit');
        }

        if ($this->dbHelper->checkAccess($ID, [Constants::ACTION_PUBLISH])) {
            $form->addTextInput('version', $this->getLang('newversion'))->val($newVersion);
            $form->addButton(
                'structpublish[' . Constants::ACTION_PUBLISH . ']',
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
