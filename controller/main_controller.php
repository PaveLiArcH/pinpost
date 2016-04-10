<?php
/**
 *
 * Extension - Pin Post
 *
 * @copyright (c) 2015 kinerity <http://www.acsyste.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace ruranobe\pinpost\controller;

/**
 * Main controller
 */
class main_controller
{
    /** @var \phpbb\auth\auth */
    protected $auth;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\user */
    protected $user;

    /** @var string phpbb_root_path */
    protected $root_path;

    /** @var string phpEx */
    protected $php_ext;

    /**
     * Constructor
     *
     * @param \phpbb\auth\auth                  $auth    Auth object
     * @param \phpbb\db\driver\driver_interface $db      Database object
     * @param \phpbb\request\request            $request Request object
     * @param \phpbb\user                       $user    User object
     * @param string                            $root_path
     * @param string                            $php_ext
     * @access public
     */
    public function __construct(
        \phpbb\auth\auth $auth,
        \phpbb\db\driver\driver_interface $db,
        \phpbb\request\request $request,
        \phpbb\user $user,
        $root_path,
        $php_ext
    ) {
        $this->auth      = $auth;
        $this->db        = $db;
        $this->request   = $request;
        $this->user      = $user;
        $this->root_path = $root_path;
        $this->php_ext   = $php_ext;
    }

    public function change_post_status($action)
    {
        $post_id = $this->request->variable('p', 0);

        $sql_array = array(
            'SELECT' => 'f.enable_pinpost, p.*, u.user_id',

            'FROM' => array(
                FORUMS_TABLE => 'f',
                POSTS_TABLE  => 'p',
                USERS_TABLE  => 'u',
            ),

            'WHERE' => "p.post_id = $post_id AND p.poster_id = u.user_id AND f.forum_id = t.forum_id",
        );

        $sql       = $this->db->sql_build_query('SELECT', $sql_array);
        $result    = $this->db->sql_query($sql);
        $post_data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        // Validate all checks and throw errors
        if (!$post_data['enable_pinpost']) {
            throw new \phpbb\exception\http_exception(404, $this->user->lang('EXTENSION_NOT_ENABLED'));
        }
        if (!$this->auth->acl_get('m_pin_post', $post_data['forum_id']) &&
            (!$this->auth->acl_get('f_pin_post', $post_data['forum_id'])) &&
            $post_data['topic_poster'] != $this->user->data['user_id']
        ) {
            throw new \phpbb\exception\http_exception(403, $this->user->lang('NOT_AUTHORISED'));
        }

        // OK, either mark or unmark pins
        switch ($action) {
            case 'pin_post':
            case 'unpin_post':
                if (confirm_box(true)) {
                    if ($action == 'unpin_post') {
                        $sql = 'UPDATE ' . POSTS_TABLE . '
							SET post_pin = false, post_pin_user = 0
							WHERE post_id = ' . $post_data['post_id'];
                        $this->db->sql_query($sql);
                    }

                    if ($action == 'pin_post') {
                        // Now, update everything
                        $sql = 'UPDATE ' . POSTS_TABLE . '
							SET post_pin = true, post_pin_user = ' . $this->user->data['user_id'] . '
							WHERE post_id = ' . $post_data['post_id'];
                        $this->db->sql_query($sql);
                    }
                } else {
                    confirm_box(false, $this->user->lang(strtoupper($action) . '_CONFIRM'));
                }
                break;
        }

        // Redirect back to the post
        $url = append_sid("{$this->root_path}viewtopic.{$this->php_ext}", 'p=' . (int)$post_id . '#p' . (int)$post_id);
        redirect($url);
    }
}
