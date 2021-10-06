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
        $this->dbHelper = plugin_load('helper', 'structpublish_permissions');
        if (!$this->permissionsHelper->isPublishable()) return;

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

        if ($this->permissionsHelper->isPublisher($ID, $user)) {

            $status = $revision->getStatus() ?: Revision::STATUS_DRAFT;
            $version = $revision->getVersion() ?: '';
            $html = sprintf(
                $this->getBannerTemplate(),
                $status,
                $version,
                $status,
                $this->formHtml()
            );
        }

        return $html;
    }

    protected function formHtml()
    {
        $form = new dokuwiki\Form\Form();
        $form->addButton('structpublish[review]', 'REVIEWED');
        $form->addButton('structpublish[publish]', 'PUBLISH');

        return $form->toHTML();
    }

    protected function getBannerTemplate()
    {
        $template = '<div class="plugin-structpublish-banner banner-%s">';
        $template .= '<div class="plugin-structpublish-banner banner-header">structpublish</div>';
        $template .= '<div class="plugin-structpublish-version">' . $this->getLang('version') . ': %s</div>';
        $template .= '<div class="plugin-structpublish-status">' . $this->getLang('status') . ': %s</div>';
        $template .= '<div class="plugin-structpublish-actions">' . $this->getLang('actions') . ': %s</div>';
        $template .= '</div>';

        return $template;
    }
}
