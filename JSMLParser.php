<?php
/* chat gpt 4:

This is a parser for Jim's Simple Markup Language (JSML)

please write a php class named JSMLParser to convert the following string:
[db.info]
master = {
     username = "root",
     password = "a#76dd1"
     driver = {
           key = "jim",
           remote = true,
           use_pdo = false
     }
}

[anotherdb.info]
master = { 
     username = "root",
     password = "a#76dd1"
     driver = {
           key = "jim",
           remote = true,
           use_pdo = false
     }
}

[html]
    head = {
       title = "test title"
}

[github]
username = "abc"
password = "some#password123"


to the following:
db.info.master.username = "root"
db.info.master.password = "a#76dd1"
db.info.master.driver.key = "jim"
db.info.master.driver.remote = true
db.info.master.driver.use_pdo = false
anotherdb.info.username = "root"
anotherdb.info.password = "a#76dd1"
anotherdb.info.master.driver.key = "jim"
anotherdb.info.master.driver.remote = true
anotherdb.info.master.driver.use_pdo = false
github.username = "abc"
github.password = "some#password123"
html.head.title = "test title"
then parse the string above to a php associate array

please note that a value can be either a number, a string or a boolean

when the value is a string, it must be wrapped in double quote or single quote

wrap the logics above to the method named parse

show me the full php code

*/

class JSMLParser {
    /**
     * Parses the structured string into an associative array.
     *
     * @param string $inputString The structured TOML-like string.
     * @return array The associative array representation of the input.
     */
    public function parse($inputString) {
        $flattenedString = $this->flattenString($inputString);
        return $this->parseToAssociativeArray($flattenedString);
    }

    /**
     * Converts the structured string to a flattened string representation.
     *
     * @param string $inputString The structured string.
     * @return string The flattened string representation.
     */
    private function flattenString($inputString) {
        $result = "";
        $lines = explode("\n", $inputString);
        $currentPath = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[([^\]]+)\]$/', trim($line), $matches)) {
                // Handle new sections
                $currentPath = [$matches[1]];
            } elseif (preg_match('/^([-\w]+)\s*=\s*\{/', trim($line), $matches)) {
                // Handle new blocks within sections, allowing for dashes in keys
                $currentPath[] = $matches[1];
            } elseif (strpos($line, '}') !== false) {
                // End of a block
                array_pop($currentPath);
            } elseif (preg_match('/^([-\w]+)\s*=\s*(.*)/', trim($line), $matches)) {
                // Key-value pairs, allowing for dashes in keys
                $path = implode('.', $currentPath) . '.' . $matches[1];
                $value = $matches[2];
                // Check if value is an array and keep it as-is if true
                if (preg_match('/^\[.*\]$/', $value)) {
                    // No need to trim quotes for array values
                } else {
                    // Ensure non-array strings are wrapped in quotes
                    if (!is_numeric($value) && strtolower($value) !== 'true' && strtolower($value) !== 'false') {
                        $value = '"' . trim($value, "\"'") . '"';
                    }
                }
                $result .= "$path = $value\n";
            }
        }

        return trim($result);
    }

    /**
     * Parses the flattened string representation into an associative array.
     *
     * @param string $flattenedString The flattened string representation.
     * @return array The associative array.
     */
    private function parseToAssociativeArray($flattenedString) {
        $result = [];
        $lines = explode("\n", $flattenedString);

        foreach ($lines as $line) {
            list($path, $value) = explode(' = ', $line, 2);
            $keys = explode('.', $path);
            $temp = &$result;

            foreach ($keys as $key) {
                if (!isset($temp[$key])) {
                    $temp[$key] = [];
                }
                $temp = &$temp[$key];
            }

            // Check if value is an array
            if (preg_match('/^\[(.*)\]$/', $value, $matches)) {
                // Parse the array value
                $arrayValues = explode(', ', $matches[1]);
                $temp = array_map(function($item) {
                    $item = trim($item);
                    if (is_numeric($item)) {
                        return $item + 0;
                    } elseif (strtolower($item) === 'true' || strtolower($item) === 'false') {
                        return strtolower($item) === 'true';
                    } else {
                        return trim($item, "\"'");
                    }
                }, $arrayValues);
            } elseif (is_numeric($value)) {
                $temp = $value + 0;
            } elseif ($value === 'true' || $value === 'false') {
                $temp = $value === 'true';
            } else {
                $temp = trim($value, "\"'");
            }
        }

        return $result;
    }
}

// Example usage
$parser = new JSMLParser();
$inputString = <<<EOT
[db.info]
master = {
     username = "root",
     password = "a#76dd1",
     driver = {
           key = "jim",
           remote = true,
           use_pdo = false
     }
}

[anotherdb.info]
master = { 
     username = "root",
     password = "a#76dd1",
     driver = {
           key = "jim",
           remote = true,
           use_pdo = false
     }
}

# we can override the key
[anotherdb.info.master]
password = "12345678"

[github]
username = "abc"
password = "some#password123"
numbers = [1, "test", true]

[html.structure]
head = {
   title = "test title",
   meta = {
      name = "description",
      content = "some description"
   }
}
body = { 
    div = {
        id = "main-wrapper",
        class = "font-bold"
        button = {
            class = "font-bold",
            htmx-get = "/contact"
        }
    }
}

[user]
name="jim2"
email = "jim@somewebsite.com"

EOT;

$parsedArray = $parser->parse($inputString);

echo "Resulting Associative Array:\n";
print_r($parsedArray);
