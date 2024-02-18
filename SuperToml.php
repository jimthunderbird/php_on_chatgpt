<?php
/* chat gpt 4:

please write a php class named SuperTomlConvertor to convert the following string:
[db.info]
master = {
     username = "root",
     password = "a#76dd1",
     driver = {
           key = "jim"
           remote = true
           use_pdo = false
     }
}

[github]
username = "abc"
password = "some#password123"


to the following:
db.info.master.username = "root"
db.info.master.password = "1234"
db.info.master.driver.key = "jim"
github.username = "abc"
github.password = "some#password123"
then parse the string above to a php associate array

please note that a value can be either a number, a string or a boolean

wrap the logics above to the method named convert

show me the full php code

 */

class SuperTomlConverter {
    /**
     * Converts a structured string to a flattened format and then parses it
     * into an associative array, handling different value types.
     *
     * @param string $inputString The structured input string.
     * @return array The associative array representation of the input.
     */
    public function convert($inputString) {
        $flattenedString = $this->flattenString($inputString);
        return $this->parseToAssociativeArray($flattenedString);
    }

    /**
     * Flattens a structured string into a dot notation string.
     *
     * @param string $inputString The structured string to be flattened.
     * @return string The flattened string in dot notation.
     */
    private function flattenString($inputString) {
        $result = "";
        $lines = explode("\n", $inputString);
        $pathStack = [];
        $currentSection = "";

        foreach ($lines as $line) {
            if (preg_match('/^\[([^\]]+)\]$/', trim($line), $matches)) {
                // New section starts
                $currentSection = $matches[1];
                $pathStack = []; // Reset path stack for new section
            } elseif (strpos($line, '{') !== false) {
                // Start a new block within the section
                preg_match('/(\w+)\s*=\s*\{/', trim($line), $matches);
                if ($matches) {
                    $pathStack[] = $matches[1];
                }
            } elseif (strpos($line, '}') !== false) {
                // End of a block, pop the last path segment
                array_pop($pathStack);
            } elseif (preg_match('/(\w+)\s*=\s*(.*)/', trim($line), $matches)) {
                // Key-value pairs
                $fullPath = !empty($pathStack) ? $currentSection . '.' . implode('.', $pathStack) . '.' . $matches[1] : $currentSection . '.' . $matches[1];
                $result .= "$fullPath = {$matches[2]}\n";
            }
        }

        return trim($result);
    }

    /**
     * Parses a flattened string into an associative array.
     *
     * @param string $flattenedString The flattened string to be parsed.
     * @return array The associative array.
     */
    private function parseToAssociativeArray($flattenedString) {
        $result = [];
        $lines = explode("\n", $flattenedString);

        foreach ($lines as $line) {
            list($path, $value) = explode(' = ', $line, 2);
            $value = trim($value); // Trim potential whitespace around the value
            $keys = explode('.', $path);
            $temp = &$result;

            foreach ($keys as $key) {
                if (!isset($temp[$key])) {
                    $temp[$key] = [];
                }
                $temp = &$temp[$key];
            }

            // Convert to appropriate data type
            if (is_numeric($value)) {
                $temp = $value + 0; // Convert to numeric type
            } elseif (strtolower($value) === "true" || strtolower($value) === "false") {
                $temp = strtolower($value) === "true"; // Convert to boolean
            } else {
                // Preserve strings, including handling special characters
                $temp = trim($value, '"');
            }
        }

        return $result;
    }
}

// Example usage
$convertor = new SuperTomlConverter();
$inputString = <<<EOT
[db.info]
master = {
     username = "root",
     password = "a#76dd1",
     driver = {
           key = "jim"
           remote = true
           use_pdo = false
     }
}

[github]
username = "abc"
password = "some#password123"
EOT;

$parsedArray = $convertor->convert($inputString);

echo "Resulting Associative Array:\n";
print_r($parsedArray);
