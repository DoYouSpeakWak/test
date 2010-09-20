<?php
/**
*
* @package phpBB3
* @version $Id: functions_guests_24.php,v 1.0.0 2009/06/23 09:55:00 EST RMcGirr83Exp $
* @copyright (c) Rich McGirr
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

function obtain_guest_count_24()
{
	global $cache, $db, $template, $user;
	
	$user->add_lang('mods/guests_24');
	
	// Get number of online guests for the past 24 hours
	// caching and main sql if none yet
	if (($total_guests_online_24 = $cache->get('_total_guests_online_24')) === false)
	{
		// teh time
		$time = (time() - 86400);
		
		if ($db->sql_layer === 'sqlite')
		{
			$sql = 'SELECT COUNT(session_ip) as num_guests_24
				FROM (
					SELECT DISTINCT session_ip
					FROM ' . SESSIONS_TABLE . '
					WHERE session_user_id = ' . ANONYMOUS . '
						AND session_time >= ' . ($time - ((int) ($time % 60))) . ')';
		}
		else
		{
			$sql = 'SELECT COUNT(DISTINCT session_ip) as num_guests_24
				FROM ' . SESSIONS_TABLE . '
				WHERE session_user_id = ' . ANONYMOUS . '
					AND session_time >= ' . ($time - ((int) ($time % 60)));
		}
		$result = $db->sql_query($sql);
		$total_guests_online_24 = (int) $db->sql_fetchfield('num_guests_24');

		$db->sql_freeresult($result);
		
		// cache this stuff for, ohhhh, how about 5 minutes
		// change 300 to whatever number to reduce or increase the cache time
		$cache->put('_total_guests_online_24', $total_guests_online_24, 300);
	}
	
	$guests_online_24 = '';
	if ($total_guests_online_24)
	{
		$guests_online_24 = ($total_guests_online_24 === 1) ? sprintf($user->lang['GUEST_ONLINE_24'], $total_guests_online_24) : sprintf($user->lang['GUESTS_ONLINE_24'], $total_guests_online_24);
	}
	
	$template->assign_vars(array(
		'TOTAL_GUESTS_ONLINE'	=> $total_guests_online_24,
		'GUESTS_PAST_24'		=> $guests_online_24,
	));
}
?>