<?php
/**
 *
 * Extension - Pin Post
 *
 * @copyright (c) 2015 kinerity <http://www.acsyste.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace ruranobe\pinpost\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class listener implements EventSubscriberInterface
{
    /** @var \phpbb\auth\auth */
    protected $auth;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var string phpbb_root_path */
    protected $root_path;

    /** @var string phpEx */
    protected $php_ext;

    /**
     * Constructor
     *
     * @param \phpbb\auth\auth                  $auth     Auth object
     * @param \phpbb\db\driver\driver_interface $db       Database object
     * @param \phpbb\controller\helper          $helper   Controller Helper object
     * @param \phpbb\request\request            $request  Request object
     * @param \phpbb\template\template          $template Template object
     * @param \phpbb\user                       $user     User object
     * @param string                            $root_path
     * @param string                            $php_ext
     * @access public
     */
    public function __construct(
        \phpbb\auth\auth $auth,
        \phpbb\db\driver\driver_interface $db,
        \phpbb\controller\helper $helper,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        $root_path,
        $php_ext
    ) {
        $this->auth      = $auth;
        $this->db        = $db;
        $this->helper    = $helper;
        $this->request   = $request;
        $this->template  = $template;
        $this->user      = $user;
        $this->root_path = $root_path;
        $this->php_ext   = $php_ext;
    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     * @static
     * @access public
     */
    static public function getSubscribedEvents()
    {
        return array(
            'core.acp_manage_forums_display_form'    => 'acp_manage_forums_display_form',
            'core.acp_manage_forums_initialise_data' => 'acp_manage_forums_initialise_data',
            'core.acp_manage_forums_request_data'    => 'acp_manage_forums_request_data',

            'core.permissions' => 'permissions',

            'core.user_setup' => 'user_setup',

            'core.viewtopic_post_rowset_data' => 'viewtopic_post_rowset_data',
            'core.viewtopic_modify_post_row'  => 'viewtopic_modify_post_row',
        );
    }

    public function acp_manage_forums_display_form($event)
    {
        $template_data                     = $event['template_data'];
        $template_data['S_ENABLE_PINPOST'] = $event['forum_data']['enable_pinpost'];
        $event['template_data']            = $template_data;
    }

    public function acp_manage_forums_initialise_data($event)
    {
        if ($event['action'] == 'add') {
            $forum_data          = $event['forum_data'];
            $forum_data          = array_merge(
                $forum_data,
                array(
                    'enable_pinpost' => false,
                )
            );
            $event['forum_data'] = $forum_data;
        }
    }

    public function acp_manage_forums_request_data($event)
    {
        $forum_data                   = $event['forum_data'];
        $forum_data['enable_pinpost'] = $this->request->variable('enable_pinpost', 0);
        $event['forum_data']          = $forum_data;
    }

    public function permissions($event)
    {
        $permissions = $event['permissions'];

        $permissions['f_pin_post'] = array('lang' => 'ACL_F_MARK_PINPOST', 'cat' => 'actions');
        $permissions['m_pin_post'] = array('lang' => 'ACL_M_MARK_PINPOST', 'cat' => 'post_actions');

        $event['permissions'] = $permissions;
    }

    public function user_setup($event)
    {
        $lang_set_ext          = $event['lang_set_ext'];
        $lang_set_ext[]        = array(
            'ext_name' => 'ruranobe/pinpost',
            'lang_set' => 'common',
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }

    public function viewtopic_post_rowset_data($event)
    {
        $row                          = $event['row'];
        $rowset_data                  = $event['rowset_data'];
        $rowset_data['post_pin']      = $row['post_pin'];
        $rowset_data['post_pin_user'] = $row['post_pin_user'];
        $event['rowset_data']         = $rowset_data;
    }

    public function viewtopic_modify_post_row($event)
    {
        $poster_id        = $event['poster_id'];
        $row              = $event['row'];
        $user_poster_data = $event['user_poster_data'];
        $post_row         = $event['post_row'];
        $topic_data       = $event['topic_data'];

        $post_row['POST_PIN'] = $row['post_pin'];

        // Enable auth checks and mark/unmark buttons if extension is enabled for this forum
        if ($topic_data['enable_pinpost']) {
            $post_row['S_PIN_AVAILABLE'] = $this->auth->acl_get(
                'm_pin_post',
                (int)$topic_data['forum_id']
            ) || ($this->auth->acl_get(
                    'f_pin_post',
                    (int)$topic_data['forum_id']
                ) && $topic_data['topic_poster'] == $this->user->data['user_id']) ? true : false;

            $post_row['U_PIN_POST']   = $this->helper->route(
                'ruranobe_pinpost_main_controller',
                array('action' => 'pin_post', 'p' => (int)$row['post_id'])
            );
            $post_row['U_UNPIN_POST'] = $this->helper->route(
                'ruranobe_pinpost_main_controller',
                array('action' => 'unpin_post', 'p' => (int)$row['post_id'])
            );
        }

        $event['post_row'] = $post_row;
    }
}
