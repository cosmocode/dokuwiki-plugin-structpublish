<?php

use dokuwiki\plugin\structpublish\meta\Constants;

class action_plugin_structpublish_publish extends DokuWiki_Action_Plugin
{
    /**
     * @inheritDoc 
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'changeStatus');
    }

    /**
     * Handle the publish button and version field
     *
     * @param  Doku_Event $event
     * @return void
     * @throws Exception
     */
    public function changeStatus(Doku_Event $event)
    {
        if ($event->data != 'show') {
            return;
        }

        global $INPUT;

        $in = $INPUT->arr('structpublish');
        $action = key($in);
        if (!$action || !in_array($action, [Constants::ACTION_PUBLISH, Constants::ACTION_APPROVE])) {
            return;
        }

        if (!checkSecurityToken()) { return;
        }

        /**
 * @var helper_plugin_structpublish_publish $helper 
*/
        $helper = plugin_load('helper', 'structpublish_publish');
        $newRevision = $helper->saveRevision(key($in), $INPUT->str('version'));

        /**
 * @var helper_plugin_structpublish_notify $notifyHelper 
*/
        $notifyHelper  = plugin_load('helper', 'structpublish_notify');
        $notifyHelper->sendEmails($action, $newRevision);
    }
}
