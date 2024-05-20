<?php

use dokuwiki\Extension\Plugin;
use dokuwiki\Extension\AuthPlugin;
use dokuwiki\plugin\structpublish\meta\Assignments;
use dokuwiki\plugin\structpublish\meta\Constants;
use dokuwiki\plugin\structpublish\meta\Revision;

/**
 * Notification helper
 */
class helper_plugin_structpublish_notify extends Plugin
{
    /** @var helper_plugin_structpublish_db  */
    protected $dbHelper;

    public function __construct()
    {
        $this->dbHelper = plugin_load('helper', 'structpublish_db');
    }

    /**
     * If activated, send emails on configured status changes.
     *
     * @param string $action
     * @param Revision $newRevision
     * @return void
     * @throws Exception
     */
    public function sendEmails($action, $newRevision)
    {

        if (!$this->triggerNotification($action)) {
            return;
        }

        // get assignees from DB
        $assignments = Assignments::getInstance();
        $assignees = $assignments->getPageAssignments($newRevision->getId(), false);

        // get recipients for the next workflow step
        $nextAction = Constants::workflowSteps($action)['nextAction'];
        if (is_null($nextAction)) {
            return;
        }

        if (empty($assignees[$nextAction])) {
            msg($this->getLang('email_error_norecipients'), -1);
            return;
        }

        // flatten the array and split into single user or group items
        $assignees = implode(',', array_values($assignees[$nextAction]));
        $assignees = explode(',', $assignees);

        // get recipient emails
        $recipients = $this->resolveRecipients($assignees);

        // prepare mail text
        $mailText = $this->prepareMailText($newRevision->getStatus());

        $this->sendMail(implode(',', $recipients), $mailText);
    }

    /**
     * @param string $recipients Comma separated list of emails
     * @param string $mailText
     * @return void
     */
    public function sendMail($recipients, $mailText)
    {
        $mailer = new Mailer();
        $mailer->bcc($recipients);

        $subject = $this->getLang('email_subject');
        $mailer->subject($subject);

        $mailer->setBody($mailText);
        $mailer->send();
    }

    /**
     * Processes an array of (comma separated) recipients
     * and returns an array of emails
     * with user groups resolved to individual users
     *
     * @param array $recipients
     * @return array
     * @throws Exception
     */
    public function resolveRecipients($recipients)
    {
        $resolved = [];

        $recipients = array_unique($recipients);

        foreach ($recipients as $recipient) {
            $recipient = trim($recipient);

            if ($recipient[0] === '@') {
                $this->resolveGroup($resolved, $recipient);
            } elseif (strpos($recipient, '@') === false) {
                $this->resolveUser($resolved, $recipient);
            } else {
                $resolved[] = $recipient;
            }
        }
        return $resolved;
    }

    /**
     * @param array $resolved
     * @param string $recipient
     * @return void
     * @throws Exception
     */
    protected function resolveGroup(&$resolved, $recipient)
    {
        /** @var AuthPlugin $auth */
        global $auth;
        if (!$auth->canDo('getUsers')) {
            throw new \Exception('Auth cannot fetch users by group.');
        }

        // set arbitrary limit because not all backends interpret limit 0 as "no limit"
        $users = $auth->retrieveUsers(0, 5000, ['grps' => substr($recipient, 1)]);
        foreach ($users as $user) {
            $resolved[] = $user['mail'];
        }
    }

    /**
     * @param array $resolved
     * @param string $recipient
     * @return void
     */
    protected function resolveUser(&$resolved, $recipient)
    {
        /** @var AuthPlugin $auth */
        global $auth;
        $user = $auth->getUserData($recipient);
        if ($user) {
            $resolved[] = $user['mail'];
        }
    }

    /**
     * Check configuration to see if a notification should be triggered.
     *
     * @return bool
     */
    private function triggerNotification($action)
    {
        if (!$this->getConf('email_enable')) {
            return false;
        }

        $actions = array_map('trim', explode(',', $this->getConf('email_status')));
        return in_array($action, $actions);
    }

    /**
     * @return string
     */
    protected function prepareMailText($status)
    {
        global $ID;

        $mailtext = file_get_contents($this->localFN('mail'));

        $vars = [
            'PAGE' => $ID,
            'URL' => wl($ID, '', true),
            'STATUS_CURRENT' => $status,
        ];

        foreach ($vars as $var => $val) {
            $mailtext = str_replace('@' . $var . '@', $val, $mailtext);
        }

        return $mailtext;
    }
}
