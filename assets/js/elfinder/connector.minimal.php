<?php
$sitePath = dirname(__FILE__, 7);
$siteUrl = dirname($_SERVER['PHP_SELF'], 7);
require $sitePath . '/_prepend.php';

error_reporting(0); // Set E_ALL for debugging

// elFinder autoload
require dirname(__FILE__,6) . '/studio-42/elfinder/php/autoload.php';


// ========== EMS III Setup =======================

$config = \Tk\Config::instance();
$customDataPath = '/media';
if (isset($_REQUEST['path'])) {
    $customDataPath = trim(strip_tags(str_replace(array('..', './', '.\\', "\n", "\r"), '', $_REQUEST['path'])));
}
list($dataPath, $dataUrl) = getElfinderPath($customDataPath);

// ===============================================


function getElfinderPath(string $customDataPath = '/media'): array
{
    $config = \Tk\Config::instance();
    $dataPath = $config->getDataPath() . $customDataPath;
    $dataUrl = $config->getDataUrl() . $customDataPath;
    if (!is_dir($dataPath)) {
        mkdir($dataPath, 0777, true);
    }
    if (!is_dir($dataPath . '/.trash/')) {
        mkdir($dataPath . '/.trash/', 0777, true);
    }
    return array($dataPath, $dataUrl);
}



// ===============================================

// Enable FTP connector netmount
//elFinder::$netDrivers['ftp'] = 'FTP';
// ===============================================

// // Required for Dropbox network mount
// // Installation by composer
// // `composer require kunalvarma05/dropbox-php-sdk`
// // Enable network mount
// elFinder::$netDrivers['dropbox2'] = 'Dropbox2';
// // Dropbox2 Netmount driver need next two settings. You can get at https://www.dropbox.com/developers/apps
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=dropbox2&host=1"
// define('ELFINDER_DROPBOX_APPKEY',    '');
// define('ELFINDER_DROPBOX_APPSECRET', '');
// ===============================================

// // Required for Google Drive network mount
// // Installation by composer
// // `composer require google/apiclient:^2.0`
// // Enable network mount
// elFinder::$netDrivers['googledrive'] = 'GoogleDrive';
// // GoogleDrive Netmount driver need next two settings. You can get at https://console.developers.google.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=googledrive&host=1"
// define('ELFINDER_GOOGLEDRIVE_CLIENTID',     '');
// define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', '');
// // Required case of without composer
// define('ELFINDER_GOOGLEDRIVE_GOOGLEAPICLIENT', '/path/to/google-api-php-client/vendor/autoload.php');
// ===============================================

// // Required for Google Drive network mount with Flysystem
// // Installation by composer
// // `composer require nao-pon/flysystem-google-drive:~1.1 nao-pon/elfinder-flysystem-driver-ext`
// // Enable network mount
// elFinder::$netDrivers['googledrive'] = 'FlysystemGoogleDriveNetmount';
// // GoogleDrive Netmount driver need next two settings. You can get at https://console.developers.google.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=googledrive&host=1"
// define('ELFINDER_GOOGLEDRIVE_CLIENTID',     '');
// define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', '');
// ===============================================

// // Required for One Drive network mount
// //  * cURL PHP extension required
// //  * HTTP server PATH_INFO supports required
// // Enable network mount
// elFinder::$netDrivers['onedrive'] = 'OneDrive';
// // GoogleDrive Netmount driver need next two settings. You can get at https://dev.onedrive.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL/netmount/onedrive/1"
// define('ELFINDER_ONEDRIVE_CLIENTID',     '');
// define('ELFINDER_ONEDRIVE_CLIENTSECRET', '');
// ===============================================

// // Required for Box network mount
// //  * cURL PHP extension required
// // Enable network mount
// elFinder::$netDrivers['box'] = 'Box';
// // Box Netmount driver need next two settings. You can get at https://developer.box.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL"
// define('ELFINDER_BOX_CLIENTID',     '');
// define('ELFINDER_BOX_CLIENTSECRET', '');
// ===============================================


// // Zoho Office Editor APIKey
// // https://www.zoho.com/docs/help/office-apis.html
// define('ELFINDER_ZOHO_OFFICE_APIKEY', '');
// ===============================================

// // Zip Archive editor
// // Installation by composer
// // `composer require nao-pon/elfinder-flysystem-ziparchive-netmount`
// define('ELFINDER_DISABLE_ZIPEDITOR', false); // set `true` to disable zip editor
// ===============================================

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from '.' (dot)
 *
 * @param  string    $attr    attribute name (read|write|locked|hidden)
 * @param  string    $path    absolute file path
 * @param  string    $data    value of volume option `accessControlData`
 * @param  object    $volume  elFinder volume driver object
 * @param  bool|null $isDir   path is directory (true: directory, false: file, null: unknown)
 * @param  string    $relpath file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume, $isDir, $relpath) {
	$basename = basename($path);
	return $basename[0] === '.'                  // if file/folder begins with '.' (dot)
			 && strlen($relpath) !== 1           // but with out volume root
		? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
		:  null;                                 // else elFinder decide it itself
}


// Documentation for connector options:
// https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
$opts = array(
	// 'debug' => true,
	'roots' => array(
		// Items volume
		array(
            'alias'      => 'Home',
			'driver'        => 'LocalFileSystem',                // driver for accessing file system (REQUIRED)
            'path'          => $dataPath . '/',                  // path to files (REQUIRED)
            'URL'           => $dataUrl  . '/',                  // URL to files (REQUIRED)
			//'path'          => '../files/',                     // path to files (REQUIRED)
			//'URL'           => dirname($_SERVER['PHP_SELF']) . '/../files/', // URL to files (REQUIRED)
			'trashHash'     => 't1_Lw',                          // elFinder's hash of trash folder
			'winHashFix'    => DIRECTORY_SEPARATOR !== '/',      // to make hash same to Linux one on windows too
            'accessControl' => 'access',                         // disable and hide dot starting files (OPTIONAL)

            'uploadDeny'    => array('all'),
            //'uploadAllow'   => array('image', 'text/plain'),
            'uploadAllow'   => array('image', 'video', 'audio', 'text/plain', 'model', 'font', 'application',
                'application/pdf', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/msword', 'application/vnd.ms-word'),    // Mimetype `image` and `text/plain` allowed to upload
            'uploadOrder'   => array('deny', 'allow'),

		),
		// Trash volume
		array(
			'id'            => '1',
			'driver'        => 'Trash',
            'path'          => $dataPath . '/.trash/',
            'tmbURL'        => $dataUrl . '/.trash/.tmb/',
			//'path'          => '../files/.trash/',
			//'tmbURL'        => dirname($_SERVER['PHP_SELF']) . '/../files/.trash/.tmb/',
			'winHashFix'    => DIRECTORY_SEPARATOR !== '/',     // to make hash same to Linux one on windows too
            'accessControl' => 'access',                        // Same as above

            'uploadDeny'    => array('all'),
            //'uploadAllow'   => array('image', 'text/plain'),
            'uploadAllow'   => array('image', 'video', 'audio', 'text/plain', 'model', 'font', 'application',
                'application/pdf', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/msword', 'application/vnd.ms-word'),    // Mimetype `image` and `text/plain` allowed to upload
            'uploadOrder'   => array('deny', 'allow'),

		)
	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();

