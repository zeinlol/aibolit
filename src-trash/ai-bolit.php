<?php
///////////////////////////////////////////////////////////////////////////
// Updated by Nick Borshchov
// Created and developed by Greg Zemskov, Revisium Company
// Email: ai@revisium.com, http://revisium.com/ai/, skype: greg_zemskov

// Commercial usage is not allowed without a license purchase or written permission of the author
// Source code and signatures usage is not allowed

// Certificated in Federal Institute of Industrial Property in 2012
// http://revisium.com/ai/i/mini_aibolit.jpg

////////////////////////////////////////////////////////////////////////////
include 'page_components.php';
include 'defines/program_defines.php';
ini_set('memory_limit', '1G');
//@mb_internal_encoding('');

$int_enc = @ini_get('mbstring.internal_encoding');


if ((isset($_SERVER['OS']) && stripos('Win', $_SERVER['OS']) !== false)/* && stripos('CygWin', $_SERVER['OS']) === false)*/) {
    define('DIR_SEPARATOR', '\\');
}

include 'defines/g_variables.php';
include 'langs/english.php';
if (LANG == 'RU') {
    include 'langs/russian.php';
} else {
    include 'langs/english.php';
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$g_AiBolitAbsolutePath = dirname(__FILE__);

if (file_exists($g_AiBolitAbsolutePath . '/ai-design.html')) {
    $l_Template = file_get_contents($g_AiBolitAbsolutePath . '/ai-design.html');
}

$l_Template = str_replace('@@MAIN_TITLE@@', AI_STR_001, $l_Template);

include 'sig.php';

if (!isCli() && !isset($_SERVER['HTTP_USER_AGENT'])) {
    echo "#####################################################\n";
    echo "# Error: cannot run on php-cgi. Requires php as cli #\n";
    echo "#                                                   #\n";
    echo "# See FAQ: http://revisium.com/ai/faq.php           #\n";
    echo "#####################################################\n";
    exit;
}


if (version_compare(phpversion(), '5.3.1', '<')) {
    echo "#####################################################\n";
    echo "# Warning: PHP Version < 5.3.1                      #\n";
    echo "# Some function might not work properly             #\n";
    echo "# See FAQ: http://revisium.com/ai/faq.php           #\n";
    echo "#####################################################\n";
    exit;
}

if (!(function_exists("file_put_contents") && is_callable("file_put_contents"))) {
    echo "#####################################################\n";
    echo "file_put_contents() is disabled. Cannot proceed.\n";
    echo "#####################################################\n";
    exit;
}

include 'defines/another_data.php';

if (!isCli()) {
    $defaults['site_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/';
}

define('CRC32_LIMIT', pow(2, 31) - 1);
define('CRC32_DIFF', CRC32_LIMIT * 2 - 2);

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
srand(time());

set_time_limit(0);
ini_set('max_execution_time', '900000');
ini_set('realpath_cache_size', '16M');
ini_set('realpath_cache_ttl', '1200');
ini_set('pcre.backtrack_limit', '150000');
ini_set('pcre.recursion_limit', '150000');

if (!function_exists('stripos')) {
    function stripos($par_Str, $par_Entry, $Offset = 0)
    {
        return strpos(strtolower($par_Str), strtolower($par_Entry), $Offset);
    }
}
include 'cms_data/cms_defines.php';
include 'cms_data/cms_classes.php';
include 'core/engine_functions.php';
include 'core/print_data.php';
include 'core/serializer.php';

$l_FastCli = false;
if (isCli()) {

    $cli_options = array(
        'm:' => 'memory:',
        's:' => 'size:',
        'a' => 'all',
        'd:' => 'delay:',
        'l:' => 'list:',
        'r:' => 'report:',
        'f' => 'fast',
        'j:' => 'file:',
        'p:' => 'path:',
        'q' => 'quite',
        'e:' => 'cms:',
        'x:' => 'mode:',
        'k:' => 'skip:',
        'i:' => 'idb:',
        'n' => 'sc',
        'h' => 'help'
    );

    $cli_longopts = array(
        'cmd:',
        'noprefix:',
        'addprefix:',
        'scan:',
        'one-pass',
        'quarantine',
        'with-2check',
        'skip-cache',
        'imake',
        'icheck',
        'lang'
    );
    $cli_longopts = array_merge($cli_longopts, array_values($cli_options));

    $options = getopt(implode('', array_keys($cli_options)), $cli_longopts);

    if (isset($options['h']) or isset($options['help'])) {
        $memory_limit = ini_get('memory_limit');
        echo <<<HELP
        AI-Bolit - Script to search for shells and other malicious software.
        
        Usage: php {$_SERVER['PHP_SELF']} [OPTIONS] [PATH]
        Current default path is: {$defaults['path']}
        
          -j, --file=FILE      Full path to single file to check
          -l, --list=FILE      Full path to create plain text file with a list of found malware
          -p, --path=PATH      Directory path to scan, by default the file directory is used
                               Current path: {$defaults['path']}
          -m, --memory=SIZE    Maximum amount of memory a script may consume. Current value: $memory_limit
                               Can take shorthand byte values (1M, 1G...)
          -s, --size=SIZE      Scan files are smaller than SIZE. 0 - All files. Current value: {$defaults['max_size_to_scan']}
          -a, --all            Scan all files (by default scan. js,. php,. html,. htaccess)
          -d, --delay=INT      delay in milliseconds when scanning files to reduce load on the file system (Default: 1)
          -x, --mode=INT       Set scan mode. 0 - for basic, 1 - for expert and 2 for paranoic.
          -k, --skip=jpg,...   Skip specific extensions. E.g. --skip=jpg,gif,png,xls,pdf
              --scan=php,...   Scan only specific extensions. E.g. --scan=php,htaccess,js
          -r, --report=PATH/EMAILS
                               Full path to create report or email address to send report to.
                               You can also specify multiple email separated by commas.
          -q, 		       Use only with -j. Quiet result check of file, 1=Infected 
              --cmd="command [args...]"
                               Run command after scanning
              --one-pass       Do not calculate remaining time
              --quarantine     Archive all malware from report
              --with-2check    Create or use AI-BOLIT-DOUBLECHECK.php file
              --imake
              --icheck
              --idb=file	   Integrity Check database file
              --lang           Choose lang for output. RU for Russian, EN for English. Default is English
        
              --help           Display this help and exit
        
        * Mandatory arguments listed below are required for both full and short way of usage.
        
        HELP;
        exit;
    }

    $l_FastCli = false;
    if (isset($options['lang'])) {
        $lang = $options['lang'];
        if ($lang == 'RU') {
            define('LANG', 'RU');
        } else {
            define('LANG', 'EN');
        }
    } else {
        define('LANG', 'EN');
    }
    echo LANG;
    if (
        (isset($options['memory']) and !empty($options['memory']) and ($memory = $options['memory']))
        or (isset($options['m']) and !empty($options['m']) and ($memory = $options['m']))
    ) {
        $memory = getBytes($memory);
        if ($memory > 0) {
            $defaults['memory_limit'] = $memory;
            ini_set('memory_limit', $memory);
        }
    }

    if (
        (isset($options['file']) and !empty($options['file']) and ($file = $options['file']) !== false)
        or (isset($options['j']) and !empty($options['j']) and ($file = $options['j']) !== false)
    ) {
        define('SCAN_FILE', $file);
    }


    if (
        (isset($options['list']) and !empty($options['list']) and ($file = $options['list']) !== false)
        or (isset($options['l']) and !empty($options['l']) and ($file = $options['l']) !== false)
    ) {

        define('PLAIN_FILE', $file);
    }
    if (
        (isset($options['size']) and !empty($options['size']) and ($size = $options['size']) !== false)
        or (isset($options['s']) and !empty($options['s']) and ($size = $options['s']) !== false)
    ) {
        $size = getBytes($size);
        $defaults['max_size_to_scan'] = $size > 0 ? $size : 0;
    }

    if (
        (isset($options['file']) and !empty($options['file']) and ($file = $options['file']) !== false)
        or (isset($options['j']) and !empty($options['j']) and ($file = $options['j']) !== false)
        and (isset($options['q']))

    ) {
        $BOOL_RESULT = true;
    }

    if (isset($options['f'])) {
        $l_FastCli = true;
    }

    if (
        (isset($options['delay']) and !empty($options['delay']) and ($delay = $options['delay']) !== false)
        or (isset($options['d']) and !empty($options['d']) and ($delay = $options['d']) !== false)
    ) {
        $delay = (int)$delay;
        if (!($delay < 0)) {
            $defaults['scan_delay'] = $delay;
        }
    }

    if (
        (isset($options['skip']) and !empty($options['skip']) and ($ext_list = $options['skip']) !== false)
        or (isset($options['k']) and !empty($options['k']) and ($ext_list = $options['k']) !== false)
    ) {
        $defaults['skip_ext'] = $ext_list;
    }

    if (isset($options['n']) or isset($options['skip-cache'])) {
        $defaults['skip_cache'] = true;
    }

    if (isset($options['all']) or isset($options['a'])) {
        $defaults['scan_all_files'] = 1;
    }

    if (isset($options['scan'])) {
        $ext_list = strtolower(trim($options['scan'], " ,\t\n\r\0\x0B"));
        if ($ext_list != '') {
            $l_FastCli = true;
            $g_SensitiveFiles = explode(",", $ext_list);
            stdOut("Scan extensions: " . $ext_list);
            $g_SpecificExt = true;
        }
    }


    if (isset($options['cms'])) {
        define('CMS', $options['cms']);
    } else if (isset($options['e'])) {
        define('CMS', $options['e']);
    }

    if (isset($options['x'])) {
        define('AI_EXPERT', $options['x']);
    } else if (isset($options['mode'])) {
        define('AI_EXPERT', $options['mode']);
    } else {
        define('AI_EXPERT', AI_EXPERT_MODE);
    }

    $l_SpecifiedPath = false;
    if (
        (isset($options['path']) and !empty($options['path']) and ($path = $options['path']) !== false)
        or (isset($options['p']) and !empty($options['p']) and ($path = $options['p']) !== false)
    ) {
        $defaults['path'] = $path;
        $l_SpecifiedPath = true;
    }

    if (
        isset($options['noprefix']) and !empty($options['noprefix']) and ($g_NoPrefix = $options['noprefix']) !== false) {
    } else {
        $g_NoPrefix = '';
    }

    if (
        isset($options['addprefix']) and !empty($options['addprefix']) and ($g_AddPrefix = $options['addprefix']) !== false) {
    } else {
        $g_AddPrefix = '';
    }


    $l_SuffixReport = str_replace('/var/www', '', $defaults['path']);
    $l_SuffixReport = str_replace('/home', '', $l_SuffixReport);
    $l_SuffixReport = preg_replace('#[/\\\.\s]#', '_', $l_SuffixReport);
    $l_SuffixReport .= "-" . rand(1, 999999);

    if (
        (isset($options['report']) and ($report = $options['report']) !== false)
        or (isset($options['r']) and ($report = $options['r']) !== false)
    ) {
        $report = str_replace('@PATH@', $l_SuffixReport, $report);
        $report = str_replace('@RND@', rand(1, 999999), $report);
        $report = str_replace('@DATE@', date('d-m-Y-h-i'), $report);
        define('REPORT', $report);
    }

    if (
        (isset($options['idb']) and ($ireport = $options['idb']) !== false)
    ) {
        $ireport = str_replace('@PATH@', $l_SuffixReport, $ireport);
        $ireport = str_replace('@RND@', rand(1, 999999), $ireport);
        $ireport = str_replace('@DATE@', date('d-m-Y-h-i'), $ireport);
        define('INTEGRITY_DB_FILE', $ireport);
    }


    $l_ReportDirName = dirname($report);
    define('QUEUE_FILENAME', ($l_ReportDirName != '' ? $l_ReportDirName . '/' : '') . 'AI-BOLIT-QUEUE-' . md5($defaults['path']) . '.txt');

    defined('REPORT') or define('REPORT', 'AI-BOLIT-REPORT-' . $l_SuffixReport . '-' . date('d-m-Y_H-i') . '.html');

    defined('INTEGRITY_DB_FILE') or define('INTEGRITY_DB_FILE', 'AINTEGRITY-' . $l_SuffixReport . '-' . date('d-m-Y_H-i'));

    $last_arg = max(1, sizeof($_SERVER['argv']) - 1);
    if (isset($_SERVER['argv'][$last_arg])) {
        $path = $_SERVER['argv'][$last_arg];
        if (
            substr($path, 0, 1) != '-'
            and (substr($_SERVER['argv'][$last_arg - 1], 0, 1) != '-' or array_key_exists(substr($_SERVER['argv'][$last_arg - 1], -1), $cli_options))) {
            $defaults['path'] = $path;
        }
    }


    define('ONE_PASS', isset($options['one-pass']));

    define('IMAKE', isset($options['imake']));
    define('ICHECK', isset($options['icheck']));

    if (IMAKE && ICHECK) die('One of the following options must be used --imake or --icheck.');

} else {
    define('AI_EXPERT', AI_EXPERT_MODE);
    define('ONE_PASS', true);
}

OptimizeSignatures();

if (!defined('PLAIN_FILE')) {
    define('PLAIN_FILE', '');
}

// Init
include 'defines/max_values.php';

// Perform full scan when running from command line
if (isCli() || isset($_GET['full'])) {
    $defaults['scan_all_files'] = 1;
}

if ($l_FastCli) {
    $defaults['scan_all_files'] = 0;
}

if (!isCli()) {
    define('ICHECK', isset($_GET['icheck']));
    define('IMAKE', isset($_GET['imake']));

    define('INTEGRITY_DB_FILE', 'ai-integrity-db');
}

define('SCAN_ALL_FILES', (bool)$defaults['scan_all_files']);
define('SCAN_DELAY', (int)$defaults['scan_delay']);
define('MAX_SIZE_TO_SCAN', getBytes($defaults['max_size_to_scan']));

if ($defaults['memory_limit'] and ($defaults['memory_limit'] = getBytes($defaults['memory_limit'])) > 0) {
    ini_set('memory_limit', $defaults['memory_limit']);
    stdOut("Changed memory limit to " . $defaults['memory_limit']);
}

define('START_TIME', microtime(true));

define('ROOT_PATH', realpath($defaults['path']));

if (!ROOT_PATH) {
    if (isCli()) {
        die(stdOut("Directory '{$defaults['path']}' not found!"));
    }
} elseif (!is_readable(ROOT_PATH)) {
    if (isCli()) {
        die(stdOut("Cannot read directory '" . ROOT_PATH . "'!"));
    }
}

define('CURRENT_DIR', getcwd());
chdir(ROOT_PATH);

// Проверяем отчет
if (isCli() and REPORT !== '' and !getEmails(REPORT)) {
    $report = str_replace('\\', '/', REPORT);
    $abs = strpos($report, '/') === 0 ? DIR_SEPARATOR : '';
    $report = array_values(array_filter(explode('/', $report)));
    $report_file = array_pop($report);
    $report_path = realpath($abs . implode(DIR_SEPARATOR, $report));

    define('REPORT_FILE', $report_file);
    define('REPORT_PATH', $report_path);

    if (REPORT_FILE and REPORT_PATH and is_file(REPORT_PATH . DIR_SEPARATOR . REPORT_FILE)) {
        @unlink(REPORT_PATH . DIR_SEPARATOR . REPORT_FILE);
    }
}


if (function_exists('phpinfo')) {
    ob_start();
    phpinfo();
    $l_PhpInfo = ob_get_contents();
    ob_end_clean();

    $l_PhpInfo = str_replace('border: 1px', '', $l_PhpInfo);
    preg_match('|<body>(.*)</body>|smi', $l_PhpInfo, $l_PhpInfoBody);
}

////////////////////////////////////////////////////////////////////////////
$l_Template = str_replace("@@MODE@@", AI_EXPERT . '/' . SMART_SCAN, $l_Template);

if (AI_EXPERT == 0) {
    $l_Result .= '<div class="rep">' . AI_STR_057 . '</div>';
} else {
}

$l_Template = str_replace('@@HEAD_TITLE@@', AI_STR_051 . $g_AddPrefix . str_replace($g_NoPrefix, '', ROOT_PATH), $l_Template);

//const QCR_INDEX_FILENAME = 'fn';
//const QCR_INDEX_TYPE = 'type';
//const QCR_INDEX_WRITABLE = 'wr';
//const QCR_SVALUE_FILE = '1';
//const QCR_SVALUE_FOLDER = '0';

include 'core/getters.php';
include "QCR/qcr_func.php";

function needIgnore($par_FN, $par_CRC): bool {
    global $g_IgnoreList;

    for ($i = 0; $i < count($g_IgnoreList); $i++) {
        if (strpos($par_FN, $g_IgnoreList[$i][0]) !== false) {
            if ($par_CRC == $g_IgnoreList[$i][1]) {
                return true;
            }
        }
    }

    return false;
}

function makeSafeFn($par_Str, $replace_path = false): string {
    global $g_AddPrefix, $g_NoPrefix;
    if ($replace_path) {
        $lines = explode("\n", $par_Str);
        array_walk($lines, function (&$n) {
            global $g_AddPrefix, $g_NoPrefix;
            $n = $g_AddPrefix . str_replace($g_NoPrefix, '', $n);
        });

        $par_Str = implode("\n", $lines);
    }

    return htmlspecialchars($par_Str, ENT_SUBSTITUTE | ENT_QUOTES);
}

function replacePathArray($par_Arr)
{
    global $g_AddPrefix, $g_NoPrefix;
    array_walk($par_Arr, function (&$n) {
        global $g_AddPrefix, $g_NoPrefix;
        $n = $g_AddPrefix . str_replace($g_NoPrefix, '', $n);
    });

    return $par_Arr;
}

function extractValue(&$par_Str, $par_Name) {
    if (preg_match('|<tr><td class="e">\s*' . $par_Name . '\s*</td><td class="v">(.+?)</td>|sm', $par_Str, $l_Result)) {
        return str_replace('no value', '', strip_tags($l_Result[1]));
    }
}

function addSlash($dir): string {
    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function escapedHexToHex($escaped): string {
    $GLOBALS['g_EncObfu']++;
    return chr(hexdec($escaped[1]));
}

function escapedOctDec($escaped): string {
    $GLOBALS['g_EncObfu']++;
    return chr(octdec($escaped[1]));
}

//function escapedDec($escaped): string {
//    $GLOBALS['g_EncObfu']++;
//    return chr($escaped[1]);
//}

if (!defined('T_ML_COMMENT')) {
    define('T_ML_COMMENT', T_COMMENT);
} else {
    define('T_DOC_COMMENT', T_ML_COMMENT);
}

function UnwrapObfu($par_Content)
{
    $GLOBALS['g_EncObfu'] = 0;

    $search = array(' ;', ' =', ' ,', ' .', ' (', ' )', ' {', ' }', '; ', '= ', ', ', '. ', '( ', '( ', '{ ', '} ', ' !', ' >', ' <', ' _', '_ ', '< ', '> ', ' $', ' %', '% ', '# ', ' #', '^ ', ' ^', ' &', '& ', ' ?', '? ');
    $replace = array(';', '=', ',', '.', '(', ')', '{', '}', ';', '=', ',', '.', '(', ')', '{', '}', '!', '>', '<', '_', '_', '<', '>', '$', '%', '%', '#', '#', '^', '^', '&', '&', '?', '?');
    $par_Content = str_replace('@', '', $par_Content);
    $par_Content = preg_replace('~\s+~', ' ', $par_Content);
    $par_Content = str_replace($search, $replace, $par_Content);
    $par_Content = preg_replace_callback('~\bchr\(\s*([0-9a-fA-FxX]+)\s*\)~', function ($m) {
        return "'" . chr(intval($m[1], 0)) . "'";
    }, $par_Content);

    $par_Content = preg_replace_callback('/\\\\x([a-fA-F0-9]{1,2})/i', 'escapedHexToHex', $par_Content);
    $par_Content = preg_replace_callback('/\\\\([0-9]{1,3})/i', 'escapedOctDec', $par_Content);

    $par_Content = preg_replace('/[\'"]\s*?\.+\s*?[\'"]/smi', '', $par_Content);
    return preg_replace('/[\'"]\s*?\++\s*?[\'"]/smi', '', $par_Content);
}

// Unicode BOM is U+FEFF, but after encoded, it will look like this.
define('UTF32_BIG_ENDIAN_BOM', chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
define('UTF16_BIG_ENDIAN_BOM', chr(0xFE) . chr(0xFF));
define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
define('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));

function detect_utf_encoding($text)
{
    $first2 = substr($text, 0, 2);
    $first3 = substr($text, 0, 3);
    $first4 = substr($text, 0, 3);

    if ($first3 == UTF8_BOM) return 'UTF-8';
    elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
    elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
    elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
    elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';

    return false;
}

function knowUrl($par_URL): bool {
    global $g_UrlIgnoreList;

    for ($jk = 0; $jk < count($g_UrlIgnoreList); $jk++) {
        if (stripos($par_URL, $g_UrlIgnoreList[$jk]) !== false) {
            return true;
        }
    }

    return false;
}

function makeSummary($par_Str, $par_Number, $par_Style): string {
    return '<tr><td class="' . $par_Style . '" width=400>' . $par_Str . '</td><td class="' . $par_Style . '">' . $par_Number . '</td></tr>';
}

function CheckVulnerability($par_Filename, $par_Index, $par_Content) {
    global $g_Vulnerable, $g_CmsListDetector;

    $l_Vuln = array();

    $par_Filename = strtolower($par_Filename);


    if (
        (strpos($par_Filename, 'libraries/joomla/session/session.php') !== false) &&
        (strpos($par_Content, '&& filter_var($_SERVER[\'HTTP_X_FORWARDED_FOR') === false)
    ) {
        $l_Vuln['id'] = 'RCE : https://docs.joomla.org/Security_hotfixes_for_Joomla_EOL_versions';
        $l_Vuln['ndx'] = $par_Index;
        $g_Vulnerable[] = $l_Vuln;
        return true;
    }

    if (
        (strpos($par_Filename, 'administrator/components/com_media/helpers/media.php') !== false) &&
        (strpos($par_Content, '$format == \'\' || $format == false ||') === false)
    ) {
        if ($g_CmsListDetector->isCms(CMS_JOOMLA, '1.5')) {
            $l_Vuln['id'] = 'AFU : https://docs.joomla.org/Security_hotfixes_for_Joomla_EOL_versions';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if (
        (strpos($par_Filename, 'joomla/filesystem/file.php') !== false) &&
        (strpos($par_Content, '$file = rtrim($file, \'.\');') === false)
    ) {
        if ($g_CmsListDetector->isCms(CMS_JOOMLA, '1.5')) {
            $l_Vuln['id'] = 'AFU : https://docs.joomla.org/Security_hotfixes_for_Joomla_EOL_versions';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if ((strpos($par_Filename, 'editor/filemanager/upload/test.html') !== false) ||
        (stripos($par_Filename, 'editor/filemanager/browser/default/connectors/php/') !== false) ||
        (stripos($par_Filename, 'editor/filemanager/connectors/uploadtest.html') !== false) ||
        (strpos($par_Filename, 'editor/filemanager/browser/default/connectors/test.html') !== false)) {
        $l_Vuln['id'] = 'AFU : FCKEDITOR : http://www.exploit-db.com/exploits/17644/ & /exploit/249';
        $l_Vuln['ndx'] = $par_Index;
        $g_Vulnerable[] = $l_Vuln;
        return true;
    }

    if ((strpos($par_Filename, 'inc_php/image_view.class.php') !== false) ||
        (strpos($par_Filename, '/inc_php/framework/image_view.class.php') !== false)) {
        if (strpos($par_Content, 'showImageByID') === false) {
            $l_Vuln['id'] = 'AFU : REVSLIDER : http://www.exploit-db.com/exploits/35385/';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if ((strpos($par_Filename, 'elfinder/php/connector.php') !== false) ||
        (strpos($par_Filename, 'elfinder/elfinder.') !== false)) {
        $l_Vuln['id'] = 'AFU : elFinder';
        $l_Vuln['ndx'] = $par_Index;
        $g_Vulnerable[] = $l_Vuln;
        return true;
    }

    if (strpos($par_Filename, 'includes/database/database.inc') !== false) {
        if (strpos($par_Content, 'foreach ($data as $i => $value)') !== false) {
            $l_Vuln['id'] = 'SQLI : DRUPAL : CVE-2014-3704';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if (strpos($par_Filename, 'engine/classes/min/index.php') !== false) {
        if (strpos($par_Content, 'tr_replace(chr(0)') === false) {
            $l_Vuln['id'] = 'AFD : MINIFY : CVE-2013-6619';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if ((strpos($par_Filename, 'timthumb.php') !== false) ||
        (strpos($par_Filename, 'thumb.php') !== false) ||
        (strpos($par_Filename, 'cache.php') !== false) ||
        (strpos($par_Filename, '_img.php') !== false)) {
        if (strpos($par_Content, 'code.google.com/p/timthumb') !== false && strpos($par_Content, '2.8.14') === false) {
            $l_Vuln['id'] = 'RCE : TIMTHUMB : CVE-2011-4106,CVE-2014-4663';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if (strpos($par_Filename, 'components/com_rsform/helpers/rsform.php') !== false) {
        if (strpos($par_Content, 'eval($form->ScriptDisplay);') !== false) {
            $l_Vuln['id'] = 'RCE : RSFORM : rsform.php, LINE 1605';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if (strpos($par_Filename, 'fancybox-for-wordpress/fancybox.php') !== false) {
        if (strpos($par_Content, '\'reset\' == $_REQUEST[\'action\']') !== false) {
            $l_Vuln['id'] = 'CODE INJECTION : FANCYBOX';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }


    if (strpos($par_Filename, 'cherry-plugin/admin/import-export/upload.php') !== false) {
        if (strpos($par_Content, 'verify nonce') === false) {
            $l_Vuln['id'] = 'AFU : Cherry Plugin';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }


    if (strpos($par_Filename, 'tiny_mce/plugins/tinybrowser/tinybrowser.php') !== false) {
        $l_Vuln['id'] = 'AFU : TINYMCE : http://www.exploit-db.com/exploits/9296/';
        $l_Vuln['ndx'] = $par_Index;
        $g_Vulnerable[] = $l_Vuln;

        return true;
    }

    if (strpos($par_Filename, 'scripts/setup.php') !== false) {
        if (strpos($par_Content, 'PMA_Config') !== false) {
            $l_Vuln['id'] = 'CODE INJECTION : PHPMYADMIN : http://1337day.com/exploit/5334';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if (strpos($par_Filename, '/uploadify.php') !== false) {
        if (strpos($par_Content, 'move_uploaded_file($tempFile,$targetFile') !== false) {
            $l_Vuln['id'] = 'AFU : UPLOADIFY : CVE: 2012-1153';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if (strpos($par_Filename, 'com_adsmanager/controller.php') !== false) {
        if (strpos($par_Content, 'move_uploaded_file($file[\'tmp_name\'], $tempPath.\'/\'.basename($file[') !== false) {
            $l_Vuln['id'] = 'AFU : https://revisium.com/ru/blog/adsmanager_afu.html';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }

    if (strpos($par_Filename, 'wp-content/plugins/wp-mobile-detector/resize.php') !== false) {
        if (strpos($par_Content, 'file_put_contents($path, file_get_contents($_REQUEST[\'src\']));') !== false) {
            $l_Vuln['id'] = 'AFU : https://www.pluginvulnerabilities.com/2016/05/31/aribitrary-file-upload-vulnerability-in-wp-mobile-detector/';
            $l_Vuln['ndx'] = $par_Index;
            $g_Vulnerable[] = $l_Vuln;
            return true;
        }

        return false;
    }


}

function AddResult($l_Filename, $i)
{
    global $g_Structure, $g_CRC;

    $l_Stat = stat($l_Filename);
    $g_Structure['n'][$i] = $l_Filename;
    $g_Structure['s'][$i] = $l_Stat['size'];
    $g_Structure['c'][$i] = $l_Stat['ctime'];
    $g_Structure['m'][$i] = $l_Stat['mtime'];
    $g_Structure['crc'][$i] = $g_CRC;
}

function WarningPHP($l_FN, $l_Content, &$l_Pos, &$l_SigId)
{
    global $g_SusDB, $g_ExceptFlex, $gXX_FlexDBShe, $gX_FlexDBShe, $g_FlexDBShe, $gX_DBShe, $g_DBShe, $g_Base64, $g_Base64Fragment;

    $l_Res = false;

    if (AI_EXTRA_WARN) {
        foreach ($g_SusDB as $l_Item) {
            if (preg_match('#(' . $l_Item . ')#smiS', $l_Content, $l_Found, PREG_OFFSET_CAPTURE)) {
                if (!CheckException($l_Content, $l_Found)) {
                    $l_Pos = $l_Found[0][1];
                    //$l_SigId = myCheckSum($l_Item);
                    $l_SigId = getSigId($l_Found);
                    return true;
                }
            }
        }
    }

    if (AI_EXPERT < 2) {
        foreach ($gXX_FlexDBShe as $l_Item) {
            if (preg_match('#(' . $l_Item . ')#smiS', $l_Content, $l_Found, PREG_OFFSET_CAPTURE)) {
                $l_Pos = $l_Found[0][1];
                //$l_SigId = myCheckSum($l_Item);
                $l_SigId = getSigId($l_Found);
                return true;
            }
        }

    }

    if (AI_EXPERT < 1) {
        foreach ($gX_FlexDBShe as $l_Item) {
            if (preg_match('#(' . $l_Item . ')#smiS', $l_Content, $l_Found, PREG_OFFSET_CAPTURE)) {
                $l_Pos = $l_Found[0][1];
                //$l_SigId = myCheckSum($l_Item);
                $l_SigId = getSigId($l_Found);
                return true;
            }
        }

        $l_Content_lo = strtolower($l_Content);

        foreach ($gX_DBShe as $l_Item) {
            $l_Pos = strpos($l_Content_lo, $l_Item);
            if ($l_Pos !== false) {
                $l_SigId = myCheckSum($l_Item);
                return true;
            }
        }
    }

}

function Adware($l_FN, $l_Content, &$l_Pos)
{
    global $g_AdwareSig;

    $l_Res = false;

    foreach ($g_AdwareSig as $l_Item) {
        $offset = 0;
        while (preg_match('#(' . $l_Item . ')#smi', $l_Content, $l_Found, PREG_OFFSET_CAPTURE, $offset)) {
            if (!CheckException($l_Content, $l_Found)) {
                $l_Pos = $l_Found[0][1];
                return true;
            }

            $offset = $l_Found[0][1] + 1;
        }
    }

    return $l_Res;
}

function CheckException(&$l_Content, &$l_Found)
{
    global $g_ExceptFlex, $gX_FlexDBShe, $gXX_FlexDBShe, $g_FlexDBShe, $gX_DBShe, $g_DBShe, $g_Base64, $g_Base64Fragment;
    $l_FoundStrPlus = substr($l_Content, max($l_Found[0][1] - 10, 0), 70);

    foreach ($g_ExceptFlex as $l_ExceptItem) {
        if (@preg_match('#(' . $l_ExceptItem . ')#smi', $l_FoundStrPlus, $l_Detected)) {
//         print("\n\nEXCEPTION FOUND\n[" . $l_ExceptItem .  "]\n" . $l_Content . "\n\n----------\n\n");
            return true;
        }
    }

    return false;
}

function Phishing($l_FN, $l_Index, $l_Content, &$l_SigId)
{
    global $g_PhishingSig, $g_PhishFiles, $g_PhishEntries;

    $l_Res = false;

    // need check file (by extension) ?
    $l_SkipCheck = SMART_SCAN;

    if ($l_SkipCheck) {
        foreach ($g_PhishFiles as $l_Ext) {
            if (strpos($l_FN, $l_Ext) !== false) {
                $l_SkipCheck = false;
                break;
            }
        }
    }

    // need check file (by signatures) ?
    if ($l_SkipCheck && preg_match('~' . $g_PhishEntries . '~smiS', $l_Content, $l_Found)) {
        $l_SkipCheck = false;
    }

    if ($l_SkipCheck && SMART_SCAN) {
        if (DEBUG_MODE) {
            echo "Skipped phs file, not critical.\n";
        }

        return false;
    }


    foreach ($g_PhishingSig as $l_Item) {
        $offset = 0;
        while (preg_match('#(' . $l_Item . ')#smi', $l_Content, $l_Found, PREG_OFFSET_CAPTURE, $offset)) {
            if (!CheckException($l_Content, $l_Found)) {
                $l_Pos = $l_Found[0][1];
//           $l_SigId = myCheckSum($l_Item);
                $l_SigId = getSigId($l_Found);

                if (DEBUG_MODE) {
                    echo "Phis: $l_FN matched [$l_Item] in $l_Pos\n";
                }

                return $l_Pos;
            }
            $offset = $l_Found[0][1] + 1;

        }
    }

    return $l_Res;
}

function CriticalJS($l_FN, $l_Index, $l_Content, &$l_SigId)
{
    global $g_JSVirSig, $gX_JSVirSig, $g_VirusFiles, $g_VirusEntries;

    $l_Res = false;

    // need check file (by extension) ?
    $l_SkipCheck = SMART_SCAN;

    if ($l_SkipCheck) {
        foreach ($g_VirusFiles as $l_Ext) {
            if (strpos($l_FN, $l_Ext) !== false) {
                $l_SkipCheck = false;
                break;
            }
        }
    }

    // need check file (by signatures) ?
    if ($l_SkipCheck && preg_match('~' . $g_VirusEntries . '~smiS', $l_Content, $l_Found)) {
        $l_SkipCheck = false;
    }

    if ($l_SkipCheck && SMART_SCAN) {
        if (DEBUG_MODE) {
            echo "Skipped js file, not critical.\n";
        }

        return false;
    }


    foreach ($g_JSVirSig as $l_Item) {
        $offset = 0;
        while (preg_match('#(' . $l_Item . ')#smi', $l_Content, $l_Found, PREG_OFFSET_CAPTURE, $offset)) {
            if (!CheckException($l_Content, $l_Found)) {
                $l_Pos = $l_Found[0][1];
//           $l_SigId = myCheckSum($l_Item);
                $l_SigId = getSigId($l_Found);

                if (DEBUG_MODE) {
                    echo "JS: $l_FN matched [$l_Item] in $l_Pos\n";
                }

                return $l_Pos;
            }

            $offset = $l_Found[0][1] + 1;

        }

//   if (pcre_error($l_FN, $l_Index)) {  }

    }

    if (AI_EXPERT > 1) {
        foreach ($gX_JSVirSig as $l_Item) {
            if (preg_match('#(' . $l_Item . ')#smi', $l_Content, $l_Found, PREG_OFFSET_CAPTURE)) {
                if (!CheckException($l_Content, $l_Found)) {
                    $l_Pos = $l_Found[0][1];
                    //$l_SigId = myCheckSum($l_Item);
                    $l_SigId = getSigId($l_Found);

                    if (DEBUG_MODE) {
                        echo "JS PARA: $l_FN matched [$l_Item] in $l_Pos\n";
                    }

                    return $l_Pos;
                }
            }

//   if (pcre_error($l_FN, $l_Index)) {  }

        }
    }

    return $l_Res;
}

//function pcre_error($par_FN, $par_Index): bool
//{
//    global $g_NotRead, $g_Structure;
//
//    $err = preg_last_error();
//    if (($err == PREG_BACKTRACK_LIMIT_ERROR) || ($err == PREG_RECURSION_LIMIT_ERROR)) {
//        if (!in_array($par_Index, $g_NotRead)) {
//            $g_NotRead[] = $par_Index;
//            AddResult('[re] ' . $par_FN, $par_Index);
//        }
//
//        return true;
//    }
//
//    return false;
//}

include 'defines/susp_defines.php';

function get_descr_heur($type): string
{
    switch ($type) {
        case SUSP_MTIME:
            return AI_STR_077;
        case SUSP_PERM:
            return AI_STR_078;
        case SUSP_PHP_IN_UPLOAD:
            return AI_STR_079;
    }

    return "---";
}

  ///////////////////////////////////////////////////////////////////////////
function HeuristicChecker($l_Content, &$l_Type, $l_Filename): bool
  {
//     $res = false;
	 
	 $l_Stat = stat($l_Filename);
	 // most likely changed by touch
	 if ($l_Stat['ctime'] < $l_Stat['mtime']) {
	     $l_Type = SUSP_MTIME;
		 return true;
	 }


    $l_Perm = fileperms($l_Filename) & 0777;
    if (($l_Perm & 0400 != 0400) || // not readable by owner
        ($l_Perm == 0000) ||
        ($l_Perm == 0404) ||
        ($l_Perm == 0505)) {
        $l_Type = SUSP_PERM;
        return true;
    }


    if ((strpos($l_Filename, '.ph')) && (
            strpos($l_Filename, '/images/stories/') ||
            //strpos($l_Filename, '/img/') ||
            //strpos($l_Filename, '/images/') ||
            //strpos($l_Filename, '/uploads/') ||
            strpos($l_Filename, '/wp-content/upload/')
        )
    ) {
        $l_Type = SUSP_PHP_IN_UPLOAD;
        return true;
    }

    return false;
}

///////////////////////////////////////////////////////////////////////////
function CriticalPHP($l_FN, $l_Index, $l_Content, &$l_Pos, &$l_SigId): bool
{

    global $g_ExceptFlex, $gXX_FlexDBShe, $gX_FlexDBShe, $g_FlexDBShe, $gX_DBShe, $g_DBShe, $g_Base64, $g_Base64Fragment,
           $g_CriticalFiles, $g_CriticalEntries;

    // @@AIBOLIT_SIG_000000000000@@ H24LKHGHCGHFHGKJHGKJHGGGHJ

    // need check file (by extension) ?
    $l_SkipCheck = SMART_SCAN;

    if ($l_SkipCheck) {
        foreach ($g_CriticalFiles as $l_Ext) {
            if (strpos($l_FN, $l_Ext) !== false) {
                $l_SkipCheck = false;
                break;
            }
        }
    }

    // need check file (by signatures) ?
    if ($l_SkipCheck && preg_match('~' . $g_CriticalEntries . '~smiS', $l_Content, $l_Found)) {
        $l_SkipCheck = false;
    }


    // if not critical - skip it
    if ($l_SkipCheck && SMART_SCAN) {
        if (DEBUG_MODE) {
            echo "Skipped file, not critical.\n";
        }

        return false;
    }

    /*
      if (AI_EXPERT > 1) {
        if (strpos($l_FN, '.php.') !== false ) {
           $g_Base64[] = $l_Index;
           $g_Base64Fragment[] = '".php."';
           $l_Pos = 0;

           if (DEBUG_MODE) {
                echo "CRIT 7: $l_FN matched [$l_Item] in $l_Pos\n";
           }

           AddResult($l_FN, $l_Index);
        }
      }
    */

    foreach ($g_FlexDBShe as $l_Item) {
        $offset = 0;
        while (preg_match('#(' . $l_Item . ')#smiS', $l_Content, $l_Found, PREG_OFFSET_CAPTURE, $offset)) {
            if (!CheckException($l_Content, $l_Found)) {
                $l_Pos = $l_Found[0][1];
                //$l_SigId = myCheckSum($l_Item);
                $l_SigId = getSigId($l_Found);

                if (DEBUG_MODE) {
                    echo "CRIT 1: $l_FN matched [$l_Item] in $l_Pos\n";
                }

                return true;
            }

            $offset = $l_Found[0][1] + 1;

        }

//   if (pcre_error($l_FN, $l_Index)) {  }

    }

    if (AI_EXPERT > 1) {
        foreach ($gXX_FlexDBShe as $l_Item) {
            if (preg_match('#(' . $l_Item . ')#smiS', $l_Content, $l_Found, PREG_OFFSET_CAPTURE)) {
                if (!CheckException($l_Content, $l_Found)) {
                    $l_Pos = $l_Found[0][1];
                    //$l_SigId = myCheckSum($l_Item);
                    $l_SigId = getSigId($l_Found);

                    if (DEBUG_MODE) {
                        echo "CRIT 2: $l_FN matched [$l_Item] in $l_Pos\n";
                    }

                    return true;
                }
            }

//   if (pcre_error($l_FN, $l_Index)) {  }
        }
    }

    if (AI_EXPERT > 0) {
        foreach ($gX_FlexDBShe as $l_Item) {
            if (preg_match('#(' . $l_Item . ')#smiS', $l_Content, $l_Found, PREG_OFFSET_CAPTURE)) {
                if (!CheckException($l_Content, $l_Found)) {
                    $l_Pos = $l_Found[0][1];
                    //$l_SigId = myCheckSum($l_Item);
                    $l_SigId = getSigId($l_Found);

                    if (DEBUG_MODE) {
                        echo "CRIT 3: $l_FN matched [$l_Item] in $l_Pos\n";
                    }

                    return true;
                }
            }

//   if (pcre_error($l_FN, $l_Index)) {  }
        }
    }

    $l_Content_lo = strtolower($l_Content);

    foreach ($g_DBShe as $l_Item) {
        $l_Pos = strpos($l_Content_lo, $l_Item);
        if ($l_Pos !== false) {
            $l_SigId = myCheckSum($l_Item);

            if (DEBUG_MODE) {
                echo "CRIT 4: $l_FN matched [$l_Item] in $l_Pos\n";
            }

            return true;
        }
    }

    if (AI_EXPERT > 0) {
        foreach ($gX_DBShe as $l_Item) {
            $l_Pos = strpos($l_Content_lo, $l_Item);
            if ($l_Pos !== false) {
                $l_SigId = myCheckSum($l_Item);

                if (DEBUG_MODE) {
                    echo "CRIT 5: $l_FN matched [$l_Item] in $l_Pos\n";
                }

                return true;
            }
        }
    }

    if (AI_EXPERT > 0) {
        if ((strpos($l_Content, 'GIF89') === 0) && (strpos($l_FN, '.php') !== false)) {
            $l_Pos = 0;

            if (DEBUG_MODE) {
                echo "CRIT 6: $l_FN matched [$l_Item] in $l_Pos\n";
            }

            return true;
        }
    }

    // detect uploaders / droppers
    if (AI_EXPERT > 1) {
        $l_Found = null;
        if (
            (filesize($l_FN) < 1024) &&
            (strpos($l_FN, '.ph') !== false) &&
            (
                (($l_Pos = strpos($l_Content, 'multipart/form-data')) > 0) ||
                (($l_Pos = strpos($l_Content, '$_FILE[') > 0)) ||
                (($l_Pos = strpos($l_Content, 'move_uploaded_file')) > 0) ||
                (preg_match('|\bcopy\s*\(|smi', $l_Content, $l_Found, PREG_OFFSET_CAPTURE))
            )
        ) {
            if ($l_Found != null) {
                $l_Pos = $l_Found[0][1];
            }
            if (DEBUG_MODE) {
                echo "CRIT 7: $l_FN matched [$l_Item] in $l_Pos\n";
            }

            return true;
        }
    }

    return false;
}

///////////////////////////////////////////////////////////////////////////
if (!isCli()) {
    header('Content-type: text/html; charset=utf-8');
}

if (!isCli()) {

    $l_PassOK = false;
    if (strlen(PASS) > 8) {
        $l_PassOK = true;
    }

    if ($l_PassOK && preg_match('|[0-9]|', PASS, $l_Found) && preg_match('|[A-Z]|', PASS, $l_Found) && preg_match('|[a-z]|', PASS, $l_Found)) {
        $l_PassOK = true;
    }

    if (!$l_PassOK) {
        echo sprintf(AI_STR_009, generatePassword());
        exit;
    }

    if (isset($_GET['fn']) && ($_GET['ph'] == crc32(PASS))) {
        printFile();
        exit;
    }

    if ($_GET['p'] != PASS) {
        $generated_pass = generatePassword();
        echo sprintf(AI_STR_010, $generated_pass, $generated_pass);
        exit;
    }
}

if (!is_readable(ROOT_PATH)) {
    echo AI_STR_011;
    exit;
}

if (isCli()) {
    if (defined('REPORT_PATH') and REPORT_PATH) {
        if (!is_writable(REPORT_PATH)) {
            die("\nCannot write report. Report dir " . REPORT_PATH . " is not writable.");
        } else if (!REPORT_FILE) {
            die("\nCannot write report. Report filename is empty.");
        } else if (($file = REPORT_PATH . DIR_SEPARATOR . REPORT_FILE) and is_file($file) and !is_writable($file)) {
            die("\nCannot write report. Report file '$file' exists but is not writable.");
        }
    }
}


// detect version CMS
$g_KnownCMS = array();
$tmp_cms = array();
$g_CmsListDetector = new CmsVersionDetector(ROOT_PATH);
$l_CmsDetectedNum = $g_CmsListDetector->getCmsNumber();
for ($tt = 0; $tt < $l_CmsDetectedNum; $tt++) {
    $g_CMS[] = $g_CmsListDetector->getCmsName($tt) . ' v' . makeSafeFn($g_CmsListDetector->getCmsVersion($tt));
    $tmp_cms[strtolower($g_CmsListDetector->getCmsName($tt))] = 1;
}

if (count($tmp_cms) > 0) {
    $g_KnownCMS = array_keys($tmp_cms);
    $len = count($g_KnownCMS);
    for ($i = 0; $i < $len; $i++) {
        if ($g_KnownCMS[$i] == strtolower(CMS_WORDPRESS)) $g_KnownCMS[] = 'wp';
        if ($g_KnownCMS[$i] == strtolower(CMS_WEBASYST)) $g_KnownCMS[] = 'shopscript';
        if ($g_KnownCMS[$i] == strtolower(CMS_IPB)) $g_KnownCMS[] = 'ipb';
        if ($g_KnownCMS[$i] == strtolower(CMS_DLE)) $g_KnownCMS[] = 'dle';
        if ($g_KnownCMS[$i] == strtolower(CMS_INSTANT_CMS)) $g_KnownCMS[] = 'instantcms';
        if ($g_KnownCMS[$i] == strtolower(CMS_SHOP_SCRIPT)) $g_KnownCMS[] = 'shopscript';
    }
}


$g_DirIgnoreList = array();
if ($defaults['skip_cache']) {
    $g_DirIgnoreList[] = 'bitrix/cache/';
    $g_DirIgnoreList[] = 'bitrix/managed_cache/';
    $g_DirIgnoreList[] = 'bitrix/stack_cache/';
    $g_DirIgnoreList[] = '/\w{2}/\w{32}\.php';
}

$g_IgnoreList = array();
$g_UrlIgnoreList = array();
$g_KnownList = array();

$l_IgnoreFilename = $g_AiBolitAbsolutePath . '/.aignore';
$l_DirIgnoreFilename = $g_AiBolitAbsolutePath . '/.adirignore';
$l_UrlIgnoreFilename = $g_AiBolitAbsolutePath . '/.aurlignore';
$l_KnownFilename = '.aknown';

if (file_exists($l_IgnoreFilename)) {
    $l_IgnoreListRaw = file($l_IgnoreFilename);
    for ($i = 0; $i < count($l_IgnoreListRaw); $i++) {
        $g_IgnoreList[] = explode("\t", trim($l_IgnoreListRaw[$i]));
    }
    unset($l_IgnoreListRaw);
}

if (file_exists($l_DirIgnoreFilename)) {
    $g_DirIgnoreList = file($l_DirIgnoreFilename);

    for ($i = 0; $i < count($g_DirIgnoreList); $i++) {
        $g_DirIgnoreList[$i] = trim($g_DirIgnoreList[$i]);
    }
}

if (file_exists($l_UrlIgnoreFilename)) {
    $g_UrlIgnoreList = file($l_UrlIgnoreFilename);

    for ($i = 0; $i < count($g_UrlIgnoreList); $i++) {
        $g_UrlIgnoreList[$i] = trim($g_UrlIgnoreList[$i]);
    }
}

QCR_Debug();

// Load custom signatures

try {
    $s_file = new SplFileObject($g_AiBolitAbsolutePath . "/ai-bolit.sig");
    $s_file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
    foreach ($s_file as $line) {
        $g_FlexDBShe[] = preg_replace('~\G(?:[^#\\\\]+|\\\\.)*+\K#~', '\\#', $line); // escaping #
    }
    stdOut("Loaded " . $s_file->key() . " signatures from ai-bolit.sig");
    $s_file = null; // file handler is closed
} catch (Exception $e) {
    QCR_Debug("Import ai-bolit.sig " . $e->getMessage());
}

QCR_Debug();

$defaults['skip_ext'] = strtolower(trim($defaults['skip_ext']));
if ($defaults['skip_ext'] != '') {
    $g_IgnoredExt = explode(',', $defaults['skip_ext']);
    for ($i = 0; $i < count($g_IgnoredExt); $i++) {
        $g_IgnoredExt[$i] = trim($g_IgnoredExt[$i]);
    }

    QCR_Debug('Skip files with extensions: ' . implode(',', $g_IgnoredExt));
    stdOut('Skip extensions: ' . implode(',', $g_IgnoredExt));
}

// scan single file
if (defined('SCAN_FILE')) {
    if (file_exists(SCAN_FILE) && is_file(SCAN_FILE) && is_readable(SCAN_FILE)) {
        stdOut("Start scanning file '" . SCAN_FILE . "'.");
        QCR_ScanFile(SCAN_FILE);
    } else {
        stdOut("Error:" . SCAN_FILE . " either is not a file or readable");
    }
} else {
    if (isset($_GET['2check'])) {
        $options['with-2check'] = 1;
    }

    // scan list of files from file
    if (!(ICHECK || IMAKE) && isset($options['with-2check']) && file_exists(DOUBLECHECK_FILE)) {
        stdOut("Start scanning the list from '" . DOUBLECHECK_FILE . "'.\n");
        $lines = file(DOUBLECHECK_FILE);
        for ($i = 0, $size = count($lines); $i < $size; $i++) {
            $lines[$i] = trim($lines[$i]);
            if (empty($lines[$i])) unset($lines[$i]);
        }
        /* skip first line with <?php die("Forbidden"); ?> */
        unset($lines[0]);
        $g_FoundTotalFiles = count($lines);
        $i = 1;
        foreach ($lines as $l_FN) {
            is_dir($l_FN) && $g_TotalFolder++;
            printProgress($i++, $l_FN);
            $BOOL_RESULT = true; // display disable
            is_file($l_FN) && QCR_ScanFile($l_FN, $i);
            $BOOL_RESULT = false; // display enable
        }

        $g_FoundTotalDirs = $g_TotalFolder;
        $g_FoundTotalFiles = $g_TotalFiles;

    } else {
        // scan whole file system
        stdOut("Start scanning '" . ROOT_PATH . "'.\n");

        file_exists(QUEUE_FILENAME) && unlink(QUEUE_FILENAME);
        if (ICHECK || IMAKE) {
            // INTEGRITY CHECK
            IMAKE and unlink(INTEGRITY_DB_FILE);
            ICHECK and load_integrity_db();
            QCR_IntegrityCheck(ROOT_PATH);
            stdOut("Found $g_FoundTotalFiles files in $g_FoundTotalDirs directories.");
            if (IMAKE) exit(0);
            if (ICHECK) {
                $i = $g_Counter;
                $g_CRC = 0;
                $changes = array();
                $ref =& $g_IntegrityDB;
                foreach ($g_IntegrityDB as $l_FileName => $type) {
                    unset($g_IntegrityDB[$l_FileName]);
                    $l_Ext2 = substr(strstr(basename($l_FileName), '.'), 1);
                    if (in_array(strtolower($l_Ext2), $g_IgnoredExt)) {
                        continue;
                    }
                    for ($dr = 0; $dr < count($g_DirIgnoreList); $dr++) {
                        if (($g_DirIgnoreList[$dr] != '') && preg_match('#' . $g_DirIgnoreList[$dr] . '#', $l_FileName, $l_Found)) {
                            continue 2;
                        }
                    }
                    $type = in_array($type, array('added', 'modified')) ? $type : 'deleted';
                    $type .= substr($l_FileName, -1) == '/' ? 'Dirs' : 'Files';
                    $changes[$type][] = ++$i;
                    AddResult($l_FileName, $i);
                }
                $g_FoundTotalFiles = count($changes['addedFiles']) + count($changes['modifiedFiles']);
                stdOut("Found changes " . count($changes['modifiedFiles']) . " files and added " . count($changes['addedFiles']) . " files.");
            }

        } else {
            QCR_ScanDirectories(ROOT_PATH);
            stdOut("Found $g_FoundTotalFiles files in $g_FoundTotalDirs directories.");
        }

        QCR_Debug();
        stdOut(str_repeat(' ', 160), false);
        QCR_GoScan(0);
        unlink(QUEUE_FILENAME);
    }
}

QCR_Debug();

if (0/*PUBLIC*/) {
    $g_HeuristicDetected = array();
    $g_Iframer = array();
    $g_Base64 = array();
}


// whitelist

$snum = 0;
$list = check_whitelist($g_Structure['crc'], $snum);

foreach (array('g_CriticalPHP', 'g_CriticalJS', 'g_Iframer', 'g_Base64', 'g_Phishing', 'g_AdwareList', 'g_Redirect') as $p) {
    if (empty($$p)) continue;

    $p_Fragment = $p . "Fragment";
    $p_Sig = $p . "Sig";
    if ($p == 'g_Redirect') $p_Fragment = $p . "PHPFragment";
    if ($p == 'g_Phishing') $p_Sig = $p . "SigFragment";

    $count = count($$p);
    for ($i = 0; $i < $count; $i++) {
        $id = "{${$p}[$i]}";
        if (in_array($g_Structure['crc'][$id], $list)) {
            unset($GLOBALS[$p][$i]);
            unset($GLOBALS[$p_Sig][$i]);
            unset($GLOBALS[$p_Fragment][$i]);
        }
    }

    $$p = array_values($$p);
    $$p_Fragment = array_values($$p_Fragment);
    if (!empty($$p_Sig)) $$p_Sig = array_values($$p_Sig);
}


if (QUARANTINE_CREATE_SORTED) {
    $quarantinePath = REPORT_PATH . DIR_SEPARATOR . 'quarantine';
    foreach (array('g_CriticalPHP', 'g_CriticalJS', 'g_Phishing') as $p) {
        if (empty($$p)) continue;

        $p_Sig = $p . "Sig";
        if ($p == 'g_Phishing') $p_Sig = $p . "SigFragment";

        foreach ($$p as $k => $i) {
            if (isset($list[$g_Structure['crc'][$i]])) continue;
            $list[$g_Structure['crc'][$i]] = true;
            $k = $GLOBALS[$p_Sig][$k];
            $path = $quarantinePath . DIR_SEPARATOR . $k[0] . DIR_SEPARATOR . $k[1] . DIR_SEPARATOR . $k;
            @mkdir($path, 0777, true);
            $path .= DIR_SEPARATOR;
            $filename = basename($g_Structure['n'][$i]);
            while (is_file($path . $filename)) {
                $filename = mt_rand(0, 99) . '-' . $filename;
            }
            copy($g_Structure['n'][$i], $path . $filename);
        }
    }
}


////////////////////////////////////////////////////////////////////////////
if ($BOOL_RESULT) {
    if ((count($g_CriticalPHP) > 0) or (count($g_CriticalJS) > 0) or (count($g_Base64) > 0) or (count($g_Iframer) > 0) or (count($g_UnixExec) > 0)) {
        echo "1\n";
        exit(0);
    }
}
////////////////////////////////////////////////////////////////////////////
$l_Template = str_replace("@@SERVICE_INFO@@", htmlspecialchars("[" . $int_enc . "][" . $snum . "]"), $l_Template);

$l_Template = str_replace("@@PATH_URL@@", (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $g_AddPrefix . str_replace($g_NoPrefix, '', addSlash(ROOT_PATH))), $l_Template);

$time_taken = normalize_seconds(microtime(true) - START_TIME);

$l_Template = str_replace("@@SCANNED@@", sprintf(AI_STR_013, $g_TotalFolder, $g_TotalFiles), $l_Template);

$l_ShowOffer = false;

stdOut("\nBuilding report [ mode = " . AI_EXPERT . " ]\n");

////////////////////////////////////////////////////////////////////////////
// save 
if (!(ICHECK || IMAKE))
    if (isset($options['with-2check']) || isset($options['quarantine']))
        if ((count($g_CriticalPHP) > 0) or (count($g_CriticalJS) > 0) or (count($g_Base64) > 0) or
            (count($g_Iframer) > 0) or (count($g_UnixExec))) {
            if (!file_exists(DOUBLECHECK_FILE)) {
                if ($l_FH = fopen(DOUBLECHECK_FILE, 'w')) {
                    fputs($l_FH, '<?php die("Forbidden"); ?>' . "\n");

                    $l_CurrPath = dirname(__FILE__);

                    if (!isset($g_CriticalPHP)) {
                        $g_CriticalPHP = array();
                    }
                    if (!isset($g_CriticalJS)) {
                        $g_CriticalJS = array();
                    }
                    if (!isset($g_Iframer)) {
                        $g_Iframer = array();
                    }
                    if (!isset($g_Base64)) {
                        $g_Base64 = array();
                    }
                    if (!isset($g_Phishing)) {
                        $g_Phishing = array();
                    }
                    if (!isset($g_AdwareList)) {
                        $g_AdwareList = array();
                    }
                    if (!isset($g_Redirect)) {
                        $g_Redirect = array();
                    }

                    $tmpIndex = array_merge($g_CriticalPHP, $g_CriticalJS, $g_Phishing, $g_Base64, $g_Iframer, $g_AdwareList, $g_Redirect);
                    $tmpIndex = array_values(array_unique($tmpIndex));

                    for ($i = 0; $i < count($tmpIndex); $i++) {
                        $tmpIndex[$i] = str_replace($l_CurrPath, '.', $g_Structure['n'][$tmpIndex[$i]]);
                    }

                    for ($i = 0; $i < count($g_UnixExec); $i++) {
                        $tmpIndex[] = str_replace($l_CurrPath, '.', $g_UnixExec[$i]);
                    }

                    $tmpIndex = array_values(array_unique($tmpIndex));

                    for ($i = 0; $i < count($tmpIndex); $i++) {
                        fputs($l_FH, $tmpIndex[$i] . "\n");
                    }

                    fclose($l_FH);
                } else {
                    stdOut("Error! Cannot create " . DOUBLECHECK_FILE);
                }
            } else {
                stdOut(DOUBLECHECK_FILE . ' already exists.');
                if (AI_STR_044 != '') $l_Result .= '<div class="rep">' . AI_STR_044 . '</div>';
            }

        }

////////////////////////////////////////////////////////////////////////////

$l_Summary = '<div class="title">' . AI_STR_074 . '</div>';
$l_Summary .= '<table cellspacing=0 border=0>';

if (count($g_Redirect) > 0) {
    $l_Summary .= makeSummary(AI_STR_059, count($g_Redirect), "crit");
}

if (count($g_CriticalPHP) > 0) {
    $l_Summary .= makeSummary(AI_STR_060, count($g_CriticalPHP), "crit");
}

if (count($g_CriticalJS) > 0) {
    $l_Summary .= makeSummary(AI_STR_061, count($g_CriticalJS), "crit");
}

if (count($g_Phishing) > 0) {
    $l_Summary .= makeSummary(AI_STR_062, count($g_Phishing), "crit");
}

if (count($g_UnixExec) > 0) {
    $l_Summary .= makeSummary(AI_STR_063, count($g_UnixExec), (AI_EXPERT > 1 ? 'crit' : 'warn'));
}

if (count($g_Iframer) > 0) {
    $l_Summary .= makeSummary(AI_STR_064, count($g_Iframer), "crit");
}

if (count($g_NotRead) > 0) {
    $l_Summary .= makeSummary(AI_STR_066, count($g_NotRead), "crit");
}

if (count($g_Base64) > 0) {
    $l_Summary .= makeSummary(AI_STR_067, count($g_Base64), (AI_EXPERT > 1 ? 'crit' : 'warn'));
}

if (count($g_BigFiles) > 0) {
    $l_Summary .= makeSummary(AI_STR_065, count($g_BigFiles), "warn");
}

if (count($g_HeuristicDetected) > 0) {
    $l_Summary .= makeSummary(AI_STR_068, count($g_HeuristicDetected), "warn");
}

if (count($g_SymLinks) > 0) {
    $l_Summary .= makeSummary(AI_STR_069, count($g_SymLinks), "warn");
}

if (count($g_HiddenFiles) > 0) {
    $l_Summary .= makeSummary(AI_STR_070, count($g_HiddenFiles), "warn");
}

if (count($g_AdwareList) > 0) {
    $l_Summary .= makeSummary(AI_STR_072, count($g_AdwareList), "warn");
}

if (count($g_EmptyLink) > 0) {
    $l_Summary .= makeSummary(AI_STR_073, count($g_EmptyLink), "warn");
}

$l_Summary .= "</table><div class=details style=\"margin: 20px 20px 20px 0\">" . AI_STR_080 . "</div>\n";

$l_Template = str_replace("@@SUMMARY@@", $l_Summary, $l_Template);


$l_Result .= AI_STR_015;

$l_Template = str_replace("@@VERSION@@", AI_VERSION, $l_Template);

////////////////////////////////////////////////////////////////////////////


if (function_exists("hostname") && is_callable("gethostname")) {
    $l_HostName = gethostname();
} else {
    $l_HostName = '???';
}

$l_PlainResult = "# Malware list detected by AI-Bolit (https://revisium.com/ai/) on " . date("d/m/Y H:i:s", time()) . " " . $l_HostName . "\n\n";

stdOut("Building list of vulnerable scripts " . count($g_Vulnerable));

if (count($g_Vulnerable) > 0) {
    $l_Result .= '<div class="note_vir">' . AI_STR_081 . ' (' . count($g_Vulnerable) . ')</div><div class="crit">';
    foreach ($g_Vulnerable as $l_Item) {
        $l_Result .= '<li>' . makeSafeFn($g_Structure['n'][$l_Item['ndx']], true) . ' - ' . $l_Item['id'] . '</li>';
        $l_PlainResult .= '[VULNERABILITY] ' . replacePathArray($g_Structure['n'][$l_Item['ndx']]) . ' - ' . $l_Item['id'] . "\n";
    }

    $l_Result .= '</div><p>' . PHP_EOL;
    $l_PlainResult .= "\n";
}


stdOut("Building list of shells " . count($g_CriticalPHP));

if (count($g_CriticalPHP) > 0) {
    $l_Result .= '<div class="note_vir">' . AI_STR_016 . ' (' . count($g_CriticalPHP) . ')</div><div class="crit">';
    $l_Result .= printList($g_CriticalPHP, $g_CriticalPHPFragment, true, $g_CriticalPHPSig, 'table_crit');
    $l_PlainResult .= '[SERVER MALWARE]' . "\n" . printPlainList($g_CriticalPHP, $l_Result, $g_CriticalPHPFragment, true, $g_CriticalPHPSig, 'table_crit') . "\n";
    $l_Result .= '</div>' . PHP_EOL;

    $l_ShowOffer = true;
} else {
    $l_Result .= '<div class="ok"><b>' . AI_STR_017 . '</b></div>';
}

stdOut("Building list of js " . count($g_CriticalJS));

if (count($g_CriticalJS) > 0) {
    $l_Result .= '<div class="note_vir">' . AI_STR_018 . ' (' . count($g_CriticalJS) . ')</div><div class="crit">';
    $l_Result .= printList($g_CriticalJS, $g_CriticalJSFragment, true, $g_CriticalJSSig, 'table_vir');
    $l_PlainResult .= '[CLIENT MALWARE / JS]' . "\n" . printPlainList($g_CriticalJS, $l_Result, $g_CriticalJSFragment, true, $g_CriticalJSSig, 'table_vir') . "\n";
    $l_Result .= "</div>" . PHP_EOL;

    $l_ShowOffer = true;
}

stdOut("Building phishing pages " . count($g_Phishing));

if (count($g_Phishing) > 0) {
    $l_Result .= '<div class="note_vir">' . AI_STR_058 . ' (' . count($g_Phishing) . ')</div><div class="crit">';
    $l_Result .= printList($g_Phishing, $g_PhishingFragment, true, $g_PhishingSigFragment, 'table_vir');
    $l_PlainResult .= '[PHISHING]' . "\n" . printPlainList($g_Phishing, $l_Result, $g_PhishingFragment, true, $g_PhishingSigFragment, 'table_vir') . "\n";
    $l_Result .= "</div>" . PHP_EOL;

    $l_ShowOffer = true;
}

stdOut("Building list of iframes " . count($g_Iframer));

if (count($g_Iframer) > 0) {
    $l_ShowOffer = true;
    $l_Result .= '<div class="note_vir">' . AI_STR_021 . ' (' . count($g_Iframer) . ')</div><div class="crit">';
    $l_Result .= printList($g_Iframer, $g_IframerFragment, true);
    $l_Result .= "</div>" . PHP_EOL;

}

stdOut("Building list of base64s " . count($g_Base64));

if (count($g_Base64) > 0) {
    if (AI_EXPERT > 1) $l_ShowOffer = true;

    $l_Result .= '<div class="note_' . (AI_EXPERT > 1 ? 'vir' : 'warn') . '">' . AI_STR_020 . ' (' . count($g_Base64) . ')</div><div class="' . (AI_EXPERT > 1 ? 'crit' : 'warn') . '">';
    $l_Result .= printList($g_Base64, $g_Base64Fragment, true);
    $l_PlainResult .= '[ENCODED / SUSP_EXT]' . "\n" . printPlainList($g_Base64, $l_Result, $g_Base64Fragment, true) . "\n";
    $l_Result .= "</div>" . PHP_EOL;

}

stdOut("Building list of redirects " . count($g_Redirect));
if (count($g_Redirect) > 0) {
    $l_ShowOffer = true;
    $l_Result .= '<div class="note_vir">' . AI_STR_027 . ' (' . count($g_Redirect) . ')</div><div class="crit">';
    $l_Result .= printList($g_Redirect, $g_RedirectPHPFragment, true);
    $l_Result .= "</div>" . PHP_EOL;
}


stdOut("Building list of unread files " . count($g_NotRead));

if (count($g_NotRead) > 0) {
    $l_Result .= '<div class="note_vir">' . AI_STR_030 . ' (' . count($g_NotRead) . ')</div><div class="crit">';
    $l_Result .= printList($g_NotRead);
    $l_Result .= "</div><div class=\"spacer\"></div>" . PHP_EOL;
    $l_PlainResult .= '[SCAN ERROR / SKIPPED]' . "\n" . implode("\n", replacePathArray($g_NotRead)) . "\n\n";
}

stdOut("Building list of symlinks " . count($g_SymLinks));

if (count($g_SymLinks) > 0) {
    $l_Result .= '<div class="note_vir">' . AI_STR_022 . ' (' . count($g_SymLinks) . ')</div><div class="crit">';
    $l_Result .= nl2br(makeSafeFn(implode("\n", $g_SymLinks), true));
    $l_Result .= "</div><div class=\"spacer\"></div>";
}

stdOut("Building list of unix executables and odd scripts " . count($g_UnixExec));

if (count($g_UnixExec) > 0) {
    $l_Result .= '<div class="note_' . (AI_EXPERT > 1 ? 'vir' : 'warn') . '">' . AI_STR_019 . ' (' . count($g_UnixExec) . ')</div><div class="' . (AI_EXPERT > 1 ? 'crit' : 'warn') . '">';
    $l_Result .= nl2br(makeSafeFn(implode("\n", $g_UnixExec), true));
    $l_PlainResult .= '[UNIX EXEC]' . "\n" . implode("\n", replacePathArray($g_UnixExec)) . "\n\n";
    $l_Result .= "</div>" . PHP_EOL;

    if (AI_EXPERT > 1) $l_ShowOffer = true;
}

////////////////////////////////////
$l_WarningsNum = count($g_HeuristicDetected) + count($g_HiddenFiles) + count($g_BigFiles) + count($g_PHPCodeInside) + count($g_AdwareList) + count($g_EmptyLink) + count($g_Doorway) + (count($g_WarningPHP[0]) + count($g_WarningPHP[1]) + count($g_SkippedFolders));

if ($l_WarningsNum > 0) {
    $l_Result .= "<div style=\"margin-top: 20px\" class=\"title\">" . AI_STR_026 . "</div>";
}

stdOut("Building list of links/adware " . count($g_AdwareList));

if (count($g_AdwareList) > 0) {
    $l_Result .= '<div class="note_warn">' . AI_STR_029 . '</div><div class="warn">';
    $l_Result .= printList($g_AdwareList, $g_AdwareListFragment, true);
    $l_PlainResult .= '[ADWARE]' . "\n" . printPlainList($g_AdwareList, $l_Result, $g_AdwareListFragment, true) . "\n";
    $l_Result .= "</div>" . PHP_EOL;

}

stdOut("Building list of heuristics " . count($g_HeuristicDetected));

if (count($g_HeuristicDetected) > 0) {
    $l_Result .= '<div class="note_warn">' . AI_STR_052 . ' (' . count($g_HeuristicDetected) . ')</div><div class="warn">';
    for ($i = 0; $i < count($g_HeuristicDetected); $i++) {
        $l_Result .= '<li>' . makeSafeFn($g_Structure['n'][$g_HeuristicDetected[$i]], true) . ' (' . get_descr_heur($g_HeuristicType[$i]) . ')</li>';
    }

    $l_Result .= '</ul></div><div class=\"spacer\"></div>' . PHP_EOL;
}

stdOut("Building list of hidden files " . count($g_HiddenFiles));
if (count($g_HiddenFiles) > 0) {
    $l_Result .= '<div class="note_warn">' . AI_STR_023 . ' (' . count($g_HiddenFiles) . ')</div><div class="warn">';
    $l_Result .= nl2br(makeSafeFn(implode("\n", $g_HiddenFiles), true));
    $l_Result .= "</div><div class=\"spacer\"></div>" . PHP_EOL;
    $l_PlainResult .= '[HIDDEN]' . "\n" . implode("\n", replacePathArray($g_HiddenFiles)) . "\n\n";
}

stdOut("Building list of big files " . count($g_BigFiles));
$max_size_to_scan = getBytes(MAX_SIZE_TO_SCAN);
$max_size_to_scan = $max_size_to_scan > 0 ? $max_size_to_scan : getBytes('1m');

if (count($g_BigFiles) > 0) {
    $l_Result .= "<div class=\"note_warn\">" . sprintf(AI_STR_038, normalize_bytes($max_size_to_scan)) . '</div><div class="warn">';
    $l_Result .= printList($g_BigFiles);
    $l_Result .= "</div>";
    $l_PlainResult .= '[BIG FILES / SKIPPED]' . "\n" . printPlainList($g_BigFiles, $l_Result) . "\n\n";
}

stdOut("Building list of php inj " . count($g_PHPCodeInside));

if ((count($g_PHPCodeInside) > 0) && (($defaults['report_mask'] & REPORT_MASK_PHPSIGN) == REPORT_MASK_PHPSIGN)) {
    $l_Result .= '<div class="note_warn">' . AI_STR_028 . '</div><div class="warn">';
    $l_Result .= printList($g_PHPCodeInside, $g_PHPCodeInsideFragment, true);
    $l_Result .= "</div>" . PHP_EOL;

}

stdOut("Building list of empty links " . count($g_EmptyLink));
if (count($g_EmptyLink) > 0) {
    $l_Result .= '<div class="note_warn">' . AI_STR_031 . '</div><div class="warn">';
    $l_Result .= printList($g_EmptyLink, '', true);

    $l_Result .= AI_STR_032 . '<br/>';

    if (count($g_EmptyLink) == MAX_EXT_LINKS) {
        $l_Result .= '(' . AI_STR_033 . MAX_EXT_LINKS . ')<br/>';
    }

    for ($i = 0; $i < count($g_EmptyLink); $i++) {
        $l_Idx = $g_EmptyLink[$i];
        for ($j = 0; $j < count($g_EmptyLinkSrc[$l_Idx]); $j++) {
            $l_Result .= '<span class="details">' . makeSafeFn($g_Structure['n'][$g_EmptyLink[$i]], true) . ' &rarr; ' . htmlspecialchars($g_EmptyLinkSrc[$l_Idx][$j]) . '</span><br/>';
        }
    }

    $l_Result .= "</div>";

}

stdOut("Building list of doorways " . count($g_Doorway));

if ((count($g_Doorway) > 0) && (($defaults['report_mask'] & REPORT_MASK_DOORWAYS) == REPORT_MASK_DOORWAYS)) {
    $l_Result .= '<div class="note_warn">' . AI_STR_034 . '</div><div class="warn">';
    $l_Result .= printList($g_Doorway);
    $l_Result .= "</div>" . PHP_EOL;

}

stdOut("Building list of php warnings " . (count($g_WarningPHP[0]) + count($g_WarningPHP[1])));

if (($defaults['report_mask'] & REPORT_MASK_SUSP) == REPORT_MASK_SUSP) {
    if ((count($g_WarningPHP[0]) + count($g_WarningPHP[1])) > 0) {
        $l_Result .= '<div class="note_warn">' . AI_STR_035 . '</div><div class="warn">';

        for ($i = 0; $i < count($g_WarningPHP); $i++) {
            if (count($g_WarningPHP[$i]) > 0)
                $l_Result .= printList($g_WarningPHP[$i], $g_WarningPHPFragment[$i], true, $g_WarningPHPSig, 'table_warn' . $i);
        }
        $l_Result .= "</div>" . PHP_EOL;

    }
}

stdOut("Building list of skipped dirs " . count($g_SkippedFolders));
if (count($g_SkippedFolders) > 0) {
    $l_Result .= '<div class="note_warn">' . AI_STR_036 . '</div><div class="warn">';
    $l_Result .= nl2br(makeSafeFn(implode("\n", $g_SkippedFolders), true));
    $l_Result .= "</div>" . PHP_EOL;
}

if (count($g_CMS) > 0) {
    $l_Result .= "<div class=\"note_warn\">" . AI_STR_037 . "<br/>";
    $l_Result .= nl2br(makeSafeFn(implode("\n", $g_CMS)));
    $l_Result .= "</div>";
}


if (ICHECK) {
    $l_Result .= "<div style=\"margin-top: 20px\" class=\"title\">" . AI_STR_087 . "</div>";

    stdOut("Building list of added files " . count($changes['addedFiles']));
    if (count($changes['addedFiles']) > 0) {
        $l_Result .= '<div class="note_int">' . AI_STR_082 . ' (' . count($changes['addedFiles']) . ')</div><div class="intitem">';
        $l_Result .= printList($changes['addedFiles']);
        $l_Result .= "</div>" . PHP_EOL;
    }

    stdOut("Building list of modified files " . count($changes['modifiedFiles']));
    if (count($changes['modifiedFiles']) > 0) {
        $l_Result .= '<div class="note_int">' . AI_STR_083 . ' (' . count($changes['modifiedFiles']) . ')</div><div class="intitem">';
        $l_Result .= printList($changes['modifiedFiles']);
        $l_Result .= "</div>" . PHP_EOL;
    }

    stdOut("Building list of deleted files " . count($changes['deletedFiles']));
    if (count($changes['deletedFiles']) > 0) {
        $l_Result .= '<div class="note_int">' . AI_STR_084 . ' (' . count($changes['deletedFiles']) . ')</div><div class="intitem">';
        $l_Result .= printList($changes['deletedFiles']);
        $l_Result .= "</div>" . PHP_EOL;
    }

    stdOut("Building list of added dirs " . count($changes['addedDirs']));
    if (count($changes['addedDirs']) > 0) {
        $l_Result .= '<div class="note_int">' . AI_STR_085 . ' (' . count($changes['addedDirs']) . ')</div><div class="intitem">';
        $l_Result .= printList($changes['addedDirs']);
        $l_Result .= "</div>" . PHP_EOL;
    }

    stdOut("Building list of deleted dirs " . count($changes['deletedDirs']));
    if (count($changes['deletedDirs']) > 0) {
        $l_Result .= '<div class="note_int">' . AI_STR_086 . ' (' . count($changes['deletedDirs']) . ')</div><div class="intitem">';
        $l_Result .= printList($changes['deletedDirs']);
        $l_Result .= "</div>" . PHP_EOL;
    }
}

if (!isCli()) {
    $l_Result .= QCR_ExtractInfo($l_PhpInfoBody[1]);
}


if (function_exists('memory_get_peak_usage')) {
    $l_Template = str_replace("@@MEMORY@@", AI_STR_043 . normalize_bytes(memory_get_peak_usage()), $l_Template);
}

$l_Template = str_replace('@@WARN_QUICK@@', ((SCAN_ALL_FILES || $g_SpecificExt) ? '' : AI_STR_045), $l_Template);

if ($l_ShowOffer) {
    $l_Template = str_replace('@@OFFER@@', $l_Offer, $l_Template);
} else {
    $l_Template = str_replace('@@OFFER@@', AI_STR_002, $l_Template);
}

$l_Template = str_replace('@@CAUTION@@', AI_STR_003, $l_Template);

$l_Template = str_replace('@@CREDITS@@', AI_STR_075, $l_Template);

$l_Template = str_replace('@@FOOTER@@', AI_STR_076, $l_Template);

$l_Template = str_replace('@@STAT@@', sprintf(AI_STR_012, $time_taken, date('d-m-Y в H:i:s', floor(START_TIME)), date('d-m-Y в H:i:s')), $l_Template);

////////////////////////////////////////////////////////////////////////////
$l_Template = str_replace("@@MAIN_CONTENT@@", $l_Result, $l_Template);

if (!isCli()) {
    echo $l_Template;
    exit;
}

if (!defined('REPORT') or REPORT === '') {
    die('Report not written.');
}

// write plain text result
if (PLAIN_FILE != '') {

    $l_PlainResult = preg_replace('|__AI_LINE1__|smi', '[', $l_PlainResult);
    $l_PlainResult = preg_replace('|__AI_LINE2__|smi', '] ', $l_PlainResult);
    $l_PlainResult = preg_replace('|__AI_MARKER__|smi', ' %> ', $l_PlainResult);

    if ($l_FH = fopen(PLAIN_FILE, "w")) {
        fputs($l_FH, $l_PlainResult);
        fclose($l_FH);
    }
}

$emails = getEmails(REPORT);

if (!$emails) {
    if ($l_FH = fopen($file, "w")) {
        fputs($l_FH, $l_Template);
        fclose($l_FH);
        stdOut("\nReport written to '$file'.");
    } else {
        stdOut("\nCannot create '$file'.");
    }
} else {
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . ($defaults['email_from'] ?: 'AI-Bolit@myhost')
    );

    for ($i = 0, $size = sizeof($emails); $i < $size; $i++) {
        mail($emails[$i], 'AI-Bolit Report ' . date("d/m/Y H:i", time()), $l_Result, implode("\r\n", $headers));
    }

    stdOut("\nReport sended to " . implode(', ', $emails));
}


$time_taken = microtime(true) - START_TIME;
$time_taken = number_format($time_taken, 5);

stdOut("Scanning complete! Time taken: " . seconds2Human($time_taken));

stdOut("\n\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
stdOut("Attention! DO NOT LEAVE either ai-bolit.php or AI-BOLIT-REPORT-<xxxx>-<yy>.html \nfile on server. COPY it locally then REMOVE from server. ");
stdOut("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");

if (isset($options['quarantine'])) {
    Quarantine();
}

if (isset($options['cmd'])) {
    stdOut("Run \"{$options['cmd']}\" ");
    system($options['cmd']);
}

QCR_Debug();

# exit with code

$l_EC1 = count($g_CriticalPHP);
$l_EC2 = count($g_CriticalJS) + count($g_Phishing) + count($g_WarningPHP[0]) + count($g_WarningPHP[1]);

if ($l_EC1 > 0) {
    stdOut('Exit code 2');
    exit(2);
} else {
    if ($l_EC2 > 0) {
        stdOut('Exit code 1');
        exit(1);
    }
}

stdOut('Exit code 0');
exit(0);

############################################# END ###############################################

function Quarantine() {
    if (!file_exists(DOUBLECHECK_FILE)) {
        return;
    }

    $g_QuarantinePass = 'aibolit';

    $archive = "AI-QUARANTINE-" . rand(100000, 999999) . ".zip";
    $infoFile = substr($archive, 0, -3) . "txt";
    $report = REPORT_PATH . DIR_SEPARATOR . REPORT_FILE;
    $inf = [];
    $files = [];

    foreach (file(DOUBLECHECK_FILE) as $file) {
        $file = trim($file);
        if (!is_file($file)) continue;

        $lStat = stat($file);

        // skip files over 300KB
        if ($lStat['size'] > 300 * 1024) continue;

        // http://www.askapache.com/security/chmod-stat.html
        $p = $lStat['mode'];
        $perm = '-';
        $perm .= (($p & 0x0100) ? 'r' : '-') . (($p & 0x0080) ? 'w' : '-');
        $perm .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x') : (($p & 0x0800) ? 'S' : '-'));
        $perm .= (($p & 0x0020) ? 'r' : '-') . (($p & 0x0010) ? 'w' : '-');
        $perm .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x') : (($p & 0x0400) ? 'S' : '-'));
        $perm .= (($p & 0x0004) ? 'r' : '-') . (($p & 0x0002) ? 'w' : '-');
        $perm .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x') : (($p & 0x0200) ? 'T' : '-'));

        $owner = (function_exists('posix_getpwuid')) ? @posix_getpwuid($lStat['uid']) : array('name' => $lStat['uid']);
        $group = (function_exists('posix_getgrgid')) ? @posix_getgrgid($lStat['gid']) : array('name' => $lStat['uid']);

        $inf['permission'][] = $perm;
        $inf['owner'][] = $owner['name'];
        $inf['group'][] = $group['name'];
        $inf['size'][] = $lStat['size'] > 0 ? normalize_bytes($lStat['size']) : '-';
        $inf['ctime'][] = $lStat['ctime'] > 0 ? date("d/m/Y H:i:s", $lStat['ctime']) : '-';
        $inf['mtime'][] = $lStat['mtime'] > 0 ? date("d/m/Y H:i:s", $lStat['mtime']) : '-';
        $files = strpos($file, './') === 0 ? substr($file, 2) : $file;
    }

    // get config files for cleaning
    $configFilesRegex = 'config(uration|\.in[ic])?\.php$|dbconn\.php$';
    $configFiles = preg_grep("~$configFilesRegex~", $files);

    // get columns width
    $width = array();
    foreach (array_keys($inf) as $k) {
        $width[$k] = strlen($k);
        for ($i = 0; $i < count($inf[$k]); ++$i) {
            $len = strlen($inf[$k][$i]);
            if ($len > $width[$k])
                $width[$k] = $len;
        }
    }

    // headings of columns
    $info = '';
    foreach (array_keys($inf) as $k) {
        $info .= str_pad($k, $width[$k], ' ', STR_PAD_LEFT) . ' ';
    }
    $info .= "name\n";

    for ($i = 0; $i < count($files); ++$i) {
        foreach (array_keys($inf) as $k) {
            $info .= str_pad($inf[$k][$i], $width[$k], ' ', STR_PAD_LEFT) . ' ';
        }
        $info .= $files[$i] . "\n";
    }
    unset($inf, $width);

    exec("zip -v 2>&1", $output, $code);

    if ($code == 0) {
        $filter = '';
        if ($configFiles && exec("grep -V 2>&1", $output, $code) && $code == 0) {
            $filter = "|grep -v -E '$configFilesRegex'";
        }

        exec("cat AI-BOLIT-DOUBLECHECK.php $filter |zip -@ --password $g_QuarantinePass $archive", $output, $code);
        if ($code == 0) {
            file_put_contents($infoFile, $info);
            $m = array();
            if (!empty($filter)) {
                foreach ($configFiles as $file) {
                    $tmp = file_get_contents($file);
                    // remove  passwords
                    $tmp = preg_replace('~^.*?pass.*~im', '', $tmp);
                    // new file name
                    $file = preg_replace('~.*/~', '', $file) . '-' . rand(100000, 999999);
                    file_put_contents($file, $tmp);
                    $m[] = $file;
                }
            }

            exec("zip -j --password $g_QuarantinePass $archive $infoFile $report " . DOUBLECHECK_FILE . ' ' . implode(' ', $m));
            stdOut("\nCreate archive '" . realpath($archive) . "'");
            stdOut("This archive have password '$g_QuarantinePass'");
            foreach ($m as $file) unlink($file);
            unlink($infoFile);
            return;
        }
    }

    $zip = new ZipArchive;

    if ($zip->open($archive, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === false) {
        stdOut("Cannot create '$archive'.");
        return;
    }

    foreach ($files as $file) {
        if (in_array($file, $configFiles)) {
            $tmp = file_get_contents($file);
            // remove  passwords
            $tmp = preg_replace('~^.*?pass.*~im', '', $tmp);
            $zip->addFromString($file, $tmp);
        } else {
            $zip->addFile($file);
        }
    }
    $zip->addFile(DOUBLECHECK_FILE, DOUBLECHECK_FILE);
    $zip->addFile($report, REPORT_FILE);
    $zip->addFromString($infoFile, $info);
    $zip->close();

    stdOut("\nCreate archive '" . realpath($archive) . "'.");
    stdOut("This archive has no password!");
}

function getRelativePath($l_FileName): string
{
    return "./" . substr($l_FileName, strlen(ROOT_PATH) + 1) . (is_dir($l_FileName) ? DIR_SEPARATOR : '');
}

/**
 *
 * @return true if known and not modified
 */
function icheck($l_FileName): bool
{
    global $g_IntegrityDB;
//    static $l_Buffer = '';
    static $l_status = array('modified' => 'modified', 'added' => 'added');
//    unused variables: $g_ICheck

    $l_RelativePath = getRelativePath($l_FileName);
    $l_known = isset($g_IntegrityDB[$l_RelativePath]);

    if (is_dir($l_FileName)) {
        if ($l_known) {
            unset($g_IntegrityDB[$l_RelativePath]);
        } else {
            $g_IntegrityDB[$l_RelativePath] =& $l_status['added'];
        }
        return $l_known;
    }

    if ($l_known == false) {
        $g_IntegrityDB[$l_RelativePath] =& $l_status['added'];
        return false;
    }

    $hash = is_file($l_FileName) ? hash_file('sha1', $l_FileName) : '';

    if ($g_IntegrityDB[$l_RelativePath] != $hash) {
        $g_IntegrityDB[$l_RelativePath] =& $l_status['modified'];
        return false;
    }

    unset($g_IntegrityDB[$l_RelativePath]);
    return true;
}

function OptimizeSignatures()
{
    global $g_FlexDBShe, $gX_FlexDBShe, $gXX_FlexDBShe;
    global $g_JSVirSig, $gX_JSVirSig;
    global $g_AdwareSig;
    global $g_PhishingSig;
    global $g_ExceptFlex, $g_SusDB;
//    unused variables: $g_SusDBPrio, $g_DBShe,

    (AI_EXPERT == 2) && ($g_FlexDBShe = array_merge($g_FlexDBShe, $gX_FlexDBShe, $gXX_FlexDBShe));
    (AI_EXPERT == 1) && ($g_FlexDBShe = array_merge($g_FlexDBShe, $gX_FlexDBShe));
    $gX_FlexDBShe = $gXX_FlexDBShe = array();

    (AI_EXPERT == 2) && ($g_JSVirSig = array_merge($g_JSVirSig, $gX_JSVirSig));
    $gX_JSVirSig = array();

    $count = count($g_FlexDBShe);

    for ($i = 0; $i < $count; $i++) {
        if ($g_FlexDBShe[$i] == '[a-zA-Z0-9_]+?\(\s*[a-zA-Z0-9_]+?=\s*\)') $g_FlexDBShe[$i] = '\((?<=[a-zA-Z0-9_].)\s*[a-zA-Z0-9_]++=\s*\)';
        if ($g_FlexDBShe[$i] == '([^\?\s])\({0,1}\.[\+\*]\){0,1}\2[a-z]*e') $g_FlexDBShe[$i] = '(?J)\.[+*](?<=(?<d>[^\?\s])\(..|(?<d>[^\?\s])..)\)?\g{d}[a-z]*e';
        if ($g_FlexDBShe[$i] == '$[a-zA-Z0-9_]\{\d+\}\s*\.$[a-zA-Z0-9_]\{\d+\}\s*\.$[a-zA-Z0-9_]\{\d+\}\s*\.') $g_FlexDBShe[$i] = '\$[a-zA-Z0-9_]\{\d+\}\s*\.\$[a-zA-Z0-9_]\{\d+\}\s*\.\$[a-zA-Z0-9_]\{\d+\}\s*\.';

        $g_FlexDBShe[$i] = str_replace('http://.+?/.+?\.php\?a', 'http://[^?\s]++(?<=\.php)\?a', $g_FlexDBShe[$i]);
        $g_FlexDBShe[$i] = preg_replace('~\[a-zA-Z0-9_\]\+\K\?~', '+', $g_FlexDBShe[$i]);
        $g_FlexDBShe[$i] = preg_replace('~^\\\\[d]\+&@~', '&@(?<=\d..)', $g_FlexDBShe[$i]);
        $g_FlexDBShe[$i] = str_replace('\s*[\'"]{0,1}.+?[\'"]{0,1}\s*', '.+?', $g_FlexDBShe[$i]);
        $g_FlexDBShe[$i] = str_replace('[\'"]{0,1}.+?[\'"]{0,1}', '.+?', $g_FlexDBShe[$i]);

        $g_FlexDBShe[$i] = preg_replace('~^\[\'"\]\{0,1\}\.?|^@\*|^\\\\s\*~', '', $g_FlexDBShe[$i]);
        $g_FlexDBShe[$i] = preg_replace('~^\[\'"\]\{0,1\}\.?|^@\*|^\\\\s\*~', '', $g_FlexDBShe[$i]);
    }

    optSig($g_FlexDBShe);
    optSig($g_JSVirSig);
    optSig($g_AdwareSig);
    optSig($g_PhishingSig);
    optSig($g_SusDB);
    //optSig($g_SusDBPrio);
    //optSig($g_ExceptFlex);

    // convert exception rules
    $cnt = count($g_ExceptFlex);
    for ($i = 0; $i < $cnt; $i++) {
        $g_ExceptFlex[$i] = trim(UnwrapObfu($g_ExceptFlex[$i]));
        if (!strlen($g_ExceptFlex[$i])) unset($g_ExceptFlex[$i]);
    }

    $g_ExceptFlex = array_values($g_ExceptFlex);
}

function _hash_($text): string
{
    static $r;

    if (empty($r)) {
        for ($i = 0; $i < 256; $i++) {
            if ($i < 33 or $i > 127) $r[chr($i)] = '';
        }
    }

    return sha1(strtr($text, $r));
}

function check_whitelist($list, &$snum): array
{
    if (empty($list)) return array();

    $file = dirname(__FILE__) . '/AIBOLIT-WHITELIST.db';

    $snum = max(0, @filesize($file) - 1024) / 20;
    echo "\nLoaded " . ceil($snum) . " known files\n";

    sort($list);

    $hash = reset($list);

    $fp = @fopen($file, 'rb');

    if (false === $fp) return array();

    $header = unpack('V256', fread($fp, 1024));

    $result = array();

    foreach ($header as $chunk_id => $chunk_size) {
        if ($chunk_size > 0) {
            $str = fread($fp, $chunk_size);

            do {
                $raw = pack("H*", $hash);
                $id = ord($raw[0]) + 1;

                if ($chunk_id == $id and binarySearch($str, $raw)) {
                    $result[] = $hash;
                }

            } while ($chunk_id >= $id and $hash = next($list));

            if ($hash === false) break;
        }
    }

    fclose($fp);

    return $result;
}

function binarySearch($str, $item): bool
{
    $item_size = strlen($item);

    if ($item_size == 0) return false;

    $first = 0;

    $last = floor(strlen($str) / $item_size);

    while ($first < $last) {
        $mid = $first + (($last - $first) >> 1);
        $b = substr($str, $mid * $item_size, $item_size);
        if (strcmp($item, $b) <= 0)
            $last = $mid;
        else
            $first = $mid + 1;
    }

    $b = substr($str, $last * $item_size, $item_size);
    if ($b == $item) {
        return true;
    } else {
        return false;
    }
}
