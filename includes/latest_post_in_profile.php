<?php
/**
*
* @package phpBB3
* @version $Id: latest_post_in_profile.php,v 1.0.4 2009/03/03 19:38:00 EST RMcGirr83 Exp $
* @copyright (c)Rich McGirr
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Include only once.
*/
function latest_post_in_profile($id)
{
	global $auth, $config, $user, $db, $phpbb_root_path, $phpEx, $template;

    // grab auths that allow a user to read a forum
	$forum_array = array_unique(array_keys($auth->acl_getf('!f_read', true)));

	// we have auths, change the sql query below
	$sql_and = '';
	if (sizeof($forum_array))
	{
		$sql_and = ' AND ' . $db->sql_in_set('p.forum_id', $forum_array, true);
	}

	// grab all posts that meet criteria and auths
	$sql = 'SELECT t.topic_title, t.forum_id, p.post_time, p.post_id
		FROM (' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . ' p)
			WHERE p.topic_id = t.topic_id
			AND p.post_approved = 1
				AND t.topic_moved_id = 0
					AND p.poster_id = ' . $id . '
                    ' . $sql_and . '
				ORDER BY p.post_time DESC';
					
	$result = $db->sql_query_limit($sql, 1);

	while( $row = $db->sql_fetchrow($result) )
	{
		$view_topic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $row['forum_id'] . '&amp;p=' . $row['post_id'] . '#p' . $row['post_id']);
		$topic_title = censor_text($row['topic_title']);

		$template->assign_vars(array(
			'S_UPOST'       => isset($topic_title) ? true : false,
			'U_TOPIC' 		=> $view_topic_url,
            'POST_TIME'      => $user->format_date($row['post_time']),			
			'TOPIC_TITLE' 	=> $topic_title)
		);
	}

	$db->sql_freeresult($result);
	$return;
}
?>