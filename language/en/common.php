<?php
/**
 *
 * Extension - Pin Post
 *
 * @copyright (c) 2015 kinerity <http://www.acsyste.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB')) {
    exit;
}

if (empty($lang) || !is_array($lang)) {
    $lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge(
    $lang,
    array(
        'PINNED' => 'PINNED',

        'BUTTON_PIN_POST'   => 'Pin Post',
        'BUTTON_UNPIN_POST' => 'Unpin Post',

        'ENABLE_PINPOST'         => 'Enable "Pin Post" feature',
        'ENABLE_PINPOST_EXPLAIN' => 'If enabled, the topic starter (if permitted) and moderators (where allowed) will be able to pin posts in topic.',
        'EXTENSION_NOT_ENABLED'  => 'The Pin Post extension is not enabled on this forum.',

        'FULL_POST' => 'GO TO FULL POST',

        'INVALID_FILTER' => 'The filter parameter is invalid. Please verify this variable is correct.',

        'PIN_POST'         => 'Pin post',
        'PIN_POST_CONFIRM' => 'Are you sure you want to pin this post?',

        'UNPIN_POST'         => 'Unpin post',
        'UNPIN_POST_CONFIRM' => 'Are you sure you want to unpin this post?',
    )
);
