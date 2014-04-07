<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2013
 * @author     Leo Feyer <https://contao.org>
 * @package    System
 * @license    LGPL
 * @filesource
 */


/**
 * Define the root path to the Contao installation
 */
define('TL_ROOT', dirname(dirname(__FILE__)));


/**
 * Include functions, constants and interfaces
 */
require(TL_ROOT . '/system/functions.php');
require(TL_ROOT . '/system/constants.php');
require(TL_ROOT . '/system/interface.php');


/**
 * Try to disable PHPSESSID
 */
@ini_set('session.use_trans_sid', 0);


/**
 * Set the error and exception handler
 */
@set_error_handler('__error');
@set_exception_handler('__exception');


/**
 * Log PHP errors
 */
@ini_set('error_log', TL_ROOT . '/system/logs/error.log');


/**
 * Define the relative path to the installation (see #5339)
 */
$objEnvironment = Environment::getInstance();

if (file_exists(TL_ROOT . '/system/config/pathconfig.php') && !defined('TL_INSTALL'))
{
	define('TL_PATH', include TL_ROOT . '/system/config/pathconfig.php');
}
elseif (TL_MODE == 'BE')
{
	define('TL_PATH', preg_replace('/\/contao\/[a-z]+\.php$/i', '', $objEnvironment->scriptName));
}
else
{
	define('TL_PATH', null); // cannot be reliably determined
}


/**
 * Start the session
 */
@session_set_cookie_params(0, (TL_PATH ? TL_PATH : '/')); // see #5339
@session_start();


/**
 * Load the basic classes
 */
$objConfig = Config::getInstance();
$objInput = Input::getInstance();
$objToken = RequestToken::getInstance();


/**
 * Set the website path (backwards compatibility)
 */
$GLOBALS['TL_CONFIG']['websitePath'] = TL_PATH;


/**
 * Set error_reporting
 */
@ini_set('display_errors', ($GLOBALS['TL_CONFIG']['displayErrors'] ? 1 : 0));
error_reporting(($GLOBALS['TL_CONFIG']['displayErrors'] || $GLOBALS['TL_CONFIG']['logErrors'] ? E_ALL|E_STRICT : 0));


/**
 * Set the timezone
 */
@ini_set('date.timezone', $GLOBALS['TL_CONFIG']['timeZone']);
@date_default_timezone_set($GLOBALS['TL_CONFIG']['timeZone']);


/**
 * Set the mbstring encoding
 */
if (USE_MBSTRING && function_exists('mb_regex_encoding'))
{
	mb_regex_encoding($GLOBALS['TL_CONFIG']['characterSet']);
}


/**
 * Set the default language
 */
if ($objInput->post('language'))
{
	$GLOBALS['TL_LANGUAGE'] = $objInput->post('language');
}
elseif (isset($_SESSION['TL_LANGUAGE']))
{
	$GLOBALS['TL_LANGUAGE'] = $_SESSION['TL_LANGUAGE'];
}
else
{
	foreach ($objEnvironment->httpAcceptLanguage as $v)
	{
		if (is_dir(TL_ROOT . '/system/modules/backend/languages/' . $v))
		{
			$GLOBALS['TL_LANGUAGE'] = $v;
			$_SESSION['TL_LANGUAGE'] = $v;
			break;
		}
	}

	unset($v);
}


/**
 * Include the custom initialization file
 */
if (file_exists(TL_ROOT . '/system/config/initconfig.php'))
{
	include(TL_ROOT . '/system/config/initconfig.php');
}


/**
 * Check the request token upon POST requests
 */
if ($_POST && !$GLOBALS['TL_CONFIG']['disableRefererCheck'] && !defined('BYPASS_TOKEN_CHECK'))
{
	// Exit if the token cannot be validated
	if (!$objToken->validate($objInput->post('REQUEST_TOKEN')))
	{
		// Force JavaScript redirect upon Ajax requests (IE requires absolute link)
		if ($objEnvironment->isAjaxRequest)
		{
			echo '<script>location.replace("' . $objEnvironment->base . 'contao/index.php")</script>';
		}
		else
		{
			// Send an error 400 header if it is not an Ajax request
			header('HTTP/1.1 400 Bad Request');

			if (file_exists(TL_ROOT . '/templates/be_referer.html5'))
			{
				include(TL_ROOT . '/templates/be_referer.html5');
			}
			elseif (file_exists(TL_ROOT . '/system/modules/backend/templates/be_referer.html5'))
			{
				include(TL_ROOT . '/system/modules/backend/templates/be_referer.html5');
			}
			else
			{
				echo 'Invalid request token. Please <a href="javascript:window.location.href=window.location.href">go back</a> and try again.';
			}
		}

		exit;
	}
}

?>