<?php
/**
*
* guests_24 [English]
*
* @author Highway of Life ( David Lewis ) http://startrekguide.com
* @package language
* @version $Id$
* @copyright (c) 2008 Star Trek Guide Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
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

$lang = array_merge($lang, array(
	// BEGIN MOD guests last 24 hours
	'GUEST_ONLINE_24'		=> '<strong>%1$s</strong> Guest and ',
	'GUESTS_ONLINE_24'		=> '<strong>%1$s</strong> Guests and ',
	'GUESTS_INTRO'			=> 'There have been  ',
	// END MOD guests last 24 hours
));

?>