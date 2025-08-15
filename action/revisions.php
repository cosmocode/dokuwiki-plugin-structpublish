<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;
use dokuwiki\Form\CheckableElement;
use dokuwiki\Form\HTMLElement;
use dokuwiki\plugin\structpublish\meta\Revision;
use dokuwiki\plugin\structpublish\meta\Constants;

class action_plugin_structpublish_revisions extends ActionPlugin
{
    public function register(EventHandler $controller)
    {
        $controller->register_hook('FORM_REVISIONS_OUTPUT', 'BEFORE', $this, 'handleRevisions');
    }

    /**
     * Adds publish info to page revisions
     *
     * @param Event $event
     * @return void
     */
    public function handleRevisions(Event $event)
    {
        global $INFO;

        /** @var dokuwiki\Form\Form $form */
        $form = $event->data;

        /** @var helper_plugin_structpublish_db $helper */
        $helper = plugin_load('helper', 'structpublish_db');

        if (!$helper->isPublishable()) {
            return;
        }

        $elCount = $form->elementCount();
        $checkName = 'rev2[]';

        for ($i = 0; $i < $elCount; $i++) {
            $el = $form->getElementAt($i);

            if (!$el instanceof CheckableElement && !$el instanceof HTMLElement) {
                continue;
            }

            // extract rev from checkbox info
            if (is_a($el, CheckableElement::class)) {
                if ($el->attr('name') === $checkName) {
                    $rev = $el->attr('value');
                }
            }

            // get most recent status for rev
            $revision = new Revision($INFO['id'], $rev);
            $status = $revision->getStatus();
            $version = $revision->getVersion();

            // insert status for published revisions
            if (
                is_a($el, HTMLElement::class) &&
                !empty(trim($el->val())) &&
                $status === Constants::STATUS_PUBLISHED
            ) {
                $val = $el->val();
                $label = '<span class="plugin-structpublish-version">' .
                    $status . ' (' . $this->getLang('version') . ' ' . $version . ')</span>';
                $el->val("$val $label");
            }
        }
    }
}
