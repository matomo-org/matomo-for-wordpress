<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3
 */

namespace Matomo\Ini;

/**
 * Writes INI configuration.
 */
class IniWriter
{
    /**
     * Writes an array configuration to a INI file.
     *
     * The array provided must be multidimensional, indexed by section names:
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
     * @param string $filename
     * @param array $config
     * @param string $header Optional header to insert at the top of the file.
     * @throws IniWritingException
     */
    public function writeToFile($filename, array $config, $header = '')
    {
        $ini = $this->writeToString($config, $header);

        if (!file_put_contents($filename, $ini)) {
            throw new IniWritingException(sprintf('Impossible to write to file %s', $filename));
        }
    }

    /**
     * Writes an array configuration to a INI string and returns it.
     *
     * The array provided must be multidimensional, indexed by section names:
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
     * @param array $config
     * @param string $header Optional header to insert at the top of the file.
     * @return string
     * @throws IniWritingException
     */
    public function writeToString(array $config, $header = '')
    {
        $ini = $header;

        $sectionNames = array_keys($config);

        foreach ($sectionNames as $sectionName) {
            $section = $config[$sectionName];

            // no point in writing empty sections
            if (empty($section)) {
                continue;
            }

            if (! is_array($section)) {
                throw new IniWritingException(sprintf("Section \"%s\" doesn't contain an array of values", $sectionName));
            }

            $sectionName = $this->encodeSectionName($sectionName);
            $ini .= "[$sectionName]\n";

            foreach ($section as $option => $value) {
                if (is_numeric($option)) {
                    $option = $sectionName;
                    $value = array($value);
                }

                if (is_array($value)) {
                    foreach ($value as $key => $currentValue) {
                        if (is_int($key)) {
                            $ini .= $this->encodeKey($option) . '[] = ' . $this->encodeValue($currentValue) . "\n";
                        } else {
                            $ini .= $this->encodeKey($option) . '[' . $this->encodeKey($key) . '] = ' . $this->encodeValue($currentValue) . "\n";
                        }
                    }
                } else {
                    $encodedOption = $this->encodeOptionName($option);

                    if ($encodedOption === '') {
                        throw new IniWritingException(sprintf('Option name "%s" cannot be written', $option));
                    }

                    $ini .= $encodedOption . ' = ' . $this->encodeValue($value) . "\n";
                }
            }

            $ini .= "\n";
        }

        return $ini;
    }

    /**
     * @param $value
     * @return int|string
     */
    private function encodeValue($value)
    {
        if (is_bool($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            // The native parse_ini_string() cannot read an escaped quote before a line
            // break, and a single remaining one would end up as the last character of a
            // line, where it is no longer distinguishable from the closing quote. Keep this.
            $value = preg_replace('/"+([\n\r])/', '$1', $value);
            // Backslashes are escaped as well, so a value ending in one cannot leave the
            // closing quote preceded by a lone backslash. IniReader reverses this.
            $value = addcslashes($value, '\\"');
            return '"' . $value . '"';
        }

        return $value;
    }

    /**
     * @param $key
     * @return string
     */
    private function encodeKey($key)
    {
        $key = preg_replace('/[^A-Za-z0-9\-_]/', '', $key);

        return $key;
    }

    /**
     * Removes the characters that would change the structure of the file when they appear in
     * an option name. Brackets are kept, as they denote an array. Everything else is kept as
     * well, so names such as "db.host" stay unchanged.
     *
     * @param $key
     * @return string
     */
    private function encodeOptionName($key)
    {
        $key = preg_replace('/[\r\n\t=;#"\']/', '', $key);

        return trim($key);
    }

    /**
     * @param $key
     * @return string
     */
    private function encodeSectionName($key)
    {
        $key = preg_replace('/[^A-Za-z0-9_ \-]/', '', $key);

        return $key;
    }
}
