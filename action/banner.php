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

    /**
     * @inheritDoc
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PLUGIN_STRUCT_RENDER_SCHEMA_DATA', 'AFTER', $this, 'renderBanner');
    }

    /**
     * Add banner to struct data of a page
     *
     * @return bool
     */
    public function renderBanner(Doku_Event $event)
    {
        global $ID;
        $data = $event->data;
        if (!$data['hasdata'] || $data['format'] !== 'xhtml') return true;

        $this->permissionsHelper = plugin_load('helper', 'structpublish_permissions');
        if (!$this->permissionsHelper->isPublishable()) return true;

        $revision = new Revision($this->permissionsHelper->getDb(), $ID);

        $renderer = $data['renderer'];
        $html = $this->getBannerHtml($revision);
        $renderer->doc .= $html;

        return true;
    }

    /**
     * @param Revision $revision
     * @return string
     */
    protected function getBannerHtml($revision)
    {
        global $ID;
        // FIXME use $INFO?
        $user = $_SERVER['REMOTE_USER'];
        $html = '';

        if ($this->permissionsHelper->isPublisher($ID, $user)) {
            $html = sprintf(
                $this->getBannerTemplate(),
                $revision->getStatus(),
                $revision->getVersion(),
                $revision->getStatus(),
                $this->linksToHtml($this->permissionsHelper->getActionLinks())
            );
        }

        return $html;
    }

    protected function linksToHtml($links)
    {
        $html = '';
        if (empty($links)) return $html;
        foreach ($links as $action => $link) {
            $html .= '<a href="' . $link . '">'. $action .'</a>';
        }
        return $html;
    }

    protected function getBannerTemplate()
    {
        $template = '<div class="plugin-structpublish-banner banner-%s">';
        $template .= '<div class="plugin-structpublish-version">' . $this->getLang('version') . ': %s</div>';
        $template .= '<div class="plugin-structpublish-status">' . $this->getLang('status') . ': %s</div>';
        $template .= '<div class="plugin-structpublish-actions">' . $this->getLang('actions') . ': %s</div>';
        $template .= '</div>';

        return $template;
    }
}
