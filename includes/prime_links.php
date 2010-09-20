<?php
/**
*
* @package phpBB3
* @version $Id: prime_links.php,v 1.2.8 2010/07/21 12:51:00 primehalo Exp $
* @copyright (c) 2007-2010 Ken F. Innes IV
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
if (!defined('INCLUDES_PRIME_LINKS'))
{
	define('INCLUDES_PRIME_LINKS', true);

	// Options
	define('PRIME_LINKS_ENABLE', true);			// Enable this MOD?
	define('REMOVE_SUBDOMAINS', true);			// Specify subdomains to be removed before checking the link, separated by semicolons (setting TRUE will remove all subdomains)
	define('USE_TARGET_ATTRIBUTE', false);		// The attribute "target" is not valid for STRICT doctypes.
	define('HIDE_LINKS_FROM_GUESTS', false);	// Hide external links from guests? If this is a string, then the text of the link will be replaced with this string.
	define('EXTERNAL_LINK_PREFIX', '');			// Example: 'http://anonym.to?'
	define('INTERNAL_LINK_DOMAINS', '');		// List of domains to be considered local, separated by semicolons. Example: 'http://www.alternate-domain.com/'
	define('FORBIDDEN_DOMAINS', '');			// List of domains for which links should be removed, separated by semicolons. Example: 'http://www.porn.com/'
	define('FORBIDDEN_NEW_URL', '#');			// URL to insert in place of any removed links. Example: 'http://www.google.com/' or '#top'
	define('TOP_LEVEL_DOMAIN_NAME', '');		// Recommended for domains with country codes. Example: '.co.jp'

	// Link relationships
	define('INTERNAL_LINK_REL', '');
	define('EXTERNAL_LINK_REL', 'nofollow');

	// Link targets (setting to FALSE will remove the link)
	define('INTERNAL_LINK_TARGET', '');
	define('EXTERNAL_LINK_TARGET', '_blank');

	// Link classes
	define('INTERNAL_LINK_CLASS', 'postlink-local');
	define('EXTERNAL_LINK_CLASS', 'postlink');

	// Link file types (separate file extensions with a vertical bar "|")
	define('PDF_LINK_TYPES', 'pdf');
	define('IMG_LINK_TYPES', 'gif|jpg|jpeg|png|bmp');
	define('ZIP_LINK_TYPES', 'zip|rar|7z');

	// Special cases for specific link types. Separate file extensions with a vertical bar (|).
	define('FORCE_EXTERNAL_LINK', '');			// Example 1: 'pdf|gif|jpg|jpeg|png|bmp|zip|rar|7z'
	define('FORCE_INTERNAL_LINK', '');			// Example 2: PDF_LINK_TYPES . '|' . IMG_LINK_TYPES . '|' . ZIP_LINK_TYPES
	define('NO_EXTERNAL_LINK_PREFIX', '');		// Don't add an external link prefix for these file types
	define('NO_LINK_PROCESSING', '');			// Don't process links to these file types

	// Link classes for specific file types.
	global $link_type_classes;
	$link_type_classes = array(
		PDF_LINK_TYPES	=> 'pdf_link',
		IMG_LINK_TYPES	=> 'img_link',
		ZIP_LINK_TYPES	=> 'zip_link',
	);

	/**
	*/
	function prime_links($text = null)
	{
		if (PRIME_LINKS_ENABLE)
		{
			if(is_string($text))
			{
				$prime_links = new prime_links();
				$text = $prime_links->modify_links($text);
			}
			else if (EXTERNAL_LINK_TARGET && $text === null)
			{
				global $template;
				$external_target = (USE_TARGET_ATTRIBUTE === true) ? (' target="' . EXTERNAL_LINK_TARGET . '"') : (' onclick="this.target=\'' . EXTERNAL_LINK_TARGET . '\';"');
				$template->assign_var('EXTERNAL_LINK_TARGET', $external_target);
				if (isset($template->_tpldata['forumrow']))
				{
					$prime_links = new prime_links();
					$key = sizeof($template->_tpldata['forumrow']) - 1;
					if (empty($template->_tpldata['forumrow'][$key]['S_IS_LINK']) || $prime_links->is_link_local($template->_tpldata['forumrow'][$key]['U_VIEWFORUM']))
					{
						$external_target = '';
					}
					$template->alter_block_array('forumrow', array('EXTERNAL_LINK_TARGET' => $external_target), true, 'change');
				}
			}
		}
		return($text);
	}

	/**
	*/
	class prime_links
	{
		var $top_level_domain_name;

		/**
		* Constructor
		*/
		function prime_links()
		{
			$this->top_level_domain_name = TOP_LEVEL_DOMAIN_NAME ? str_replace('.', '\\.', TOP_LEVEL_DOMAIN_NAME) : '[a-z0-9-]+\.(?:aero|biz|com|coop|info|jobs|museum|name|net|org|pro|travel|gov|edu|mil|int)';
			//$this->top_level_domain_name = (strpos($this->top_level_domain_name, '\\.') !== 0 ? '\\.' : '') . $this->top_level_domain_name;
		}

		/**
		* Decodes all HTML entities. The html_entity_decode() function doesn't decode numerical entities,
		* and the htmlspecialchars_decode() function only decodes the most common form for entities.
		*/
		function decode_entities($text)
		{
			$text = html_entity_decode($text, ENT_QUOTES, 'ISO-8859-1'); 		//UTF-8 does not work!
			$text = preg_replace('/&#(\d+);/me', 'chr($1)', $text); 			//decimal notation
			$text = preg_replace('/&#x([a-f0-9]+);/mei', 'chr(0x$1)', $text);	//hex notation
			return($text);
		}

		/**
		* Removes subdomains from a URL. If no subdomains are provided
		* as an input parameter, all subdomains will be removed.
		*/
		function remove_subdomains($url, $remove = true)
		{
			$stripped_url = $url;
			if ($remove !== false && strpos($url, '//') !== false)
			{
				$url_parts = @parse_url($url);
				if ($remove && is_string($remove))
				{
					$remove = str_replace(';', '|', $remove);
					$remove = str_replace('.', '\\.', $remove);
					$stripped_url = preg_replace('#^(http|https)://(?:' . $remove . '\.)([^/]+)#i', '$1://$2', $url);
				}
				else if (!empty($url_parts['host']) && substr($url_parts['host'], -9) == 'localhost') // Domain could have a port number, but it's too rare a case with localhost
				{
					$stripped_url = preg_replace('#^(http|https)://[^/]+\.localhost#i', '$1://localhost', $url);
				}
				else
				{
					//$top_level_domain_name = TOP_LEVEL_DOMAIN_NAME ? str_replace('.', '\\.', TOP_LEVEL_DOMAIN_NAME) : '\.(?:aero|biz|com|coop|info|jobs|museum|name|net|org|pro|travel|gov|edu|mil|int)';
					//$top_level_domain_name = (strpos($top_level_domain_name, '\\.') !== 0 ? '\\.' : '') . $top_level_domain_name;
					$stripped_url = preg_replace('#^(http|https)://[^/]+\.(' . $this->top_level_domain_name . ')#i', '$1://$2', $url);
					if ($stripped_url == $url)
					{
						$stripped_url = preg_replace('#^(http|https)://[^/]+?\.([a-z0-9-]+\.(?:\.[a-z])\.(?:ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bl|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mf|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw))#i', '$1://$2', $url);
						//$stripped_url = preg_replace('#^(http|https)://[^/]+?\.([a-z0-9-]+(?:\.[a-z]{2})?\.(?:ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bl|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mf|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw))#i', '$1://$2', $url);
					}
				}
			}
			return($stripped_url);
		}

		/**
		* Determine if the URL contains a domain.
		* $domains	: list of domains (an array or a string separated by semicolons)
		* $remove	: list of subdomains to remove (or TRUE/FALSE to remove all/none)
		*/
		function match_domain($url, $domains, $remove = true)
		{
			$domain_list = is_string($domains) ? explode(';', $domains) : $domains;
			foreach ($domain_list as $domain)
			{
				$domain = (strpos($domain, 'http') === 0) ? $domain : 'http://' . $domain;
				$domain = $this->remove_subdomains($domain, $remove);
				if (strpos($url, utf8_case_fold_nfc($domain)) === 0)
				{
					return true;
				}
			}
			return false;
		}

		/**
		* Determines if a URL is local or external. If no valid-ish scheme is found,
		* assume a relative (thus internal) link that happens to contain a colon (:).
		*/
		function is_link_local($url, $board_url = false)
		{
			$url = strtolower($url);
			if ($board_url === false)
			{
				$board_url = generate_board_url(true);
				$board_url = $this->remove_subdomains($board_url, REMOVE_SUBDOMAINS);
			}
			$board_url = strtolower($board_url);

			// Treat http and https as the same scheme
			$board_url	= (strpos($board_url, 'https://') === 0) ? ('http' . substr($board_url, 5)) : $board_url;
			$url 		= (strpos($url		, 'https://') === 0) ? ('http' . substr($url, 		5)) : $url;

			// Compare the URLs
			if (!($is_local = (strpos($url, $board_url) === 0)))
			{
				// If there is no scheme, then it's probably a relative, local link
				$scheme = substr($url, 0, strpos($url, ':'));
				//$is_local = !$scheme || ($scheme && !in_array($scheme, array('http', 'https', 'mailto', 'ftp', 'gopher')));
				$is_local = !$scheme || ($scheme && !preg_match('/^[a-z0-9.]{2,16}$/i', $scheme));
			}

			// Not local, now check forced local domains
			if (!$is_local && INTERNAL_LINK_DOMAINS)
			{
				$is_local = $this->match_domain($url, INTERNAL_LINK_DOMAINS, REMOVE_SUBDOMAINS);
			}
			return($is_local);
		}

		/**
		* Removes an attribute from an HTML tag.
		*/
		function remove_attribute($attr_name, $html_tag)
		{
			$html_tag = preg_replace('/\s+' . $attr_name . '="[^"]*"/i', '', $html_tag);
			return $html_tag;
		}

		/**
		* Insert an attribute into an HTML tag.
		*/
		function insert_attribute($attr_name, $new_attr, $html_tag, $overwrite = false)
		{
			$javascript	= (strpos($attr_name, 'on') === 0);	// onclick, onmouseup, onload, etc.
			$old_attr	= preg_replace('/^.*' . $attr_name . '="([^"]*)".*$/i', '$1', $html_tag);
			$is_attr	= !($old_attr == $html_tag);		// Does the attribute already exist?
			$old_attr	= ($is_attr) ? $old_attr : '';

			if ($javascript)
			{
				if ($is_attr && !$overwrite)
				{
					$old_attr = ($old_attr && ($last_char = substr(trim($old_attr), -1)) && $last_char != '}' && $last_char != ';') ? $old_attr . ';' : $old_attr; // Ensure we can add code after any existing code
					$new_attr = $old_attr . $new_attr;
				}
				$overwrite = true;
			}

			if ($overwrite && is_string($overwrite))
			{
				if (strpos(' ' . $overwrite . ' ', ' ' . $old_attr . ' ') !== false)
				{
					// Overwrite the specified value if it exists, otherwise just append the value.
					$new_attr = trim(str_replace(' '  . $overwrite . ' ', ' ' . $new_attr . ' ', ' '  . $old_attr . ' '));
				}
				else
				{
					$overwrite = false;
				}
			}
			if (!$overwrite)
			{
				 // Append the new one if it's not already there.
				$new_attr = strpos(' ' . $old_attr . ' ', ' ' . $new_attr . ' ') === false ? trim($old_attr . ' ' . $new_attr) : $old_attr;
			}

			$html_tag = $is_attr ? str_replace("$attr_name=\"$old_attr\"", "$attr_name=\"$new_attr\"", $html_tag) : str_replace('>', " $attr_name=\"$new_attr\">", $html_tag);
			return($html_tag);
		}

		/**
		* Modify links within a block of text.
		*/
		function modify_links($message = '')
		{
			// A quick check before we start using regular expressions
			if (strpos($message, '<a ') === false)
			{
				return($message);
			}
			global $user, $link_type_classes;

			$board_url = generate_board_url(true);
			$board_url = $this->remove_subdomains($board_url, REMOVE_SUBDOMAINS);
			preg_match_all('#(<a\s[^>]+?>)(.*?</a>)#i', $message, $matches, PREG_SET_ORDER);
			foreach ($matches as $links)
			{
				$link = $new_link = $links[1];
				$href = preg_replace('/^.*href="([^"]*)".*$/i', '$1', $link);
				if ($href == $link) //no link was found
				{
					continue;
				}
				$href	= $this->decode_entities($href);
				$scheme	= substr($href, 0, strpos($href, ':'));
				if ($scheme)
				{
					$scheme = strtolower($scheme);
					if ($scheme != 'http' && $scheme != 'https') // Only classify links for these schemes (or no scheme)
					{
						continue;
					}
				}
				$external_prefix = EXTERNAL_LINK_PREFIX;

				if (NO_LINK_PROCESSING && preg_match('/\.(?:' . NO_LINK_PROCESSING . ')(?:[#?]|$)/', $href))
				{
					continue;
				}

				$is_local = null;
				$is_local = (FORCE_INTERNAL_LINK && preg_match('/\.(?:' . FORCE_INTERNAL_LINK . ')(?:[#?]|$)/', $href)) ? true : $is_local;
				$is_local = (FORCE_EXTERNAL_LINK && preg_match('/\.(?:' . FORCE_EXTERNAL_LINK . ')(?:[#?]|$)/', $href)) ? false : $is_local;
				if ($is_local === null)
				{
					$href = $this->remove_subdomains($href, REMOVE_SUBDOMAINS);
					if (FORBIDDEN_DOMAINS && $this->match_domain($href, FORBIDDEN_DOMAINS, REMOVE_SUBDOMAINS))
					{
						$searches[]		= $link;
						$replacements[]	= $this->insert_attribute('href', FORBIDDEN_NEW_URL, $new_link, true);
						continue;
					}
					$is_local = $this->is_link_local($href, $board_url);
				}
				$new_class	= $is_local ? INTERNAL_LINK_CLASS  : EXTERNAL_LINK_CLASS;
				$new_target	= $is_local ? INTERNAL_LINK_TARGET : EXTERNAL_LINK_TARGET;
				$new_rel	= $is_local ? INTERNAL_LINK_REL    : EXTERNAL_LINK_REL;

				// Check if this link needs a special class based on the type of file to which it points.
				foreach ($link_type_classes as $extensions => $class)
				{
					if ($class && $extensions && preg_match('/\.(?:' . $extensions . ')(?:[#?]|$)/', $href))
					{
						$new_class .= ' ' . $class;
						break;
					}
				}
				if ($new_class)
				{
					$new_link = $this->insert_attribute('class', $new_class, $new_link, 'postlink');
				}
				if ($new_rel)
				{
					$new_link = $this->insert_attribute('rel', $new_rel, $new_link);
				}
				if ($new_target)
				{
					if (USE_TARGET_ATTRIBUTE === true)
					{
						$new_link = $this->insert_attribute('target', $new_target, $new_link, true);
					}
					else
					{
						$new_link = $this->insert_attribute('onclick', "this.target='$new_target';", $new_link);
					}
				}
				// Remove the link?
				if ($new_target === false || (HIDE_LINKS_FROM_GUESTS && !$is_local && !$user->data['is_registered']))
				{
					$new_text = is_string(HIDE_LINKS_FROM_GUESTS) ? HIDE_LINKS_FROM_GUESTS : substr($links[2], 0, -4);
					$new_link = '<span class="link_removed">' . $new_text . '</span>';
					$link = $links[0];
				}
				if (!$is_local && $external_prefix)
				{
					$external_prefix = (NO_EXTERNAL_LINK_PREFIX && preg_match('/\.(?:' . NO_EXTERNAL_LINK_PREFIX . ')(?:[#?]|$)/', $href)) ? '' : $external_prefix;
					$new_link = str_replace('href="', 'href="' . $external_prefix, $new_link);
				}
				$searches[]		= $link;
				$replacements[]	= $new_link;
			}
			if (isset($searches) && isset($replacements))
			{
				$message = str_replace($searches, $replacements, $message);
			}
			return($message);
		}
	}
}
?>