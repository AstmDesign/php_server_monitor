<?php
/**
 * PHP Server Monitor
 * Monitor your servers and websites.
 *
 * This file is part of PHP Server Monitor.
 * PHP Server Monitor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PHP Server Monitor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PHP Server Monitor.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     phpservermon
 * @author      Pepijn Over <pep@neanderthal-technology.com>
 * @copyright   Copyright (c) 2008-2014 Pepijn Over <pep@neanderthal-technology.com>
 * @license     http://www.gnu.org/licenses/gpl.txt GNU GPL v3
 * @version     Release: v3.1.1
 * @link        http://www.phpservermonitor.org/
 * @since       phpservermon 2.1.0
 **/

// Include paths
define('PSM_PATH_SRC', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('PSM_PATH_INC', PSM_PATH_SRC . 'includes' . DIRECTORY_SEPARATOR);
define('PSM_PATH_TPL', PSM_PATH_SRC . 'templates' . DIRECTORY_SEPARATOR);
define('PSM_PATH_LANG', PSM_PATH_SRC . 'lang' . DIRECTORY_SEPARATOR);

// user levels
define('PSM_USER_ADMIN', 10);
define('PSM_USER_USER', 20);
define('PSM_USER_ANONYMOUS', 30);

if(function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
	date_default_timezone_set(@date_default_timezone_get());
}

// find config file
$path_conf = PSM_PATH_SRC . '../config.php';
if(file_exists($path_conf)) {
	include_once $path_conf;
}
// check for a debug var
if(!defined('PSM_DEBUG')) {
	define('PSM_DEBUG', false);
}
if(PSM_DEBUG) {
	error_reporting(E_ALL);
	ini_set('display_erors', 1);
} else {
	error_reporting(0);
	ini_set('display_errors', 0);
}

$vendor_autoload = PSM_PATH_SRC . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if(!file_exists($vendor_autoload)) {
	die('No dependencies found in vendor dir. Did you install the dependencies? Please run "php composer.phar install".');
}
require_once $vendor_autoload;

// set autoloader, make sure to set $prepend = true so that our autoloader is called first
spl_autoload_register(function($class) {
	// remove leading \
	$class = ltrim($class, '\\');
	$path_parts = explode('\\', $class);

	$filename = array_pop($path_parts);
	$path = PSM_PATH_SRC . implode(DIRECTORY_SEPARATOR, $path_parts) .
			DIRECTORY_SEPARATOR .
			$filename . '.class.php'
	;
	if(file_exists($path)) {
		require_once $path;
		return;
	}
});

// auto-find all include files
$includes = glob(PSM_PATH_INC . '*.inc.php');
foreach($includes as $file) {
	include_once $file;
}
// init db connection
$db = new psm\Service\Database();

// sanity check!
if(!defined('PSM_INSTALL') || !PSM_INSTALL) {
	if($db->getDbHost() === null) {
		// no config file has been loaded, redirect the user to the install
		header('Location: install.php');
		die();
	}
	// config file has been loaded, check if we have a connection
	if(!$db->status()) {
		die('Unable to establish database connection...');
	}
	// attempt to load configuration from database
	if(!psm_load_conf()) {
		// unable to load from config table
		header('Location: install.php');
		die();
	}
	// config load OK, make sure database version is up to date
	$installer = new \psm\Util\Install\Installer($db);
	if($installer->isUpgradeRequired()) {
		die('Your database is for an older version and requires an upgrade, <a href="install.php">please click here</a> to update your database to the latest version.');
	}
}

$lang = psm_get_conf('language', 'en_US');
psm_load_lang($lang);