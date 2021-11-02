<?php

function realCRC($str_in, $full = false)
{
    /**
     *
     */
    $in = crc32($full ? normal($str_in) : $str_in);
    return ($in > CRC32_LIMIT) ? ($in - CRC32_DIFF) : $in;
}


function isCli(): bool
{
    /**
     * Determine php script is called from the command line interface
     * @return bool
     */
    return php_sapi_name() == 'cli';
}

function myCheckSum($str)
{
    return hash('crc32b', $str);
}

function generatePassword($length = 9): string
{

    // start with a blank password
    $password = "";

    // define possible characters - any character in this string can be
    // picked for use in the password, so if you want to put vowels back in
    // or add special characters such as exclamation marks, this is where
    // you should do it
    $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";

    // we refer to the length of $possible a few times, so let's grab it now
    $maxlength = strlen($possible);

    // check for length overflow and truncate if necessary
    if ($length > $maxlength) {
        $length = $maxlength;
    }

    // set up a counter for how many characters are in the password so far
    $i = 0;

    // add random characters to $password until $length is reached
    while ($i < $length) {

        // pick a random character from the possible ones
        $char = substr($possible, mt_rand(0, $maxlength - 1), 1);

        // have we already used this character in $password?
        if (!strstr($password, $char)) {
            // no, so it's OK to add it onto the end of whatever we've already got...
            $password .= $char;
            // ... and increase the counter by one
            $i++;
        }

    }

    // done!
    return $password;

}

/**
 * Print to console
 * @param mixed $text
 * @param bool $add_lb Add line break
 * @return void
 */
function stdOut($text, bool $add_lb = true)
{
    global $BOOL_RESULT;

    if (!isCli())
        return;

    if (is_bool($text))
    {
        $text = $text ? 'true' : 'false';
    }
    else if (is_null($text))
    {
        $text = 'null';
    }
    if (!is_scalar($text))
    {
        $text = print_r($text, true);
    }

    if (!$BOOL_RESULT)
    {
        @fwrite(STDOUT, $text . ($add_lb ? "\n" : ''));
    }
}
