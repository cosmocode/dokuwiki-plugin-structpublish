<?php

use dokuwiki\plugin\structpublish\meta\Revision;
use \dokuwiki\plugin\structpublish\meta\Constants;

class action_plugin_structpublish_revisions extends DokuWiki_Action_Plugin
{

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('FORM_REVISIONS_OUTPUT', 'BEFORE', $this, 'handleRevisions');
    }

    /**
     * Adds publish info to page revisions
     *
     * @param Doku_Event $event
     * @return void
     */
    public function handleRevisions(Doku_Event $event)
    {
        global $INFO;

        /** @var dokuwiki\Form\Form $form */
        $form = $event->data;

        /** @var helper_plugin_structpublish_db $helper */
        $helper = plugin_load('helper', 'structpublish_db');

        if (!$helper->isPublishable()) return;

        $elCount = $form->elementCount();
        $checkName = 'rev2[]';

        for ($i = 0; $i < $elCount; $i++) {
            $el = $form->getElementAt($i);

            if (!is_a($el, \dokuwiki\Form\CheckableElement::class) && !is_a($el, \dokuwiki\Form\HTMLElement::class)) {
                continue;
            }

            // extract rev from checkbox info
            if (is_a($el, \dokuwiki\Form\CheckableElement::class)) {
                if ($el->attr('name') === $checkName) {
                    $rev = $el->attr('value');
                }
            }

            // get most recent status for rev
            $revision = new Revision($INFO['id'], $rev);
            $status = $revision->getStatus();
            $version = $revision->getVersion();

            // insert status for published revisions
            if (is_a($el, \dokuwiki\Form\HTMLElement::class) && !empty(trim($el->val())) && $status === Constants::STATUS_PUBLISHED) {
                $val = $el->val();
                $label = '<span class="plugin-structpublish-version">' . $status . ' (' . $this->getLang('version') . ' ' . $version . ')</span>';
                $el->val("$val $label");
            }
        }
    }
}
