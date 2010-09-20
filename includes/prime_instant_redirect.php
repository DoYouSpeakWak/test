<?php
/**
*
* @package phpBB3
* @version $Id: prime_instant_redirect.php,v 1.0.0 2010/06/05 17:31:00 primehalo Exp $
* @copyright (c) 2010 Ken F. Innes IV
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
global $prime_instant_redirect;
if (!class_exists('prime_instant_redirect'))
{
	/**
	* Class structure
	*/
	class prime_instant_redirect
	{
		/**
		* Constructor
		*/
		function prime_instant_redirect()
		{
		}

		/**
		*/
		function redirect($url, $time)
		{
			if ($time == 3)
			{
				redirect($url, false);
			}
		}
	}
	// End class

	$prime_instant_redirect = new prime_instant_redirect();
}
?>