<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3
 */

namespace Matomo\Ini;

/**
 * Reads INI configuration.
 */
class IniReader
{
    /**
     * @var bool
     */
    private $useNativeFunction;

    public function __construct()
    {
        $this->useNativeFunction = function_exists('parse_ini_string');
    }

    /**
     * Reads a INI configuration file and returns it as an array.
     *
     * The array returned is multidimensional, indexed by section names:
     *
     * ```
     * array(
     *     'Section 1' => array(
     *         'value1' => 'hello',
     *         'value2' => 'world',
     *     ),
     *     'Section 2' => array(
     *         'value3' => 'foo',
     *     )
     * );
     * ```
     *
     * @param string $filename The file to read.
     * @throws IniReadingException
     * @return array
     */
    public function readFile($filename)
    {
        $ini = $this->getContentOfIniFile($filename);

        return $this->readString($ini);
    }

    /**
     * Reads a INI configuration string and returns it as an array.
     *
     * The array returned is multidimensional, indexed by section names:
     *
     * ```
     * array(
     *     'Section 1' => array(
     *         'value1' => 'hello',
     *         'value2' => 'world',
     *     ),
     *     'Section 2' => array(
     *         'value3' => 'foo',
     *     )
     * );
     * ```
     *
     * @param string $ini String containing INI configuration.
     * @throws IniReadingException
     * @return array
     */
    public function readString($ini)
    {
        // On PHP 5.3.3 an empty line return is needed at the end
        // See http://3v4l.org/jD1Lh
        $ini .= "\n";

        if ($this->useNativeFunction) {
            $array = $this->readWithNativeFunction($ini);
        } else {
            $array = $this->readWithAlternativeImplementation($ini);
        }

        return $array;
    }

    /**
     * @param string $ini
     * @throws IniReadingException
     * @return array
     */
    private function readWithNativeFunction($ini)
    {
        $array = @parse_ini_string($ini, true);

        if ($array === false) {
            $e = error_get_last();
            throw new IniReadingException('Syntax error in INI configuration: ' . $e['message']);
        }

        // We cannot use INI_SCANNER_RAW by default because it is buggy under PHP 5.3.14 and 5.4.4
        // http://3v4l.org/m24cT
        $rawValues = @parse_ini_string($ini, true, INI_SCANNER_RAW);
        if ($rawValues === false) {
            return $this->decode($array, $array);
        }
        $array = $this->decode($array, $rawValues);

        return $array;
    }

    private function getContentOfIniFile($filename)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new IniReadingException(sprintf("The file %s doesn't exist or is not readable", $filename));
        }

        $content = $this->getFileContent($filename);

        if ($content === false) {
            throw new IniReadingException(sprintf('Impossible to read the file %s', $filename));
        }

        return $content;
    }

    /**
     * Reads ini comments for each key.
     *
     * The array returned is multidimensional, indexed by section names:
     *
     * ```
     * array(
     *     'Section 1' => array(
     *         'key1' => 'comment 1',
     *         'key2' => 'comment 2',
     *     ),
     *     'Section 2' => array(
     *         'key3' => 'comment 3',
     *     )
     * );
     * ```
     *
     * @param string $filename The path to a file.
     * @throws IniReadingException
     * @return array
     */
    public function readComments($filename)
    {
        $ini = $this->getContentOfIniFile($filename);
        $ini = $this->splitIniContentIntoLines($ini);

        $descriptions = array();

        $section = '';
        $lastComment = '';

        $lineCount = count($ini);
        for ($lineNumber = 0; $lineNumber < $lineCount; $lineNumber++) {
            $line = trim($ini[$lineNumber]);

            if (strpos($line, '[') === 0) {
                $tmp = explode(']', $line);
                $section = trim(substr($tmp[0], 1));
                $descriptions[$section] = array();
                $lastComment = '';
                continue;
            }

            if ($line === '') {
                $lastComment = "\n";
                continue;
            }

            if (!preg_match('/^[a-zA-Z0-9[]/', $line)) {
                if (strpos($line, ';') === 0) {
                    $line = trim(substr($line, 1));
                }
                // comment
                $lastComment .= $line . "\n";
                continue;
            }

            $parts = explode('=', $line, 2);

            // Skip the remaining lines of a value that spans several lines, so they are not
            // taken for sections or keys of their own.
            $rawValue = ltrim(explode('=', $ini[$lineNumber], 2)[1] ?? '');
            $quote = isset($rawValue[0]) && ($rawValue[0] === '"' || $rawValue[0] === "'") ? $rawValue[0] : null;

            if ($quote !== null) {
                [, $lineNumber] = $this->readQuotedValue($ini, $lineNumber, $lineCount, $rawValue, $quote);
            }

            $key = trim($parts[0]);
            if (strpos($key, '[]') === strlen($key) - 2) {
                $key = substr($key, 0, -2);
            }

            if (empty($descriptions[$section][$key])) {
                $descriptions[$section][$key] = $lastComment;
            }

            $lastComment = '';
        }

        return $descriptions;
    }

    private function splitIniContentIntoLines($ini)
    {
        if (is_string($ini)) {
            // Only a carriage return that is not part of a "\r\n" is a line break of its own,
            // so that a "\r\n" stays intact inside a value.
            $ini = explode("\n", preg_replace('/\r(?!\n)/', "\n", $ini));
        }

        return $ini;
    }

    /**
     * Reimplementation in case `parse_ini_file()` is disabled.
     *
     * @author Andrew Sohn <asohn (at) aircanopy (dot) net>
     * @author anthon (dot) pang (at) gmail (dot) com
     *
     * @param string $ini
     * @return array
     */
    private function readWithAlternativeImplementation($ini)
    {
        $ini = $this->splitIniContentIntoLines($ini);

        if (count($ini) == 0) {
            return array();
        }

        $sections = array();
        $values = array();
        $result = array();
        $globals = array();
        $i = 0;
        $lineCount = count($ini);
        for ($lineNumber = 0; $lineNumber < $lineCount; $lineNumber++) {
            $line = trim($ini[$lineNumber]);
            $line = str_replace("\t", " ", $line);

            // Comments
            if (!preg_match('/^[a-zA-Z0-9[]/', $line)) {
                continue;
            }

            // Sections
            if ($line[0] == '[') {
                $tmp = explode(']', $line);

                // Text and comments after the section name are ignored, but a quote or an
                // assignment there means the line is not a section, as for the native parser.
                $rest = substr($line, strlen($tmp[0]) + 1);
                $rest = explode(';', $rest, 2)[0];
                if (strpos($rest, '"') !== false || strpos($rest, '=') !== false) {
                    throw new IniReadingException('Syntax error in INI configuration: unexpected content after a section name');
                }

                $sections[] = trim(substr($tmp[0], 1));
                $i++;
                continue;
            }

            // Key-value pair
            [$key] = explode('=', $line, 2);
            $key = trim($key);

            // Taken from the unmodified line, so tabs and inner whitespace are preserved.
            $rawParts = explode('=', $ini[$lineNumber], 2);
            $value = isset($rawParts[1]) ? ltrim($rawParts[1]) : '';

            $quote = isset($value[0]) && ($value[0] === '"' || $value[0] === "'") ? $value[0] : null;

            if ($quote !== null) {
                // A quoted value may span several lines, which the native parser reads as
                // one value as well. Anything after the closing quote is dropped.
                [$value, $lineNumber] = $this->readQuotedValue($ini, $lineNumber, $lineCount, $value, $quote);

                if ($value === null) {
                    throw new IniReadingException('Syntax error in INI configuration: unterminated quoted value');
                }
            } else {
                // An unquoted value ends at an inline comment.
                $value = trim(str_replace("\t", " ", $value));
                if (strstr($value, ";")) {
                    $tmp = explode(';', $value);
                    $value = $tmp[0];
                }

                $value = trim($value);

                // Apostrophes are not treated as quotes, so a value such as "don't" is still
                // read the way it was before.
                if ($this->hasUnterminatedDoubleQuote($value)) {
                    throw new IniReadingException('Syntax error in INI configuration: unterminated quote in a value');
                }
            }

            // Special keywords
            if ($value === 'true' || $value === 'yes' || $value === 'on') {
                $value = true;
            } elseif ($value === 'false' || $value === 'no' || $value === 'off') {
                $value = false;
            } elseif ($value === '' || $value === 'null') {
                $value = null;
            }

            if (is_string($value)) {
                if (preg_match('/^"(.*)"$/s', $value)) {
                    $value = preg_replace('/^"(.*)"$/s', '$1', $value);
                    $value = $this->unescapeDoubleQuoted($value);
                } elseif (preg_match("/^'(.*)'$/s", $value)) {
                    $value = preg_replace("/^'(.*)'$/s", '$1', $value);
                    $value = str_replace("\'", "'", $value);
                } else {
                    $value = trim($value, "'\"");
                }
            }

            if ($i == 0) {
                if (substr($key, -2) == '[]') {
                    $globals[substr($key, 0, -2)][] = $value;
                } else {
                    $globals[$key] = $value;
                }
            } else {
                if (substr($key, -2) == '[]') {
                    $values[$i - 1][substr($key, 0, -2)][] = $value;
                } else {
                    $values[$i - 1][$key] = $value;
                }
            }
        }

        for ($j = 0; $j < $i; $j++) {
            if (isset($values[$j])) {
                $result[$sections[$j]] = $values[$j];
            } else {
                $result[$sections[$j]] = array();
            }
        }

        $finalResult = $result + $globals;

        return $this->decode($finalResult, $finalResult);
    }

    /**
     * Returns the position of the closing quote of a quoted value, or false if the value is
     * not closed yet.
     *
     * For double quotes a backslash escapes the following character (matching IniWriter,
     * which escapes "\\" and "\""); single-quoted INI values do not support escaping, so the
     * next single quote closes them.
     *
     * $offset is where the scan starts and is updated to where it stopped, so that a value
     * built up over several lines is scanned only once instead of from the start every time.
     *
     * @param string $value  A string whose first character is the opening quote.
     * @param string $quote  The quote character, either '"' or "'".
     * @param int    $offset Position to start scanning at, updated in place.
     * @return int|false
     */
    private function findClosingQuote($value, $quote, &$offset)
    {
        $length = strlen($value);
        for ($i = $offset; $i < $length; $i++) {
            if ($quote === '"' && $value[$i] === '\\') {
                $i++; // skip the escaped character
                continue;
            }
            if ($value[$i] === $quote) {
                $offset = $i;
                return $i;
            }
        }

        // $i is past the end when the last character was an escape, so that the character
        // appended next is not scanned again.
        $offset = $i;

        return false;
    }

    /**
     * Reads a quoted value, which may span several lines, and returns it together with the
     * number of the line it ends on. The value is null when it is never closed.
     *
     * @param array  $ini        All lines of the file.
     * @param int    $lineNumber Number of the line the value starts on.
     * @param int    $lineCount  Total number of lines.
     * @param string $value      The value as it starts on that line, including the quote.
     * @param string $quote      The quote character, either '"' or "'".
     * @return array
     */
    private function readQuotedValue(array $ini, $lineNumber, $lineCount, $value, $quote)
    {
        $offset = 1;
        $position = $this->findClosingQuote($value, $quote, $offset);

        if ($position === false) {
            $position = $this->findClosingQuoteAtLineEnd($value, $quote);
        }

        while ($position === false && ++$lineNumber < $lineCount) {
            $value .= "\n" . $ini[$lineNumber];
            $position = $this->findClosingQuote($value, $quote, $offset);

            if ($position === false) {
                $position = $this->findClosingQuoteAtLineEnd($value, $quote);
            }
        }

        return array(
            $position === false ? null : substr($value, 0, $position + 1),
            min($lineNumber, $lineCount - 1),
        );
    }

    /**
     * Whether a value contains a double quote that opens a string which is never closed.
     *
     * @param string $value
     * @return bool
     */
    private function hasUnterminatedDoubleQuote($value)
    {
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            if ($value[$i] !== '"') {
                continue;
            }

            $offset = 1;
            $position = $this->findClosingQuote(substr($value, $i), '"', $offset);

            if ($position === false) {
                return true;
            }

            $i += $position;
        }

        return false;
    }

    /**
     * Returns the position of a quote that ends the last line of a value, or false.
     *
     * A value written by an older IniWriter, which escaped quotes but not backslashes, can
     * end in a backslash directly before its closing quote (e.g. `key = "C:\dir\"`). The
     * native parser ends the value at that quote, so a quote that is the very last character
     * of a line is treated as the closing quote here as well. Anything following the quote
     * on the same line means it is an escaped quote inside the value instead, which is why
     * only a quote at the exact end of the line qualifies.
     *
     * @param string $value
     * @param string $quote The quote character, either '"' or "'".
     * @return int|false
     */
    private function findClosingQuoteAtLineEnd($value, $quote)
    {
        $value = rtrim($value, "\r");

        if (strlen($value) > 1 && substr($value, -1) === $quote) {
            return strlen($value) - 1;
        }

        return false;
    }

    /**
     * Reverses the escaping applied by IniWriter to double-quoted values: "\\" becomes a
     * single backslash and "\"" becomes a double quote. Processed left to right so the two
     * escapes cannot interfere with each other. Any other backslash sequence is left as-is.
     *
     * @param string $value
     * @return string
     */
    private function unescapeDoubleQuoted($value)
    {
        $result = '';
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            if ($value[$i] === '\\' && $i + 1 < $length
                && ($value[$i + 1] === '\\' || $value[$i + 1] === '"')
            ) {
                $result .= $value[$i + 1];
                $i++;
                continue;
            }
            $result .= $value[$i];
        }

        return $result;
    }

    /**
     * @param string $filename
     * @return bool|string Returns false if failure.
     */
    private function getFileContent($filename)
    {
        if (function_exists('file_get_contents')) {
            return file_get_contents($filename);
        } elseif (function_exists('file')) {
            $ini = file($filename);
            if ($ini !== false) {
                return implode("\n", $ini);
            }
        } elseif (function_exists('fopen') && function_exists('fread')) {
            $handle = fopen($filename, 'r');
            if (!$handle) {
                return false;
            }
            $ini = fread($handle, filesize($filename));
            fclose($handle);
            return $ini;
        }

        return false;
    }

    /**
     * We have to decode values manually because parse_ini_file() has a poor implementation.
     *
     * @param mixed $value    The array decoded by `parse_ini_file`
     * @param mixed $rawValue The same array but with raw strings, so that we can re-decode manually
     *                        and override the poor job of `parse_ini_file`
     * @return mixed
     */
    private function decode($value, $rawValue)
    {
        if (is_array($value)) {
            foreach ($value as $i => &$subValue) {
                // Both scanners can disagree about the structure of a file, so only decode
                // the values they both returned.
                $subRawValue = is_array($rawValue) && array_key_exists($i, $rawValue)
                    ? $rawValue[$i]
                    : $subValue;

                $subValue = $this->decode($subValue, $subRawValue);
            }
            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $value = $this->decodeBoolean($value, $rawValue);
        $value = $this->decodeNull($value, $rawValue);

        if (is_numeric($value) && $this->noLossWhenCastToInt($value)) {
            return $value + 0;
        }

        return $value;
    }

    private function decodeBoolean($value, $rawValue)
    {
        if ($value === '1' && ($rawValue === 'true' || $rawValue === 'yes' || $rawValue === 'on')) {
            return true;
        }
        if ($value === '' && ($rawValue === 'false' || $rawValue === 'no' || $rawValue === 'off')) {
            return false;
        }
        return $value;
    }

    private function decodeNull($value, $rawValue)
    {
        if ($value === '' && $rawValue === 'null') {
            return null;
        }
        return $value;
    }

    private function noLossWhenCastToInt($value)
    {
        return (string) ($value + 0) === $value;
    }

    /**
     * @return bool
     */
    public function isUseNativeFunction()
    {
        return $this->useNativeFunction;
    }

    /**
     * @param bool $useNativeFunction
     *
     * @return IniReader
     */
    public function setUseNativeFunction($useNativeFunction)
    {
        $this->useNativeFunction = $useNativeFunction;

        return $this;
    }
}
