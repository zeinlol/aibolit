<?php
define('SHORT_PHP_TAG', strtolower(ini_get('short_open_tag')) == 'on' || strtolower(ini_get('short_open_tag')) == 1);

// put 1 for expert mode, 0 for basic check and 2 for paranoia mode
// установите 1 для режима "Эксперта", 0 для быстрой проверки и 2 для параноидальной проверки (для лечения сайта)
const AI_EXPERT_MODE = 2;

// Put any strong password to open the script from web
// Впишите вместо put_any_strong_password_here сложный пароль
const PASS = '????????????????????';

const LANG = 'EN';
// define('LANG', 'RU');

const REPORT_MASK_PHPSIGN = 1;
const REPORT_MASK_SPAMLINKS = 2;
const REPORT_MASK_DOORWAYS = 4;
const REPORT_MASK_SUSP = 8;
//const REPORT_MASK_CANDI = 16;
//const REPORT_MASK_WRIT = 32;
const REPORT_MASK_FULL = REPORT_MASK_PHPSIGN | REPORT_MASK_DOORWAYS | REPORT_MASK_SUSP;
/* <-- remove this line to enable "recommendations"

| REPORT_MASK_SPAMLINKS

 remove this line to enable "recommendations" --> */

const SMART_SCAN = 0;
const AI_EXTRA_WARN = 0;
const QUARANTINE_CREATE_SORTED = 0;

$defaults = array(
    'path' => dirname(__FILE__),
    'scan_all_files' => 0, // full scan (rather than just a .js, .php, .html, .htaccess)
    'scan_delay' => 0, // delay in file scanning to reduce system load
    'max_size_to_scan' => '600K',
    'site_url' => '', // website url
    'no_rw_dir' => 0,
    'skip_ext' => '',
    'skip_cache' => false,
    'report_mask' => REPORT_MASK_FULL
);
const DEBUG_MODE = 0;
const DIR_SEPARATOR = '/';
const DOUBLECHECK_FILE = 'AI-BOLIT-DOUBLECHECK.php';
