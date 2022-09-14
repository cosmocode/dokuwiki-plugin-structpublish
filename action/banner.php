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

    /**
     * @inheritDoc
     */
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

        if ($event->data !== 'show') return;

        $this->dbHelper = plugin_load('helper', 'structpublish_db');

        if (!$this->dbHelper->isPublishable()) return;

        $revision = new Revision($this->dbHelper->getDB(), $ID, $REV ?: $INFO['currentrev']);

        echo $this->getBannerHtml($revision);
    }

    /**
     * @param Revision $revision latest publish data
     * @return string
     */
    protected function getBannerHtml($revision)
    {
        global $ID;

        $status = $revision->getStatus() ?: Constants::STATUS_DRAFT;
        if ($status === Constants::STATUS_PUBLISHED) {
            $publisher = userlink($revision->getUser(), true);
            $publishDate = $revision->getDate();
        } else {
            $publisher = userlink($revision->getLatestPublishedRevision()->getUser(), true);
            $publishDate = $revision->getLatestPublishedRevision()->getDate();
        }

        $version =  '';
        if ($revision->getVersion()) {
            $version = $revision->getVersion() . " ($publishDate, $publisher)";

            if ($status !== Constants::STATUS_PUBLISHED) {
                $version = sprintf(
                    '<a href="'. wl($ID, ['rev' => $revision->getLatestPublishedRevision()->getRev()]) . ' ">%s</a>',
                    $version
                );
            }
        }

        $actionForm = $this->formHtml($status);

        $html = sprintf(
            $this->getBannerTemplate(),
            $status,
            $status,
            $version,
            $actionForm
        );

        return $html;
    }

    protected function formHtml($status)
    {
        global $ID;
        if ($status === Constants::STATUS_PUBLISHED) return '';

        $form = new dokuwiki\Form\Form();

        if ($status !== Constants::STATUS_APPROVED) {
            $form->addButton('structpublish[approve]', 'APPROVE')->attr('type', 'submit');
        }


        $form->addButton('structpublish[publish]', 'PUBLISH')->attr('type', 'submit');

        return $form->toHTML();
    }

    protected function getBannerTemplate()
    {
        $template = '<div class="plugin-structpublish-banner banner-%s">';
        $template .= '<div class="plugin-structpublish-status">' . $this->getLang('status') . ': %s</div>';
        $template .= '<div class="plugin-structpublish-version">' . $this->getLang('version') . ': %s</div>';
        $template .= '<div class="plugin-structpublish-actions">%s</div>';
        $template .= '</div>';

        return $template;
    }
}
