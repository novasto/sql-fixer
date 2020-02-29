<?php

namespace novasto\SqlFixer;

use SebastianBergmann\Diff\Differ;

class SqlFixer
{
    /**
     * Format and output to stdout
     *
     * @param string $path
     * @return string|false
     * @throws \Exception
     */
    public static function diff(string $path)
    {
        [$formatted, $has_diff] = self::format($path);

        if (!$has_diff) {
            return false;
        }

        return (new Differ())->diff(file_get_contents($path), $formatted);
    }

    /**
     * Format and overwrite
     *
     * @param string $path
     * @return bool
     * @throws \Exception
     */
    public static function fix(string $path)
    {
        [$formatted, $has_diff] = self::format($path);

        if (!$has_diff) {
            return false;
        }

        file_put_contents($path, $formatted);

        return true;
    }

    /**
     * Return formatted string
     *
     * @param string $path
     * @return array [$result, $has_diff]
     * @throws \Exception
     */
    public static function format(string $path)
    {
        $source = $origin = file_get_contents($path);

        if (substr($path, -4) == '.php') {
            preg_match_all('/<<<\s*[\'"]?SQL[\'"]?(.*?)SQL/s', $source, $matches);
            $matches = $matches[1];
        } elseif (substr($path, -4) == '.sql') {
            $matches = [$source];
        } else {
            throw new \Exception('Unprocessable file');
        }

        foreach ($matches as $v) {
            $formatted = \SqlFormatter::format(trim($v), false);

            // remove white space on EOL
            $split_formatted = explode(PHP_EOL, $formatted);
            foreach ($split_formatted as &$v2) {
                $v2 = rtrim($v2);
            }
            unset($v2);

            $source = str_replace(trim($v), implode(PHP_EOL, $split_formatted), $source);
        }

        return [$source, $source !== $origin];
    }

    /**
     * Search files
     *
     * @param string $root
     * @param array $paths
     * @return array
     * @throws \Exception
     */
    public static function fileLists(string $root, array $paths)
    {
        $lists = [];

        foreach ($paths as $path) {
            if (!file_exists($root.$path)) {
                throw new \Exception("{$path} is not found");
            }

            if (!is_dir($root.$path)) {
                $lists[] = $root.$path;
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $root.$path,
                    \FilesystemIterator::CURRENT_AS_PATHNAME
                )
            );

            foreach ($files as $file) {
                $lists[] = $file;
            }
        }

        $lists = array_filter($lists, function ($v) {
            return in_array(substr($v, -4), ['.php', '.sql']);
        });

        return array_values($lists);
    }
}
