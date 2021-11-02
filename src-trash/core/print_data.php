<?php

/**
 * Print file
 */
function printFile() {
    $l_FileName = $_GET['fn'];
    $l_CRC = isset($_GET['c']) ? (int)$_GET['c'] : 0;
    $l_Content = file_get_contents($l_FileName);
    $l_FileCRC = realCRC($l_Content);
    if ($l_FileCRC != $l_CRC) {
        echo 'Доступ запрещен.';
        exit;
    }
    echo '<pre>' . htmlspecialchars($l_Content) . '</pre>';
}

function printList($par_List, $par_Details = null, $par_NeedIgnore = false, $par_SigId = null, $par_TableName = null): string {
    global $g_Structure, $g_NoPrefix, $g_AddPrefix;

    if ($par_TableName == null) {
        $par_TableName = 'table_' . rand(1000000, 9000000);
    }

    $l_Result = "<div class=\"flist\"><table cellspacing=1 cellpadding=4 border=0 id=\"" . $par_TableName . "\">";
    $l_Result .= "<thead><tr class=\"tbgh" . ($i % 2) . "\">";
    $l_Result .= "<th width=70%>" . AI_STR_004 . "</th>";
    $l_Result .= "<th>" . AI_STR_005 . "</th>";
    $l_Result .= "<th>" . AI_STR_006 . "</th>";
    $l_Result .= "<th width=90>" . AI_STR_007 . "</th>";
    $l_Result .= "<th width=0 class=\"hidd\">CRC32</th>";
    $l_Result .= "<th width=0 class=\"hidd\"></th>";
    $l_Result .= "<th width=0 class=\"hidd\"></th>";
    $l_Result .= "<th width=0 class=\"hidd\"></th>";
    $l_Result .= "</tr></thead><tbody>";

    for ($i = 0; $i < count($par_List); $i++) {
        if ($par_SigId != null) {
            $l_SigId = 'id_' . $par_SigId[$i];
        } else {
            $l_SigId = 'id_z' . rand(1000000, 9000000);
        }

        $l_Pos = $par_List[$i];
        if ($par_NeedIgnore) {
            if (needIgnore($g_Structure['n'][$par_List[$i]], $g_Structure['crc'][$l_Pos])) {
                continue;
            }
        }

        $l_Creat = $g_Structure['c'][$l_Pos] > 0 ? date("d/m/Y H:i:s", $g_Structure['c'][$l_Pos]) : '-';
        $l_Modif = $g_Structure['m'][$l_Pos] > 0 ? date("d/m/Y H:i:s", $g_Structure['m'][$l_Pos]) : '-';
        $l_Size = $g_Structure['s'][$l_Pos] > 0 ? normalize_bytes($g_Structure['s'][$l_Pos]) : '-';

        if ($par_Details != null) {
            $l_WithMarker = preg_replace('|__AI_MARKER__|smi', '<span class="marker">&nbsp;</span>', $par_Details[$i]);
            $l_WithMarker = preg_replace('|__AI_LINE1__|smi', '<span class="line_no">', $l_WithMarker);
            $l_WithMarker = preg_replace('|__AI_LINE2__|smi', '</span>', $l_WithMarker);

            $l_Body = '<div class="details">';

            if ($par_SigId != null) {
                $l_Body .= '<a href="#" onclick="return hsig(\'' . $l_SigId . '\')">[x]</a> ';
            }

            $l_Body .= $l_WithMarker . '</div>';
        } else {
            $l_Body = '';
        }

        $l_Result .= '<tr class="tbg' . ($i % 2) . '" o="' . $l_SigId . '">';

        if (is_file($g_Structure['n'][$l_Pos])) {
//		$l_Result .= '<td><div class="it"><a class="it" target="_blank" href="'. $defaults['site_url'] . 'ai-bolit.php?fn=' .
//	              $g_Structure['n'][$l_Pos] . '&ph=' . realCRC(PASS) . '&c=' . $g_Structure['crc'][$l_Pos] . '">' . $g_Structure['n'][$l_Pos] . '</a></div>' . $l_Body . '</td>';
            $l_Result .= '<td><div class="it"><a class="it">' . makeSafeFn($g_AddPrefix . str_replace($g_NoPrefix, '', $g_Structure['n'][$l_Pos])) . '</a></div>' . $l_Body . '</td>';
        } else {
            $l_Result .= '<td><div class="it"><a class="it">' . makeSafeFn($g_AddPrefix . str_replace($g_NoPrefix, '', $g_Structure['n'][$par_List[$i]])) . '</a></div></td>';
        }

        $l_Result .= '<td align=center><div class="ctd">' . $l_Creat . '</div></td>';
        $l_Result .= '<td align=center><div class="ctd">' . $l_Modif . '</div></td>';
        $l_Result .= '<td align=center><div class="ctd">' . $l_Size . '</div></td>';
        $l_Result .= '<td class="hidd"><div class="hidd">' . $g_Structure['crc'][$l_Pos] . '</div></td>';
        $l_Result .= '<td class="hidd"><div class="hidd">' . $g_Structure['c'][$l_Pos] . '</div></td>';
        $l_Result .= '<td class="hidd"><div class="hidd">' . $g_Structure['m'][$l_Pos] . '</div></td>';
        $l_Result .= '<td class="hidd"><div class="hidd">' . $l_SigId . '</div></td>';
        $l_Result .= '</tr>';

    }

    $l_Result .= "</tbody></table></div><div class=clear style=\"margin: 20px 0 0 0\"></div>";

    return $l_Result;
}

function printPlainList($par_List, $l_Result, $par_Details = null, $par_NeedIgnore = false): string {
//  unused parameters:  $par_SigId = null, $par_TableName = null
    global $g_Structure, $g_NoPrefix, $g_AddPrefix;

//  $l_Result = "\n#\n";

    $l_Src = array('&quot;', '&lt;', '&gt;', '&amp;', '&#039;');
    $l_Dst = array('"', '<', '>', '&', '\'');

    for ($i = 0; $i < count($par_List); $i++) {
        $l_Pos = $par_List[$i];
        if ($par_NeedIgnore) {
            if (needIgnore($g_Structure['n'][$par_List[$i]], $g_Structure['crc'][$l_Pos])) {
                continue;
            }
        }


        if ($par_Details != null) {
            $l_Body = preg_replace('|(L\d+).+__AI_MARKER__|smi', '$1: ...', $par_Details[$i]);
            $l_Body = preg_replace('/[^\x21-\x7F]/', '.', $l_Body);
            $l_Body = str_replace($l_Src, $l_Dst, $l_Body);

        } else {
            $l_Body = '';
        }

        if (is_file($g_Structure['n'][$l_Pos])) {
            $l_Result .= $g_AddPrefix . str_replace($g_NoPrefix, '', $g_Structure['n'][$l_Pos]) . "\t\t\t" . $l_Body . "\n";
        } else {
            $l_Result .= $g_AddPrefix . str_replace($g_NoPrefix, '', $g_Structure['n'][$par_List[$i]]) . "\n";
        }

    }

    return $l_Result;
}
/**
 * Print progress
 */
function printProgress(int $num, $par_File)
{
    global $g_CriticalPHP, $g_Base64, $g_Phishing, $g_CriticalJS, $g_Iframer;
    $total_files = $GLOBALS['g_FoundTotalFiles'];
    $elapsed_time = microtime(true) - START_TIME;
    $percent = number_format($total_files ? $num*100/$total_files : 0, 1);
    $stat = '';
    if ($elapsed_time >= 1)
    {
        $elapsed_seconds = round($elapsed_time);
        $fs = floor($num / $elapsed_seconds);
        $left_files = $total_files - $num;
        if ($fs > 0)
        {
            $left_time = ($left_files / $fs); //ceil($left_files / $fs);
            $stat = ' [Avg: ' . round($fs,2) . ' files/s' . ($left_time > 0  ? ' Left: ' . normalize_seconds($left_time) : '') . '] [Mlw:' . (count($g_CriticalPHP) + count($g_Base64))  . '|' . (count($g_CriticalJS) + count($g_Iframer) + count($g_Phishing)) . ']';
        }
    }

    $l_FN = substr($par_File, -60);

    $text = "$percent% [$l_FN] $num of $total_files. " . $stat;
    $text = str_pad($text, 160);
    stdOut(str_repeat(chr(8), 160) . $text, false);
}
