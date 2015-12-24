<?php
/**
*
* Extension - Best Answer
*
* @copyright (c) 2015 kinerity <http://www.acsyste.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace kinerity\bestanswer\event;

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
	* @param \phpbb\auth\auth						$auth			Auth object
	* @param \phpbb\db\driver\driver_interface		$db				Database object
	* @param \phpbb\controller\helper				$helper			Controller Helper object
	* @param \phpbb\request\request					$request		Request object
	* @param \phpbb\template\template				$template		Template object
	* @param \phpbb\user							$user			User object
	* @param string									$root_path
	* @param string									$php_ext
	* @access public
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $root_path, $php_ext)
	{
		$this->auth = $auth;
		$this->db = $db;
		$this->helper = $helper;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
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
			'core.acp_manage_forums_display_form'		=> 'acp_manage_forums_display_form',
			'core.acp_manage_forums_initialise_data'	=> 'acp_manage_forums_initialise_data',
			'core.acp_manage_forums_request_data'		=> 'acp_manage_forums_request_data',

			/**
			* TODO: implement these events?
			*
			*'core.delete_posts_before'					=> 'delete_posts_before',
			*'core.delete_topics_before_query'			=> 'delete_topics_before_query',
			*/
			'core.display_forums_modify_forum_rows'		=> 'display_forums_modify_forum_rows',
			'core.display_forums_modify_sql'			=> 'display_forums_modify_sql',
			'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',

			'core.mcp_change_poster_after'	=> 'mcp_change_poster_after',

			'core.permissions'	=> 'permissions',

			'core.search_get_topic_data'	=> 'search_get_topic_data',
			'core.search_modify_tpl_ary'	=> 'modify_topicrow_tpl_ary',

			'core.user_setup'	=> 'user_setup',

			'core.viewforum_modify_topicrow'				=> 'modify_topicrow_tpl_ary',
			'core.viewtopic_assign_template_vars_before'	=> 'viewtopic_assign_template_vars_before',
			'core.viewtopic_cache_user_data'				=> 'viewtopic_cache_user_data',
			'core.viewtopic_modify_post_row'				=> 'viewtopic_modify_post_row',
		);
	}

	public function acp_manage_forums_display_form($event)
	{
		$template_data = $event['template_data'];
		$template_data['S_ENABLE_BESTANSWER'] = $event['forum_data']['enable_bestanswer'];
		$event['template_data'] = $template_data;
	}

	public function acp_manage_forums_initialise_data($event)
	{
		if ($event['action'] == 'add')
		{
			$forum_data = $event['forum_data'];
			$forum_data = array_merge($forum_data, array(
				'enable_bestanswer'	=> false,
			));
			$event['forum_data'] = $forum_data;
		}
	}

	public function acp_manage_forums_request_data($event)
	{
		$forum_data = $event['forum_data'];
		$forum_data['enable_bestanswer'] = $this->request->variable('enable_bestanswer', 0);
		$event['forum_data'] = $forum_data;
	}

	public function display_forums_modify_forum_rows($event)
	{
		$forum_rows = $event['forum_rows'];
		$parent_id = $event['parent_id'];
		$row = $event['row'];

		$forum_rows[$parent_id]['bestanswer_id'] = $row['bestanswer_id'];

		$event['forum_rows'] = $forum_rows;
	}

	public function display_forums_modify_sql($event)
	{
		$sql_ary = $event['sql_ary'];

		$sql_ary['SELECT'] .= ', t.bestanswer_id, t.topic_id AS forum_last_post_topic_id';

		$sql_ary['LEFT_JOIN'][] = array(
			'FROM'	=> array(POSTS_TABLE => 'p'),
			'ON'	=> 'f.forum_last_post_id = p.post_id',
		);
		$sql_ary['LEFT_JOIN'][] = array(
			'FROM'	=> array(TOPICS_TABLE => 't'),
			'ON'	=> 't.topic_id = p.topic_id',
		);

		$event['sql_ary'] = $sql_ary;
	}

	public function display_forums_modify_template_vars($event)
	{
		$forum_row = $event['forum_row'];
		$row = $event['row'];

		$forum_row['S_ANSWERED'] = $row['bestanswer_id'] ? true : false;

		$event['forum_row'] = $forum_row;
	}

	public function mcp_change_poster_after($event)
	{
		$userdata = $event['userdata'];
		$post_info = $event['post_info'];

		$sql = 'SELECT bestanswer_id
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id = ' . $post_info['topic_id'];
		$result = $this->db->sql_query($sql);
		$bestanswer_id = (int) $this->db->sql_fetchfield('bestanswer_id');
		$this->db->sql_freeresult($result);

		if ($bestanswer_id == $post_info['post_id'])
		{
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_answers = user_answers - 1
				WHERE user_id = ' . $post_info['user_id'];
			$this->db->sql_query($sql);

			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_answers = user_answers + 1
				WHERE user_id = ' . $userdata['user_id'];
			$this->db->sql_query($sql);
		}
	}

	public function modify_topicrow_tpl_ary($event)
	{
		$block = $event['topic_row'] ? 'topic_row' : 'tpl_ary';
		$event[$block] = $this->display_topic_answered($event['row'], $event[$block]);
	}

	public function permissions($event)
	{
		$permissions = $event['permissions'];

		$permissions['f_mark_bestanswer'] = array('lang' => 'ACL_F_MARK_BESTANSWER', 'cat' => 'actions');
		$permissions['m_mark_bestanswer'] = array('lang' => 'ACL_M_MARK_BESTANSWER', 'cat' => 'post_actions');

		$event['permissions'] = $permissions;
	}

	public function search_get_topic_data($event)
	{
		$sql_select = $event['sql_select'];
		$sql_from = $event['sql_from'];
		$sql_where = $event['sql_where'];

		// Allow users to search topics a user has answered
		$filter = $this->request->variable('filter', '');
		$author_id = $this->request->variable('author_id', 0);

		if ($filter == 'topicsanswered')
		{
			$sql_select .= ', p.post_id, p.poster_id';
			$sql_from .= ' LEFT JOIN ' . POSTS_TABLE . ' p ON (p.post_id = t.bestanswer_id)';
			$sql_where .= ' AND p.poster_id = ' . (int) $author_id;

			// Set $total_match_count to 0 - DO NOT modify the event['total_match_count'] variable!
			$total_match_count = 0;

			// Grab all necessary data to modify total_match_count
			$sql_array = array(
				'SELECT'	=> 'p.post_id, p.poster_id, t.topic_id, t.bestanswer_id',

				'FROM'		=> array(
					POSTS_TABLE		=> 'p',
					TOPICS_TABLE	=> 't',
				),

				'WHERE'		=> 'p.post_id = t.bestanswer_id
									AND p.poster_id = ' . (int) $author_id,
			);
			$sql = $this->db->sql_build_query('SELECT', $sql_array);

			// Run the built query
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$total_match_count++;
			}
			$this->db->sql_freeresult($result);

			$event['total_match_count'] = $total_match_count;
		}
		else if ($filter != '')
		{
			trigger_error($this->user->lang('INVALID_FILTER'));
		}

		$event['sql_select'] = $sql_select;
		$event['sql_from'] = $sql_from;
		$event['sql_where'] = $sql_where;
	}

	public function user_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name'	=> 'kinerity/bestanswer',
			'lang_set'	=> 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function viewtopic_assign_template_vars_before($event)
	{
		$topic_data = $event['topic_data'];

		if ($topic_data['bestanswer_id'])
		{
			$this->template->assign_vars(array(
				'S_ANSWERED'	=> true,
			));
		}
	}

	public function viewtopic_cache_user_data($event)
	{
		$user_cache_data = $event['user_cache_data'];
		$row = $event['row'];

		$user_cache_data['topics_answered'] = (int) $row['user_answers'];

		$event['user_cache_data'] = $user_cache_data;
	}

	public function viewtopic_modify_post_row($event)
	{
		$poster_id = $event['poster_id'];
		$row = $event['row'];
		$user_poster_data = $event['user_poster_data'];
		$post_row = $event['post_row'];
		$topic_data = $event['topic_data'];

		$post_row['BESTANSWER_ID'] = (int) $topic_data['bestanswer_id'];

		$post_row['U_ANSWER'] = append_sid("{$this->root_path}viewtopic.{$this->php_ext}", 'p=' . (int) $topic_data['bestanswer_id'] . '#p' . (int) $topic_data['bestanswer_id']);

		// Enable auth checks and mark/unmark buttons if extension is enabled for this forum
		if ($topic_data['enable_bestanswer'])
		{
			$post_row['S_AUTH'] = $this->auth->acl_get('m_mark_bestanswer', (int) $topic_data['forum_id']) || ($this->auth->acl_get('f_mark_bestanswer', (int) $topic_data['forum_id']) && $topic_data['topic_poster'] == $this->user->data['user_id']) ? true : false;

			$post_row['U_MARK_ANSWER'] = $this->helper->route('kinerity_bestanswer_main_controller', array('action' => 'mark_answer', 'p' => (int) $row['post_id']));
			$post_row['U_UNMARK_ANSWER'] = $this->helper->route('kinerity_bestanswer_main_controller', array('action' => 'unmark_answer', 'p' => (int) $row['post_id']));
		}

		// Only pull answer post text if a bestanswer_id is supplied and the post_id is the first post in a topic
		if ($topic_data['bestanswer_id'] && ($topic_data['topic_first_post_id'] == $row['post_id']))
		{
			$sql = 'SELECT p.*, u.user_id, u.username, u.user_colour
				FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE p.post_id = ' . (int) $topic_data['bestanswer_id'] . '
					AND p.poster_id = u.user_id';
			$result = $this->db->sql_query($sql);
			while ($post = $this->db->sql_fetchrow($result))
			{
				$bbcode_options = (($post['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
					(($post['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +
					(($post['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
				$post_row['ANSWER'] = generate_text_for_display($post['post_text'], $post['bbcode_uid'], $post['bbcode_bitfield'], $bbcode_options);
				$post_row['ANSWER_AUTHOR_FULL'] = get_username_string('full', $post['user_id'], $post['username'], $post['user_colour']);
				$post_row['ANSWER_DATE'] = $this->user->format_date($post['post_time']);
			}
			$this->db->sql_freeresult($result);
		}

		// Add the topics answered search URL to the mini profile in viewtopic
		$url = append_sid("{$this->root_path}search.{$this->php_ext}", 'author_id=' . (int) $poster_id . '&amp;sr=topics&amp;filter=topicsanswered');

		$post_row['U_TOPICS_ANSWERED'] = $url;
		$post_row['TOPICS_ANSWERED'] = $user_poster_data['topics_answered'];

		$event['post_row'] = $post_row;
	}

	private function display_topic_answered($row, $block)
	{
		$block = array_merge($block, array(
			'S_ANSWERED'	=> $row['bestanswer_id'] ? true : false,
		));

		return $block;
	}
}
