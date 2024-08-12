<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Helpers;

class Validation 
{

    /**
     * This method checks if a specified value is a valid filename.
     * @param mixed $value The value to be checked.
     * @return bool TRUE if valid filename, FALSE if not.
     * 
     */
    public static function isValidFilename($value): bool 
    {
        $cleanValue = mb_substr($value, 0, 250);
        $cleanValue = preg_replace('/[^\p{L}\p{N} \-_\(\)\[\]\@$€§%°.]+/u', '', $cleanValue);
        return $cleanValue === $value;
    }

    /**
     * This method checks if a specified value is a valid path.
     * @param mixed $value The value to be checked.
     * @param bool $isRootAllowed If '/' is allowed or prohibited.
     * @return bool TRUE if (theoretical) valid path, FALSE if not.
     * 
     */
    public static function isValidPath(mixed $value, bool $isRootAllowed = true): bool
    {
        if (!is_string($value)) { return false; }
        if ($value === '/') { return $isRootAllowed; }

        $segments = array_filter(explode('/', $value));
        if (count($segments) === 0) { return false; }

        foreach ($segments as $segment)
        {
            if (empty($segment)) { return false; }
            if (!self::isValidFilename($segment)) { return false; }
        }

        return true;
    }

    /*

    public static function validLabel($value): bool 
    {
        $cleanValue = mb_substr($value, 0, 250);
        $cleanValue = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleanValue);
        $cleanValue = preg_replace('/[<>&"\'`\\\\\/]/u', '', $cleanValue);
        return $cleanValue === $value;
    }

    {
    // Limit the input to 60 characters
    $value = mb_substr($value, 0, 60);

    // Define a pattern to remove dangerous characters
    $dangerousCharsPattern = '/[<>&"\'`\\\\\/\n\r\t\^]/u';

    // Define a pattern to allow letters, symbols, numbers, punctuation, and spaces
    $pattern = '/[^\p{L}\p{S}\p{N}\p{P}\p{Z}]+/u';

    // Remove dangerous characters
    $value = preg_replace($dangerousCharsPattern, '', $value);

    // Remove all characters not matching the allowed pattern
    $value = preg_replace($pattern, '', $value);

    return $value;
}

export const cleanLabel = (value) => {
	value = value.substring(0, 60)
	const dangerousCharsPattern = /[<>&"'`\\\\/\n\r\t\^]/g
    const pattern = XRegExp('[^\\p{L}\\p{S}\\p{N}\\p{P}\\p{Z}]', 'gu')
	value = value.replace(dangerousCharsPattern, '')
	return XRegExp.replace(value, pattern, '')
}

    public static function validTag($value): bool 
    {
        $cleanValue = mb_substr($value, 0, 250);
        $cleanValue = preg_replace('/[^\p{L}\p{N}]+/u', '', $cleanValue);
        return $cleanValue === $value;
    }
    
    public static function validEmail($value): bool 
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL)!==false;
    }

    public static function validPhone($value): bool 
    {

        // check if empty
        if (!is_string($value)) { return false; }
        $value = trim($value);
        if (!$value) { return false; }
    
        // check if double chars exist
        if (preg_match('/\/\/+/', $value)) { return false; }
        if (preg_match('/  +/', $value)) { return false; }
        if (preg_match('/--+/', $value)) { return false; }
    
        // test parentheses
        $parOpen = 0;
        $parValid = true;
        $chars = str_split($value);
        foreach ($chars as $char) {
            if ($char === '(') { $parOpen++; }
            else if ($char === ')') { $parOpen--; }
            if ($parOpen >= 2 || $parOpen < 0) { $parValid = false; break; }
        }
        if (!$parValid) { return false; }
        if ($parOpen !== 0) { return false; }

        // allow + only at beginning
        if (strpos($value, "+") !== false && !preg_match('/^\+|^\(\+/', $value)) { return false; }
    
        // allow only one +
        if (substr_count($value, "+") > 1) { return false; }

        // Check if there are less than three digits
        preg_match_all('/[0-9]/', $value, $matches);
        if (count($matches[0]) < 3) {
            return false;
        }
        
        // remove allowed chars
        $value = preg_replace('/[0-9\-\/\s\+\(\)]+/', '', $value);
    
        // should be empty now if valid
        return $value === '';
        
    }

    public static function validUrl($value): bool 
    {
        $meta = parse_url($value);
        if ($meta === false) { return false; }
        
        $scheme = $meta['scheme'] ?? null;
        $host = $meta['host'] ?? null;
        $path = $meta['path'] ?? null;
        
        if (!!$scheme && !!$host) { return true; }
        else if (!$scheme && !$host && !!$path)
        {
            $segments = explode('.', $path);
            if (count($segments) <= 1) { return false; }
            
            $valid = true;
            foreach ($segments as $index => $segment)
            {
                if (empty($segment)) { return false; }
                if ($index === count($segments) - 1) {
                    // Last part can include characters for TLD
                    if (!preg_match('/^[a-zA-Z0-9-._~:\/?#\[\]@!$&\'()*+,;=]+$/', $segment)) {
                        $valid = false;
                    }
                } else {
                    // Other parts should be alphanumeric or hyphen
                    if (!preg_match('/^[a-zA-Z0-9-]+$/', $segment)) {
                        $valid = false;
                    }
                }
            }
            return $valid;
            
        }
        else { return false; }
    }

    public static function validDate($value): bool 
    {
        if (!$value) { return false; }
        $d = \DateTime::createFromFormat(Converter::DATESTRINGFORMAT, $value);
        return $d !== false;
    }

    public static function validPathTemplate($value): bool 
    {
        if (!is_string($value)) { return false; }

        if ($value[0] !== '/') { return false; }
        $segments = array_filter(explode('/', $value));
        if (count($segments) === 0) { return false; }

        $placeholders = 
        [
            AktnschrnkService::PATHTEMPLATE_ADDYEAR,
            AktnschrnkService::PATHTEMPLATE_ADDMONTH,
            AktnschrnkService::PATHTEMPLATE_ADDDAY,
            AktnschrnkService::PATHTEMPLATE_MENTIONEDYEAR,
            AktnschrnkService::PATHTEMPLATE_MENTIONEDMONTH,
            AktnschrnkService::PATHTEMPLATE_MENTIONEDDAY,
            AktnschrnkService::PATHTEMPLATE_RECIPIENT,
            AktnschrnkService::PATHTEMPLATE_ORIGIN,
            AktnschrnkService::PATHTEMPLATE_TRAY1,
            AktnschrnkService::PATHTEMPLATE_TRAY2,
            AktnschrnkService::PATHTEMPLATE_TRAY3,
            AktnschrnkService::PATHTEMPLATE_GROUPBY,
        ];

        foreach ($segments as $segment)
        {
            if (!in_array($segment, $placeholders)) { return false; }
        }

        return true;

    }

    public static function arePathsContainingEachother($path1, $path2): bool 
    {
        return strpos($path1, $path2) === 0 || strpos($path2, $path1) === 0;
    }

    */

}