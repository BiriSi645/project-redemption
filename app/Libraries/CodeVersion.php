<?php

namespace App\Libraries;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CodeVersion
{
    private const CACHE_KEY = 'project_redemption_code_version';

    public function current(): string
    {
        $releaseVersion = trim((string) env('app.version', ''));
        if ($releaseVersion !== '') {
            return hash('sha256', $releaseVersion);
        }

        $cache = service('cache');
        $cached = $cache->get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $latestModified = 0;
        $totalSize = 0;
        $fileCount = 0;

        foreach ([APPPATH, FCPATH] as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['php', 'js', 'css'], true)) {
                    continue;
                }
                $latestModified = max($latestModified, $file->getMTime());
                $totalSize += $file->getSize();
                $fileCount++;
            }
        }

        $version = hash('sha256', $latestModified . '|' . $totalSize . '|' . $fileCount);
        $cache->save(self::CACHE_KEY, $version, 30);

        return $version;
    }
}
