<?php
/**
 *
 * Extension - Pin Post
 *
 * @copyright (c) 2015 kinerity <http://www.acsyste.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace ruranobe\pinpost\migrations\v10x;

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
            'add_columns' => array(
                $this->table_prefix . 'forums' => array(
                    'enable_pinpost' => array('BOOL', 0),
                ),
                $this->table_prefix . 'posts'  => array(
                    'post_pin'      => array('BOOL', 0),
                    'post_pin_user' => array('UINT', 0),
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
            array('permission.add', array('f_pin_post', false)),
            array('permission.add', array('m_pin_post', false)),

            // Set permissions
            array('permission.permission_set', array('ROLE_FORUM_FULL', 'f_pin_post')),
            array('permission.permission_set', array('ROLE_FORUM_STANDARD', 'f_pin_post')),

            array('permission.permission_set', array('ROLE_MOD_FULL', 'm_pin_post')),
            array('permission.permission_set', array('ROLE_MOD_STANDARD', 'm_pin_post')),
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
            'drop_columns' => array(
                $this->table_prefix . 'forums' => array(
                    'enable_pinpost',
                ),
                $this->table_prefix . 'posts'  => array(
                    'post_pin',
                    'post_pin_user',
                ),
            ),
        );
    }
}
