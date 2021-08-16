<?php

/**
 * Action component responsible for the publish banner
 * attached to struct data of a page
 */
class action_plugin_structpublish_banner extends DokuWiki_Action_Plugin
{
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
        $data = $event->data;
        if (!$data['hasdata'] || $data['format'] !== 'xhtml') return true;

        $renderer = $data['renderer'];
        $html = $this->getBannerHtml();
        $renderer->doc .= $html;

        return true;
    }

    /**
     * @return string
     */
    protected function getBannerHtml()
    {
        global $ID;
        $user = $_SERVER['REMOTE_USER'];
        $html = '';

        /** @var \helper_plugin_structpublish_permissions $permissonsHelper */
        $permissonsHelper = plugin_load('helper', 'structpublish_permissions');
        if ($permissonsHelper->isPublisher($ID, $user)) {
            $html .= 'YOU MAY!';
        }

        return $html;
    }
}
