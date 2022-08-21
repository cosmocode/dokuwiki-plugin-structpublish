<?php

use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Action component responsible for the publish banner
 * attached to struct data of a page
 */
class action_plugin_structpublish_banner extends DokuWiki_Action_Plugin
{
    /** @var \helper_plugin_structpublish_permissions */
    protected $permissionsHelper;
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

        if ($event->data !== 'show') return;

        $this->permissionsHelper = plugin_load('helper', 'structpublish_permissions');
        $this->dbHelper = plugin_load('helper', 'structpublish_db');
        if (!$this->dbHelper->IS_PUBLISHER($ID)) return;

        $revision = new Revision($this->permissionsHelper->getDb(), $ID, $INFO['currentrev']);

        echo $this->getBannerHtml($revision);
    }

    /**
     * @param Revision $revision latest publish data
     * @return string
     */
    protected function getBannerHtml($revision)
    {
        global $ID;
        $user = $_SERVER['REMOTE_USER'];
        $html = '';

        if ($this->dbHelper->IS_PUBLISHER($ID, $user)) {

            $status = $revision->getStatus() ?: Revision::STATUS_DRAFT;
            $publisher = userlink($revision->getUser(), true);
            $publishDate = $revision->getDate();

            $version =  '';
            if ($revision->getVersion()) {
                $version = '<a href="'. wl($ID, ['rev' => $revision->getLatestPublishedRev()]) . ' ">';
                $version .= $revision->getVersion() . " ($publishDate, $publisher)";
                $version .= '</a>';
            }

            $actionForm = $this->formHtml($status);

            $html = sprintf(
                $this->getBannerTemplate(),
                $status,
                $status,
                $version,
                $actionForm
            );
        }

        return $html;
    }

    protected function formHtml($status)
    {
        if ($status === Revision::STATUS_PUBLISHED) return '';

        $form = new dokuwiki\Form\Form();

        if ($status !== Revision::STATUS_APPROVED) {
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
