<?php
$sitePath = rtrim(dirname(__FILE__, 7), '/');
$siteUrl = rtrim(dirname($_SERVER['PHP_SELF'], 7), '/');

require $sitePath . '/_prepend.php';

\Tk\Config::instance()->set('base.path', $sitePath);
\Tk\Config::instance()->set('base.url', $siteUrl);

// This is a standalone entry point (bootstraps _prepend.php directly, never
// routed through the app's HttpKernel/controllers), so it must gate access
// itself - without this, any request carrying a valid session cookie could
// read/write/upload arbitrary files under the data path.
if (!\Bs\Auth::getAuthUser()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Optional exec path settings (Default is called with command name only)
$bin = '/bin';
define('ELFINDER_TAR_PATH',      $bin.'/tar');
define('ELFINDER_GZIP_PATH',     $bin.'/gzip');
// define('ELFINDER_BZIP2_PATH',    $bin.'/bzip2');
// define('ELFINDER_XZ_PATH',       $bin.'/xz');
define('ELFINDER_ZIP_PATH',      $bin.'/zip');
define('ELFINDER_UNZIP_PATH',    $bin.'/unzip');
// define('ELFINDER_RAR_PATH',      $bin.'/rar');
// define('ELFINDER_UNRAR_PATH',    $bin.'/unrar');
// define('ELFINDER_7Z_PATH',       $bin.'/7za');
// define('ELFINDER_CONVERT_PATH',  $bin.'/convert');
// define('ELFINDER_IDENTIFY_PATH', $bin.'/identify');
// define('ELFINDER_EXIFTRAN_PATH', $bin.'/exiftran');
// define('ELFINDER_JPEGTRAN_PATH', $bin.'/jpegtran');
// define('ELFINDER_FFMPEG_PATH',   $bin.'/ffmpeg');

if (\Tk\Config::isDev()) {
    define('ELFINDER_DEBUG_ERRORLEVEL', -1); // Error reporting level of debug mode
}

// elFinder autoload
require dirname(__FILE__,6) . '/studio-42/elfinder/php/autoload.php';
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
 */
function access($attr, $path, $data, $volume, $isDir, $relpath) {
	$basename = basename($path);
	return $basename[0] === '.'                  // if file/folder begins with '.' (dot)
			 && strlen($relpath) !== 1           // but with out volume root
		? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
		:  null;                                 // else elFinder decide it itself
}

// ========== Setup data-elfinder-path =============
// NOTE: The custom path sent to the GET request should be relative to the `/data` path
function getElfinderPath(string $customDataPath = '/media'): array
{
    global $sitePath, $siteUrl;
    $customDataPath = trim(str_replace(array('..', './', '.\\', "\n", "\r"), '', $customDataPath));
    $customDataPath = rtrim($customDataPath, '/');
    $dataPath = $sitePath . \Tk\Config::getDataPath() . $customDataPath;
    $dataUrl = $siteUrl . \Tk\Config::getDataPath() . $customDataPath;
    if (!is_dir($dataPath)) {
        mkdir($dataPath, 0777, true);
    }
    if (!is_dir($dataPath . '/.trash/')) {
        mkdir($dataPath . '/.trash/', 0777, true);
    }
    return [rtrim($dataPath, '/'), rtrim($dataUrl, '/')];
}

[$dataPath, $dataUrl] = getElfinderPath($_REQUEST['cpth'] ?? '/media');


// Documentation for connector options:
// https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
$opts = array(
	'debug' => true,
	'roots' => array(
		// Items volume
		'Media' => array(
			'driver'        => 'LocalFileSystem',           // driver for accessing file system (REQUIRED)
            'path'          => $dataPath . '/',             // path to files (REQUIRED)
            'URL'           => $dataUrl  . '/',             // URL to files (REQUIRED)
			'trashHash'     => 't1_Lw',                     // elFinder's hash of trash folder
			'winHashFix'    => DIRECTORY_SEPARATOR !== '/', // to make hash same to Linux one on windows too
			'uploadDeny'    => array('all'),                // All Mimetypes not allowed to upload
            'uploadAllow'   => array('image', 'video', 'audio', 'text/plain', 'model', 'font', 'application', 'text/vcard',
                'application/pdf', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/msword', 'application/vnd.ms-word'),
			'uploadOrder'   => array('deny', 'allow'),      //
			'accessControl' => 'access'                     //
		),
		// Trash volume
		'Trash' => array(
			'id'            => '1',
			'driver'        => 'Trash',
            'path'          => $dataPath . '/.trash/',
            'tmbURL'        => $dataUrl . '/.trash/.tmb/',
			'winHashFix'    => DIRECTORY_SEPARATOR !== '/', // to make hash same to Linux one on windows too
			'uploadDeny'    => array('all'),                // Recommend the same settings as the original volume that uses the trash
            'uploadAllow'   => array('image', 'video', 'audio', 'text/plain', 'model', 'font', 'application', 'text/vcard',
                'application/pdf', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/msword', 'application/vnd.ms-word'),    // Mimetype `image` and `text/plain` allowed to upload
			'uploadOrder'   => array('deny', 'allow'),      //
			'accessControl' => 'access',                    //
		),
    ),
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
