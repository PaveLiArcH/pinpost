<?php
/**
*
* Extension - Best Answer
*
* @copyright (c) 2015 kinerity <http://www.acsyste.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace kinerity\bestanswer\controller;

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
	* @param \phpbb\auth\auth						$auth			Auth object
	* @param \phpbb\db\driver\driver_interface		$db				Database object
	* @param \phpbb\request\request					$request		Request object
	* @param \phpbb\user							$user			User object
	* @param string									$root_path
	* @param string									$php_ext
	* @access public
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\user $user, $root_path, $php_ext)
	{
		$this->auth = $auth;
		$this->db = $db;
		$this->request = $request;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function change_post_status($action)
	{
		$post_id = $this->request->variable('p', 0);

		$sql_array = array(
			'SELECT'	=> 'f.forum_id, f.enable_bestanswer, p.post_id, p.topic_id, p.poster_id, t.topic_id, t.topic_first_post_id, t.topic_poster, u.user_id, u.user_answers',

			'FROM'		=> array(
				FORUMS_TABLE	=> 'f',
				POSTS_TABLE		=> 'p',
				TOPICS_TABLE	=> 't',
				USERS_TABLE		=> 'u',
			),

			'WHERE'		=> 'p.post_id = ' . (int) $post_id . '
								AND p.topic_id = t.topic_id
								AND p.poster_id = u.user_id
								AND t.forum_id = f.forum_id',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		// Run the built query
		$result = $this->db->sql_query($sql);
		$data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Validate all checks and throw errors
		if (!$data['enable_bestanswer'])
		{
			throw new \phpbb\exception\http_exception(404, $this->user->lang('EXTENSION_NOT_ENABLED'));
		}
		if ($data['topic_first_post_id'] == (int) $post_id)
		{
			throw new \phpbb\exception\http_exception(404, $this->user->lang('TOPIC_FIRST_POST'));
		}
		if (!$this->auth->acl_get('m_mark_bestanswer', $data['forum_id']) && (!$this->auth->acl_get('f_mark_bestanswer', $data['forum_id']) && $data['topic_poster'] != $this->user->data['user_id']))
		{
			throw new \phpbb\exception\http_exception(403, $this->user->lang('NOT_AUTHORISED'));
		}

		// OK, either mark or unmark answers
		switch ($action)
		{
			case 'mark_answer':
			case 'unmark_answer':
				if (confirm_box(true))
				{
					if ($action == 'unmark_answer')
					{
						$sql_data = array(
							'bestanswer_id' => 0,
						);
						$sql = 'UPDATE ' . TOPICS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_data) . ' WHERE topic_id = ' . $data['topic_id'];
						$this->db->sql_query($sql);

						$sql_data = array(
							'user_answers' => $data['user_answers'] - 1,
						);
						$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_data) . ' WHERE user_id = ' . $data['user_id'];
						$this->db->sql_query($sql);
					}

					if ($action == 'mark_answer')
					{
						$sql_array = array(
							'SELECT'	=> 'p.post_id, p.topic_id, p.poster_id, t.topic_id, t.bestanswer_id, u.user_id, u.user_answers',

							'FROM'		=> array(
								POSTS_TABLE		=> 'p',
								TOPICS_TABLE	=> 't',
								USERS_TABLE		=> 'u',
							),

							'WHERE'		=> 'p.post_id = t.bestanswer_id
												AND p.topic_id = t.topic_id
												AND p.poster_id = u.user_id',
						);
						$sql = $this->db->sql_build_query('SELECT', $sql_array);

						// Run the built query
						$result = $this->db->sql_query($sql);
						$row = $this->db->sql_fetchrow($result);
						$this->db->sql_freeresult($result);

						if ($row['bestanswer_id'])
						{
							$sql_data = array(
								'user_answers' => $row['user_answers'] - 1,
							);
							$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_data) . ' WHERE user_id = ' . $row['user_id'];
							$this->db->sql_query($sql);
						}

						// Now, update everything
						$sql_data = array(
							'bestanswer_id' => (int) $post_id,
						);
						$sql = 'UPDATE ' . TOPICS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_data) . ' WHERE topic_id = ' . $data['topic_id'];
						$this->db->sql_query($sql);

						$sql_data = array(
							'user_answers' => $data['user_answers'] + 1,
						);
						$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_data) . ' WHERE user_id = ' . $data['user_id'];
						$this->db->sql_query($sql);
					}
				}
				else
				{
					confirm_box(false, $this->user->lang(strtoupper($action) . '_CONFIRM'));
				}
			break;
		}

		// Redirect back to the post
		$url = append_sid("{$this->root_path}viewtopic.{$this->php_ext}", 'p=' . (int) $post_id . '#p' . (int) $post_id);
		redirect($url);
	}
}
