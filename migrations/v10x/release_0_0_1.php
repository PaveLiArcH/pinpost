<?php
/**
*
* Extension - Best Answer
*
* @copyright (c) 2015 kinerity <http://www.acsyste.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace kinerity\bestanswer\migrations\v10x;

class release_0_0_1 extends \phpbb\db\migration\migration
{
	/**
	* Add or update schema in the database
	*
	* @return array Array of table schema
	* @access public
	*/
	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'forums'	=> array(
					'enable_bestanswer'	=> array('BOOL', 0),
				),
				$this->table_prefix . 'topics'	=> array(
					'bestanswer_id'	=> array('UINT', 0),
				),
				$this->table_prefix . 'users'	=> array(
					'user_answers'	=> array('UINT', 0),
				),
			),
		);
	}

	/**
	* Add or update data in the database
	*
	* @return array Array of table data
	* @access public
	*/
	public function update_data()
	{
		return array(
			// Add permissions
			array('permission.add', array('f_mark_bestanswer', false)),
			array('permission.add', array('m_mark_bestanswer', false)),

			// Set permissions
			array('permission.permission_set', array('ROLE_FORUM_FULL', 'f_mark_bestanswer')),
			array('permission.permission_set', array('ROLE_FORUM_STANDARD', 'f_mark_bestanswer')),

			array('permission.permission_set', array('ROLE_MOD_FULL', 'm_mark_bestanswer')),
			array('permission.permission_set', array('ROLE_MOD_STANDARD', 'm_mark_bestanswer')),
		);
	}

	/**
	* Drop schema in the database
	*
	* @return array Array of table schema
	* @access public
	*/
	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'forums'	=> array(
					'enable_bestanswer',
				),
				$this->table_prefix . 'topics'	=> array(
					'bestanswer_id',
				),
				$this->table_prefix . 'users'	=> array(
					'user_answers',
				),
			),
		);
	}
}
