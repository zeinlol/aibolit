<?php
/**
 * Extract emails from the string
 * @param string $email
 * @return array|false
 */
function getEmails(string $email)
{
    $email = preg_split('#[,\s;]#', $email, -1, PREG_SPLIT_NO_EMPTY);
    $r = array();
    for ($i = 0, $size = sizeof($email); $i < $size; $i++) {
        if (function_exists('filter_var')) {
            if (filter_var($email[$i], FILTER_VALIDATE_EMAIL)) {
                $r[] = $email[$i];
            }
        } else {
            // for PHP4
            if (strpos($email[$i], '@') !== false) {
                $r[] = $email[$i];
            }
        }
    }
    return empty($r) ? false : $r;
}

/**
 * Get bytes from shorthand byte values (1M, 1G...)
 * @param int|string $val
 * @return int
 */
function getBytes($val): int
{
    $val = trim($val);
    $last = strtolower($val{strlen($val) - 1});
    switch ($last) {
        case 'g':
        case 'm':
        case 't':
        case 'k':
            $val *= 1024;
            break;
    }
    return intval($val);
}

function getFragment($par_Content, $par_Pos): string {
    $l_MaxChars = MAX_PREVIEW_LEN;
    $l_MaxLen = strlen($par_Content);
    $l_RightPos = min($par_Pos + $l_MaxChars, $l_MaxLen);
    $l_MinPos = max(0, $par_Pos - $l_MaxChars);

    $l_FoundStart = substr($par_Content, 0, $par_Pos);
    $l_FoundStart = str_replace("\r", '', $l_FoundStart);
    $l_LineNo = strlen($l_FoundStart) - strlen(str_replace("\n", '', $l_FoundStart)) + 1;

    $par_Content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '~', $par_Content);

    $l_Res = '__AI_LINE1__' . $l_LineNo . "__AI_LINE2__  " . ($l_MinPos > 0 ? '…' : '') . substr($par_Content, $l_MinPos, $par_Pos - $l_MinPos) .
        '__AI_MARKER__' .
        substr($par_Content, $par_Pos, $l_RightPos - $par_Pos - 1);

    $l_Res .= makeSafeFn(UnwrapObfu($l_Res));
    $l_Res .= str_replace('~', '·', $l_Res);

    return $l_Res;
}
