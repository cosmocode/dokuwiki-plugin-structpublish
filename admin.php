<?php

use dokuwiki\plugin\structpublish\meta\Assignments;

/**
 * DokuWiki Plugin structpublish (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */

class admin_plugin_structpublish extends DokuWiki_Admin_Plugin
{
    /**
     * @return int sort number in admin menu
     */
    public function getMenuSort()
    {
        return 555;
    }

    /**
     * @return bool true if only access for superuser, false is for superusers and moderators
     */
    public function forAdminOnly()
    {
        return false;
    }

    /**
     * Based on struct pattern assignments
     */
    public function handle()
    {
        global $INPUT;
        global $ID;

        try {
            $assignments = Assignments::getInstance();
        } catch (Exception $e) {
            msg($e->getMessage(), -1);
            return false;
        }

        if ($INPUT->str('action') && $INPUT->arr('assignment') && checkSecurityToken()) {
            $assignment = $INPUT->arr('assignment');
            if (!blank($assignment['pattern']) && !blank($assignment['status'])) {
                if ($INPUT->str('action') === 'delete') {
                    $ok = $assignments->removePattern($assignment['pattern'], $assignment['user'], $assignment['status']);
                    if (!$ok) msg('failed to remove pattern', -1);
                } elseif ($INPUT->str('action') === 'add') {
                    if ($assignment['pattern'][0] == '/') {
                        if (@preg_match($assignment['pattern'], null) === false) {
                            msg('Invalid regular expression. Pattern not saved', -1);
                        } else {
                            $ok = $assignments->addPattern($assignment['pattern'], $assignment['user'], $assignment['status']);
                            if (!$ok) msg('failed to add pattern', -1);
                        }
                    } else {
                        $ok = $assignments->addPattern($assignment['pattern'],$assignment['user'], $assignment['status']);
                        if (!$ok) msg('failed to add pattern', -1);
                    }
                }
            }

            send_redirect(wl($ID, array('do' => 'admin', 'page' => 'structpublish'), true, '&'));
        }
    }

    /**
     * Render HTML output, e.g. helpful text and a form
     */
    public function html()
    {
        ptln('<h1>' . $this->getLang('menu') . '</h1>');

        global $ID;

        try {
            $assignments = Assignments::getInstance();
        } catch (Exception $e) {
            msg($e->getMessage(), -1);
            return false;
        }
        $list = $assignments->getAllPatterns();

        echo '<form action="' . wl($ID) . '" action="post">';
        echo '<input type="hidden" name="do" value="admin" />';
        echo '<input type="hidden" name="page" value="structpublish" />';
        echo '<input type="hidden" name="sectok" value="' . getSecurityToken() . '" />';
        echo '<table class="inline">';

        // header
        echo '<tr>';
        echo '<th>' . $this->getLang('assign_pattern') . '</th>';
        echo '<th>' . $this->getLang('assign_status') . '</th>';
        echo '<th>' . $this->getLang('assign_user') . '</th>';
        echo '<th></th>';
        echo '</tr>';

        // existing assignments
        foreach ($list as $assignment) {
            $pattern = $assignment['pattern'];
            $status = $assignment['status'];
            $user = $assignment['user'];

            $link = wl(
                $ID,
                [
                    'do' => 'admin',
                    'page' => 'structpublish',
                    'action' => 'delete',
                    'sectok' => getSecurityToken(),
                    'assignment[status]' => $status,
                    'assignment[pattern]' => $pattern,
                    'assignment[user]' => $user,
                ]
            );

            echo '<tr>';
            echo '<td>' . hsc($pattern) . '</td>';
            echo '<td>' . hsc($status) . '</td>';
            echo '<td>' . hsc($user) . '</td>';
            echo '<td><a class="deleteSchema" href="' . $link . '">' . $this->getLang('assign_del') . '</a></td>';
            echo '</tr>';
        }

        // new assignment form
        echo '<tr>';
        echo '<td><input type="text" name="assignment[pattern]" /></td>';
        echo '<td>';
        echo '<select name="assignment[status]">';
        foreach (['approve', 'publish'] as $status) {
            echo '<option value="' . $status . '">' . $status . '</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '<td><input type="text" name="assignment[user]" /></td>';
        echo '<td><button type="submit" name="action" value="add">' . $this->getLang('assign_add') . '</button></td>';
        echo '</tr>';

        echo '</table>';
        echo '</form>';
    }
}

