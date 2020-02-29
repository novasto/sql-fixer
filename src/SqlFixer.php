<?php

namespace Novasto\SqlFixer;

use SebastianBergmann\Diff\Differ;

class SqlFixer
{
    /**
     * Get diff
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
     * Get formatted string
     *
     * @param string $path
     * @return array [$result, $has_diff]
     * @throws \Exception
     */
    public static function format(string $path)
    {
        $source = $origin = file_get_contents($path);

        if (substr($path, -4) == '.php') {
            preg_match_all('/<<<\s*[\'"]?SQL[\'"]?\n(.*?)\nSQL/s', $source, $matches);
            $matches = $matches[1];
        } elseif (substr($path, -4) == '.sql') {
            $matches = [$source];
        } else {
            throw new \Exception('Unprocessable file');
        }

        foreach ($matches as $v) {
            $mock_map = [];

            $formatted = trim($v);

            // replace variable
            $formatted = preg_replace_callback('/\{ *\$.+?\}/', function($matches)use(&$mock_map){
                $mock = 'sqlfixer_'.bin2hex(random_bytes(10));
                $mock_map[$mock] = $matches[0];
                return $mock;
            }, $formatted);

            // format
            $formatted = \SqlFormatter::format($formatted, false);

            // restore variable
            foreach($mock_map as $mk => $mv){
                $formatted = str_replace($mk, $mv, $formatted);
            }

            // remove white space on EOL
            $formatted = preg_replace('/ \n/', "\n", $formatted);

            $source = str_replace($v, $formatted, $source);
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
            $absolute_path = substr($path, 0, 1) === '/' ? $path : $root.$path;

            if (!file_exists($absolute_path)) {
                throw new \Exception("{$path} is not found");
            }

            if (!is_dir($absolute_path)) {
                $lists[] = $absolute_path;
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $absolute_path,
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
