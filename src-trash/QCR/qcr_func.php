<?php

function QCR_ExtractInfo($par_Str): string {
    $l_PhpInfoSystem = extractValue($par_Str, 'System');
    $l_PhpPHPAPI = extractValue($par_Str, 'Server API');
    $l_AllowUrlFOpen = extractValue($par_Str, 'allow_url_fopen');
    $l_AllowUrlInclude = extractValue($par_Str, 'allow_url_include');
    $l_DisabledFunction = extractValue($par_Str, 'disable_functions');
    $l_DisplayErrors = extractValue($par_Str, 'display_errors');
    $l_ErrorReporting = extractValue($par_Str, 'error_reporting');
    $l_ExposePHP = extractValue($par_Str, 'expose_php');
    $l_LogErrors = extractValue($par_Str, 'log_errors');
    $l_MQGPC = extractValue($par_Str, 'magic_quotes_gpc');
    $l_MQRT = extractValue($par_Str, 'magic_quotes_runtime');
    $l_OpenBaseDir = extractValue($par_Str, 'open_basedir');
    $l_RegisterGlobals = extractValue($par_Str, 'register_globals');
    $l_SafeMode = extractValue($par_Str, 'safe_mode');


    $l_DisabledFunction = ($l_DisabledFunction == '' ? '-?-' : $l_DisabledFunction);
    $l_OpenBaseDir = ($l_OpenBaseDir == '' ? '-?-' : $l_OpenBaseDir);

    $l_Result = '<div class="title">' . AI_STR_008 . ': ' . phpversion() . '</div>';
    $l_Result .= 'System Version: <span class="php_ok">' . $l_PhpInfoSystem . '</span><br/>';
    $l_Result .= 'PHP API: <span class="php_ok">' . $l_PhpPHPAPI . '</span><br/>';
    $l_Result .= 'allow_url_fopen: <span class="php_' . ($l_AllowUrlFOpen == 'On' ? 'bad' : 'ok') . '">' . $l_AllowUrlFOpen . '</span><br/>';
    $l_Result .= 'allow_url_include: <span class="php_' . ($l_AllowUrlInclude == 'On' ? 'bad' : 'ok') . '">' . $l_AllowUrlInclude . '</span><br/>';
    $l_Result .= 'disable_functions: <span class="php_' . ($l_DisabledFunction == '-?-' ? 'bad' : 'ok') . '">' . $l_DisabledFunction . '</span><br/>';
    $l_Result .= 'display_errors: <span class="php_' . ($l_DisplayErrors == 'On' ? 'ok' : 'bad') . '">' . $l_DisplayErrors . '</span><br/>';
    $l_Result .= 'error_reporting: <span class="php_ok">' . $l_ErrorReporting . '</span><br/>';
    $l_Result .= 'expose_php: <span class="php_' . ($l_ExposePHP == 'On' ? 'bad' : 'ok') . '">' . $l_ExposePHP . '</span><br/>';
    $l_Result .= 'log_errors: <span class="php_' . ($l_LogErrors == 'On' ? 'ok' : 'bad') . '">' . $l_LogErrors . '</span><br/>';
    $l_Result .= 'magic_quotes_gpc: <span class="php_' . ($l_MQGPC == 'On' ? 'ok' : 'bad') . '">' . $l_MQGPC . '</span><br/>';
    $l_Result .= 'magic_quotes_runtime: <span class="php_' . ($l_MQRT == 'On' ? 'bad' : 'ok') . '">' . $l_MQRT . '</span><br/>';
    $l_Result .= 'register_globals: <span class="php_' . ($l_RegisterGlobals == 'On' ? 'bad' : 'ok') . '">' . $l_RegisterGlobals . '</span><br/>';
    $l_Result .= 'open_basedir: <span class="php_' . ($l_OpenBaseDir == '-?-' ? 'bad' : 'ok') . '">' . $l_OpenBaseDir . '</span><br/>';

    if (phpversion() < '5.3.0') {
        $l_Result .= 'safe_mode (PHP < 5.3.0): <span class="php_' . ($l_SafeMode == 'On' ? 'ok' : 'bad') . '">' . $l_SafeMode . '</span><br/>';
    }

    return $l_Result . '<p>';
}

function QCR_Debug($par_Str = "") {
    if (!DEBUG_MODE) {
        return;
    }

    $l_MemInfo = ' ';
    if (function_exists('memory_get_usage')) {
        $l_MemInfo .= ' curmem=' . normalize_bytes(memory_get_usage());
    }

    if (function_exists('memory_get_peak_usage')) {
        $l_MemInfo .= ' maxmem=' . normalize_bytes(memory_get_peak_usage());
    }

    stdOut("\n" . date('H:i:s') . ': ' . $par_Str . $l_MemInfo . "\n");
}

function QCR_ScanDirectories($l_RootDir) {
    global $g_Structure, $g_Counter, $g_Doorway, $g_FoundTotalFiles, $g_FoundTotalDirs,
           $g_SkippedFolders, $g_DirIgnoreList, $g_SymLinks, $g_HiddenFiles, $g_UnixExec,
           $g_IgnoredExt, $g_SensitiveFiles, $g_SuspiciousFiles, $g_ShortListExt;
    // unused variables: $g_UnsafeFilesFound, $defaults, $g_UrlIgnoreList, $g_UnsafeDirArray,

    static $l_Buffer = '';

    $l_DirCounter = 0;
    $l_DoorwayFilesCounter = 0;
    $l_SourceDirIndex = $g_Counter - 1;

    QCR_Debug('Scan ' . $l_RootDir);

//    $l_QuotedSeparator = quotemeta(DIR_SEPARATOR);
    if ($l_DIRH = @opendir($l_RootDir)) {
        while (($l_FileName = readdir($l_DIRH)) !== false) {
            if ($l_FileName == '.' || $l_FileName == '..') continue;

            $l_FileName = $l_RootDir . DIR_SEPARATOR . $l_FileName;

            $l_Type = filetype($l_FileName);
            if ($l_Type == "link") {
                $g_SymLinks[] = $l_FileName;
                continue;
            } else
                if ($l_Type != "file" && $l_Type != "dir") {
                    if (!in_array($l_FileName, $g_UnixExec)) {
                        $g_UnixExec[] = $l_FileName;
                    }

                    continue;
                }

            $l_Ext = strtolower(pathinfo($l_FileName, PATHINFO_EXTENSION));
            $l_IsDir = is_dir($l_FileName);

            if (in_array($l_Ext, $g_SuspiciousFiles)) {
                if (!in_array($l_FileName, $g_UnixExec)) {
                    $g_UnixExec[] = $l_FileName;
                }
            }

            // which files should be scanned
            $l_NeedToScan = SCAN_ALL_FILES || (in_array($l_Ext, $g_SensitiveFiles));

            if (in_array(strtolower($l_Ext), $g_IgnoredExt)) {
                $l_NeedToScan = false;
            }

            if ($l_IsDir) {
                // if folder in ignore list
                $l_Skip = false;
                for ($dr = 0; $dr < count($g_DirIgnoreList); $dr++) {
                    if (($g_DirIgnoreList[$dr] != '') &&
                        preg_match('#' . $g_DirIgnoreList[$dr] . '#', $l_FileName, $l_Found)) {
                        $l_Skip = true;
                    }
                }

                // skip on ignore
                if ($l_Skip) {
                    $g_SkippedFolders[] = $l_FileName;
                    continue;
                }

                $l_BaseName = basename($l_FileName);

                if ((strpos($l_BaseName, '.') === 0) && ($l_BaseName != '.htaccess')) {
                    $g_HiddenFiles[] = $l_FileName;
                }

//				$g_Structure['d'][$g_Counter] = $l_IsDir;
//				$g_Structure['n'][$g_Counter] = $l_FileName;
                if (ONE_PASS) {
                    $g_Structure['n'][$g_Counter] = $l_FileName . DIR_SEPARATOR;
                } else {
                    $l_Buffer .= $l_FileName . DIR_SEPARATOR . "\n";
                }

                $l_DirCounter++;

                if ($l_DirCounter > MAX_ALLOWED_PHP_HTML_IN_DIR) {
                    $g_Doorway[] = $l_SourceDirIndex;
                    $l_DirCounter = -655360;
                }

                $g_Counter++;
                $g_FoundTotalDirs++;

                QCR_ScanDirectories($l_FileName);
            } else {
                if ($l_NeedToScan) {
                    $g_FoundTotalFiles++;
                    if (in_array($l_Ext, $g_ShortListExt)) {
                        $l_DoorwayFilesCounter++;

                        if ($l_DoorwayFilesCounter > MAX_ALLOWED_PHP_HTML_IN_DIR) {
                            $g_Doorway[] = $l_SourceDirIndex;
                            $l_DoorwayFilesCounter = -655360;
                        }
                    }

                    if (ONE_PASS) {
                        QCR_ScanFile($l_FileName, $g_Counter++);
                    } else {
                        $l_Buffer .= $l_FileName . "\n";
                    }

                    $g_Counter++;
                }
            }

            if (strlen($l_Buffer) > 32000) {
                file_put_contents(QUEUE_FILENAME, $l_Buffer, FILE_APPEND) or die("Cannot write to file " . QUEUE_FILENAME);
                $l_Buffer = '';
            }

        }

        closedir($l_DIRH);
    }

    if (($l_RootDir == ROOT_PATH) && !empty($l_Buffer)) {
        file_put_contents(QUEUE_FILENAME, $l_Buffer, FILE_APPEND) or die("Cannot write to file " . QUEUE_FILENAME);
        $l_Buffer = '';
    }

}

function QCR_IntegrityCheck($l_RootDir)
{
    global $g_Counter, $g_FoundTotalFiles, $g_FoundTotalDirs,
           $g_SkippedFolders, $g_DirIgnoreList,
           $g_SymLinks, $g_UnixExec, $g_IgnoredExt;
//    unused variables: $g_Structure, $g_Doorway, $defaults, $g_UrlIgnoreList, $g_UnsafeDirArray,
//    $g_HiddenFiles, $g_UnsafeFilesFound, $g_SuspiciousFiles, $g_IntegrityDB, $g_ICheck
    static $l_Buffer = '';

    $l_DirCounter = 0;
//    $l_DoorwayFilesCounter = 0;
//    $l_SourceDirIndex = $g_Counter - 1;

    QCR_Debug('Check ' . $l_RootDir);

    if ($l_DIRH = @opendir($l_RootDir)) {
        while (($l_FileName = readdir($l_DIRH)) !== false) {
            if ($l_FileName == '.' || $l_FileName == '..') continue;

            $l_FileName = $l_RootDir . DIR_SEPARATOR . $l_FileName;

            $l_Type = filetype($l_FileName);
            $l_IsDir = ($l_Type == "dir");
            if ($l_Type == "link") {
                $g_SymLinks[] = $l_FileName;
                continue;
            } else
                if ($l_Type != "file" && (!$l_IsDir)) {
                    $g_UnixExec[] = $l_FileName;
                    continue;
                }

            $l_Ext = substr($l_FileName, strrpos($l_FileName, '.') + 1);

            $l_NeedToScan = true;
            $l_Ext2 = substr(strstr(basename($l_FileName), '.'), 1);
            if (in_array(strtolower($l_Ext2), $g_IgnoredExt)) {
                $l_NeedToScan = false;
            }

            if (getRelativePath($l_FileName) == "./" . INTEGRITY_DB_FILE) $l_NeedToScan = false;

            if ($l_IsDir) {
                // if folder in ignore list
                $l_Skip = false;
                for ($dr = 0; $dr < count($g_DirIgnoreList); $dr++) {
                    if (($g_DirIgnoreList[$dr] != '') &&
                        preg_match('#' . $g_DirIgnoreList[$dr] . '#', $l_FileName, $l_Found)) {
                        $l_Skip = true;
                    }
                }

                // skip on ignore
                if ($l_Skip) {
                    $g_SkippedFolders[] = $l_FileName;
                    continue;
                }

                $l_BaseName = basename($l_FileName);

                $l_DirCounter++;

                $g_Counter++;
                $g_FoundTotalDirs++;

                QCR_IntegrityCheck($l_FileName);

            } else {
                if ($l_NeedToScan) {
                    $g_FoundTotalFiles++;
                    $g_Counter++;
                }
            }

            if (!$l_NeedToScan) continue;

            if (IMAKE) {
                write_integrity_db_file($l_FileName);
                continue;
            }

            // ICHECK
            // skip if known and not modified.
            if (icheck($l_FileName)) continue;

            $l_Buffer .= getRelativePath($l_FileName);
            $l_Buffer .= $l_IsDir ? DIR_SEPARATOR . "\n" : "\n";

            if (strlen($l_Buffer) > 32000) {
                file_put_contents(QUEUE_FILENAME, $l_Buffer, FILE_APPEND) or die("Cannot write to file " . QUEUE_FILENAME);
                $l_Buffer = '';
            }

        }

        closedir($l_DIRH);
    }

    if (($l_RootDir == ROOT_PATH) && !empty($l_Buffer)) {
        file_put_contents(QUEUE_FILENAME, $l_Buffer, FILE_APPEND) or die("Cannot write to file " . QUEUE_FILENAME);
        $l_Buffer = '';
    }

    if (($l_RootDir == ROOT_PATH)) {
        write_integrity_db_file();
    }

}

function QCR_ScanFile($l_Filename, $i = 0)
{
    global $g_IframerFragment, $g_Iframer, $g_Redirect, $g_Doorway, $g_EmptyLink, $g_Structure, $g_Counter,
           $g_HeuristicType, $g_HeuristicDetected, $g_TotalFolder, $g_TotalFiles, $g_WarningPHP, $g_AdwareList,
           $g_CriticalPHP, $g_Phishing, $g_CriticalJS, $g_UrlIgnoreList, $g_CriticalJSFragment, $g_PHPCodeInside, $g_PHPCodeInsideFragment,
           $g_NotRead, $g_WarningPHPFragment, $g_WarningPHPSig, $g_BigFiles, $g_RedirectPHPFragment, $g_EmptyLinkSrc, $g_CriticalPHPSig, $g_CriticalPHPFragment,
           $g_Base64Fragment, $g_UnixExec, $g_PhishingSigFragment, $g_PhishingFragment, $g_PhishingSig, $g_CriticalJSSig, $g_IframerFragment, $g_CMS, $defaults, $g_AdwareListFragment, $g_KnownList, $g_Vulnerable;

    global $g_CRC;
    static $_files_and_ignored = 0;

    $l_CriticalDetected = false;
    $l_Stat = stat($l_Filename);

    if (substr($l_Filename, -1) == DIR_SEPARATOR) {
        // FOLDER
        $g_Structure['n'][$i] = $l_Filename;
        $g_TotalFolder++;
        printProgress($_files_and_ignored, $l_Filename);
        return;
    }

    QCR_Debug('Scan file ' . $l_Filename);
    printProgress(++$_files_and_ignored, $l_Filename);

    // FILE
    if ((MAX_SIZE_TO_SCAN > 0 and $l_Stat['size'] > MAX_SIZE_TO_SCAN) || ($l_Stat['size'] < 0)) {
        $g_BigFiles[] = $i;
        AddResult($l_Filename, $i);
    } else {
        $g_TotalFiles++;

        $l_TSStartScan = microtime(true);

        if (filetype($l_Filename) == 'file') {
            $l_Content = @file_get_contents($l_Filename);
//            if (SHORT_PHP_TAG) {
////                      $l_Content = preg_replace('|<\?\s|smiS', '<?php ', $l_Content);
//            }

            $l_Unwrapped = @php_strip_whitespace($l_Filename);
        }

        $l_Ext = strtolower(pathinfo($l_Filename, PATHINFO_EXTENSION));

        if (($l_Content == '') && ($l_Stat['size'] > 0)) {
            $g_NotRead[] = $i;
            AddResult('[io] ' . $l_Filename, $i);
            return;
        }

        // ignore itself
        if (strpos($l_Content, '@@AIBOLIT_SIG_000000000000@@') !== false) {
            return;
        }

        // unix executables
        if (strpos($l_Content, chr(127) . 'ELF') !== false) {
            if (!in_array($l_Filename, $g_UnixExec)) {
                $g_UnixExec[] = $l_Filename;
            }

            return;
        }

        $g_CRC = _hash_($l_Unwrapped);


        $l_UnicodeContent = detect_utf_encoding($l_Content);
        //$l_Unwrapped = $l_Content;

        // check vulnerability in files
        $l_CriticalDetected = CheckVulnerability($l_Filename, $i, $l_Content);

        if ($l_UnicodeContent !== false) {
            if (function_exists('iconv')) {
                $l_Unwrapped = iconv($l_UnicodeContent, "CP1251//IGNORE", $l_Unwrapped);
//       			   if (function_exists('mb_convert_encoding')) {
//                                    $l_Unwrapped = mb_convert_encoding($l_Unwrapped, $l_UnicodeContent, "CP1251");
            } else {
                $g_NotRead[] = $i;
                AddResult('[ec] ' . $l_Filename, $i);
            }
        }

        $l_Unwrapped = UnwrapObfu($l_Unwrapped);

        // critical
        $g_SkipNextCheck = false;

        if (CriticalPHP($l_Filename, $i, $l_Unwrapped, $l_Pos, $l_SigId)) {
            $g_CriticalPHP[] = $i;
            $g_CriticalPHPFragment[] = getFragment($l_Unwrapped, $l_Pos);
            $g_CriticalPHPSig[] = $l_SigId;
            $g_SkipNextCheck = true;
        } else {
            if (CriticalPHP($l_Filename, $i, $l_Content, $l_Pos, $l_SigId)) {
                $g_CriticalPHP[] = $i;
                $g_CriticalPHPFragment[] = getFragment($l_Content, $l_Pos);
                $g_CriticalPHPSig[] = $l_SigId;
                $g_SkipNextCheck = true;
            }
        }

        $l_TypeDe = 0;
        if ((!$g_SkipNextCheck) && HeuristicChecker($l_Content, $l_TypeDe, $l_Filename)) {
            $g_HeuristicDetected[] = $i;
            $g_HeuristicType[] = $l_TypeDe;
            $l_CriticalDetected = true;
        }

        // critical JS
        if (!$g_SkipNextCheck) {
            $l_Pos = CriticalJS($l_Filename, $i, $l_Unwrapped, $l_SigId);
            if ($l_Pos !== false) {
                $g_CriticalJS[] = $i;
                $g_CriticalJSFragment[] = getFragment($l_Unwrapped, $l_Pos);
                $g_CriticalJSSig[] = $l_SigId;
                $g_SkipNextCheck = true;
            }
        }

        // phishing
        if (!$g_SkipNextCheck) {
            $l_Pos = Phishing($l_Filename, $i, $l_Unwrapped, $l_SigId);
            if ($l_Pos !== false) {
                $g_Phishing[] = $i;
                $g_PhishingFragment[] = getFragment($l_Unwrapped, $l_Pos);
                $g_PhishingSigFragment[] = $l_SigId;
                $g_SkipNextCheck = true;
            }
        }


        if (!$g_SkipNextCheck) {
            if (SCAN_ALL_FILES || stripos($l_Filename, 'index.')) {
                // check iframes
                if (preg_match_all('|<iframe[^>]+src.+?>|smi', $l_Unwrapped, $l_Found, PREG_SET_ORDER)) {
                    for ($kk = 0; $kk < count($l_Found); $kk++) {
                        $l_Pos = stripos($l_Found[$kk][0], 'http://');
                        $l_Pos = $l_Pos || stripos($l_Found[$kk][0], 'https://');
                        $l_Pos = $l_Pos || stripos($l_Found[$kk][0], 'ftp://');
                        if (($l_Pos !== false) && (!knowUrl($l_Found[$kk][0]))) {
                            $g_Iframer[] = $i;
                            $g_IframerFragment[] = getFragment($l_Found[$kk][0], $l_Pos);
                            $l_CriticalDetected = true;
                        }
                    }
                }

                // check empty links
                if ((($defaults['report_mask'] & REPORT_MASK_SPAMLINKS) == REPORT_MASK_SPAMLINKS) &&
                    (preg_match_all('|<a[^>]+href([^>]+?)>(.*?)</a>|smi', $l_Unwrapped, $l_Found, PREG_SET_ORDER))) {
                    for ($kk = 0; $kk < count($l_Found); $kk++) {
                        if ((stripos($l_Found[$kk][1], 'http://') !== false) &&
                            (trim(strip_tags($l_Found[$kk][2])) == '')) {

                            $l_NeedToAdd = true;

                            if ((stripos($l_Found[$kk][1], $defaults['site_url']) !== false)
                                || knowUrl($l_Found[$kk][1])) {
                                $l_NeedToAdd = false;
                            }

                            if ($l_NeedToAdd && (count($g_EmptyLink) < MAX_EXT_LINKS)) {
                                $g_EmptyLink[] = $i;
                                $g_EmptyLinkSrc[$i][] = substr($l_Found[$kk][0], 0, MAX_PREVIEW_LEN);
                                $l_CriticalDetected = true;
                            }
                        }
                    }
                }
            }

            // check for PHP code inside any type of file
            if (stripos($l_Ext, 'ph') === false) {
                $l_Pos = QCR_SearchPHP($l_Content);
                if ($l_Pos !== false) {
                    $g_PHPCodeInside[] = $i;
                    $g_PHPCodeInsideFragment[] = getFragment($l_Unwrapped, $l_Pos);
                    $l_CriticalDetected = true;
                }
            }

            // htaccess
            if (stripos($l_Filename, '.htaccess')) {

                if (stripos($l_Content, 'index.php?name=$1') !== false ||
                    stripos($l_Content, 'index.php?m=1') !== false
                ) {
                    $g_SuspDir[] = $i;
                }

                $l_HTAContent = preg_replace('|^\s*#.+$|m', '', $l_Content);

                $l_Pos = stripos($l_Content, 'auto_prepend_file');
                if ($l_Pos !== false) {
                    $g_Redirect[] = $i;
                    $g_RedirectPHPFragment[] = getFragment($l_Content, $l_Pos);
                    $l_CriticalDetected = true;
                }

                $l_Pos = stripos($l_Content, 'auto_append_file');
                if ($l_Pos !== false) {
                    $g_Redirect[] = $i;
                    $g_RedirectPHPFragment[] = getFragment($l_Content, $l_Pos);
                    $l_CriticalDetected = true;
                }

                $l_Pos = stripos($l_Content, '^(%2d|-)[^=]+$');
                if ($l_Pos !== false) {
                    $g_Redirect[] = $i;
                    $g_RedirectPHPFragment[] = getFragment($l_Content, $l_Pos);
                    $l_CriticalDetected = true;
                }

                if (!$l_CriticalDetected) {
                    $l_Pos = stripos($l_Content, '%{HTTP_USER_AGENT}');
                    if ($l_Pos !== false) {
                        $g_Redirect[] = $i;
                        $g_RedirectPHPFragment[] = getFragment($l_Content, $l_Pos);
                        $l_CriticalDetected = true;
                    }
                }

                if (!$l_CriticalDetected) {
                    if (
                        preg_match_all("|RewriteRule\s+.+?\s+http://(.+?)/.+\s+\[.*R=\d+.*\]|smi", $l_HTAContent, $l_Found, PREG_SET_ORDER)
                    ) {
                        $l_Host = str_replace('www.', '', $_SERVER['HTTP_HOST']);
                        for ($j = 0; $j < sizeof($l_Found); $j++) {
                            $l_Found[$j][1] = str_replace('www.', '', $l_Found[$j][1]);
                            if ($l_Found[$j][1] != $l_Host) {
                                $g_Redirect[] = $i;
                                $l_CriticalDetected = true;
                                break;
                            }
                        }
                    }
                }

                unset($l_HTAContent);
            }


            // warnings
            $l_Pos = '';

            if (WarningPHP($l_Filename, $l_Unwrapped, $l_Pos, $l_SigId)) {
                $l_Prio = 1;
                if (strpos($l_Filename, '.ph') !== false) {
                    $l_Prio = 0;
                }

                $g_WarningPHP[$l_Prio][] = $i;
                $g_WarningPHPFragment[$l_Prio][] = getFragment($l_Unwrapped, $l_Pos);
                $g_WarningPHPSig[] = $l_SigId;

                $l_CriticalDetected = true;
            }


            // adware
            if (Adware($l_Filename, $l_Unwrapped, $l_Pos)) {
                $g_AdwareList[] = $i;
                $g_AdwareListFragment[] = getFragment($l_Unwrapped, $l_Pos);
                $l_CriticalDetected = true;
            }

            // articles
            if (stripos($l_Filename, 'article_index')) {
                $g_AdwareList[] = $i;
                $l_CriticalDetected = true;
            }
        }
    } // end of if (!$g_SkipNextCheck) {

    unset($l_Unwrapped);
    unset($l_Content);

    //printProgress(++$_files_and_ignored, $l_Filename);

    $l_TSEndScan = microtime(true);
    if ($l_TSEndScan - $l_TSStartScan >= 0.5) {
        usleep(SCAN_DELAY * 1000);
    }

    if ($g_SkipNextCheck || $l_CriticalDetected) {
        AddResult($l_Filename, $i);
    }
}

function QCR_GoScan($par_Offset)
{
    global $g_IframerFragment, $g_Iframer, $g_Redirect, $g_Doorway, $g_EmptyLink, $g_Structure, $g_Counter,
           $g_HeuristicType, $g_HeuristicDetected, $g_TotalFolder, $g_TotalFiles, $g_WarningPHP, $g_AdwareList,
           $g_CriticalPHP, $g_Phishing, $g_CriticalJS, $g_UrlIgnoreList, $g_CriticalJSFragment, $g_PHPCodeInside, $g_PHPCodeInsideFragment,
           $g_NotRead, $g_WarningPHPFragment, $g_WarningPHPSig, $g_BigFiles, $g_RedirectPHPFragment, $g_EmptyLinkSrc, $g_CriticalPHPSig, $g_CriticalPHPFragment,
           $g_Base64Fragment, $g_UnixExec, $g_PhishingSigFragment, $g_PhishingFragment, $g_PhishingSig, $g_CriticalJSSig, $g_IframerFragment, $g_CMS, $defaults, $g_AdwareListFragment, $g_KnownList, $g_Vulnerable;

    QCR_Debug('QCR_GoScan ' . $par_Offset);

    $i = 0;

    try {
        $s_file = new SplFileObject(QUEUE_FILENAME);
        $s_file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        foreach ($s_file as $l_Filename) {
            QCR_ScanFile($l_Filename, $i++);
        }

        unset($s_file);
    } catch (Exception $e) {
        QCR_Debug($e->getMessage());
    }
}
function QCR_SearchPHP($src) {
    if (preg_match("/(<\?php[\w\s]{5,})/smi", $src, $l_Found, PREG_OFFSET_CAPTURE)) {
        return $l_Found[0][1];
    }

    if (preg_match("/(<script[^>]*language\s*=\s*)('|\"|)php('|\"|)([^>]*>)/i", $src, $l_Found, PREG_OFFSET_CAPTURE)) {
        return $l_Found[0][1];
    }

    return false;
}
