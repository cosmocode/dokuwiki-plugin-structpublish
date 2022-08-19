<?php

/**
 * DokuWiki Plugin structpublish (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */

class helper_plugin_structpublish_assignments extends DokuWiki_Plugin
{

    /**
     * @param $pattern
     * @param $src
     * @param $config
     * @return array
     */
    public function generateRules($pattern = null, $src = null, $config = null)
    {
        $rules = [];
        // ns pattern
        $pattern = 'testpage';
        $src = [
            'page' => [
                'status' => [
                    'publish' => [
                        'user' => ['@admin']
                    ]
                ]
            ],
            'test' => [
                'status' => [
                    'publish' => [
                        'user' => ['@admin']
                    ]
                ]
            ],
            'testpage' => [
                'status' => [
                    'publish' => [
                        'user' => ['@admin']
                    ]
                ]
            ],
            'testpage1' => [
                'status' => [
                    'publish' => [
                        'user' => ['@admin']
                    ]
                ]
            ],
        ];

        // Expected return pattern:

        $rules = [

        ];

        return $rules;
    }
    /**
     * Check if the given pattern matches the given page
     * @author Andreas Gohr
     *
     * @param string $pattern the pattern to check against
     * @param string $page the cleaned pageid to check
     * @param string|null $pns optimization, the colon wrapped namespace of the page, set null for automatic
     * @return bool
     */
    public function matchPagePattern($pattern, $page, $pns = null)
    {
        if (trim($pattern, ':') == '**') return true; // match all

        // regex patterns
        if ($pattern[0] == '/') {
            return (bool)preg_match($pattern, ":$page");
        }

        if (is_null($pns)) {
            $pns = ':' . getNS($page) . ':';
        }

        $ans = ':' . cleanID($pattern) . ':';
        if (substr($pattern, -2) == '**') {
            // upper namespaces match
            if (strpos($pns, $ans) === 0) {
                return true;
            }
        } elseif (substr($pattern, -1) == '*') {
            // namespaces match exact
            if ($ans == $pns) {
                return true;
            }
        } else {
            // exact match
            if (cleanID($pattern) == $page) {
                return true;
            }
        }

        return false;
    }
}
