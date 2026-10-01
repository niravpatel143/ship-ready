<?php

namespace ShipReady\Analyzers;

use Symfony\Component\Finder\Finder;

final class BladeAnalyzer
{
    private array $viewPaths;

    public function __construct(array $viewPaths)
    {
        $this->viewPaths = $viewPaths;
    }

    /**
     * Find all raw (unescaped) echo expressions: {!! ... !!}
     *
     * @return EchoInfo[]
     */
    public function rawEchoes(): array
    {
        $results = [];

        foreach ($this->bladeFiles() as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                if (preg_match_all('/\{!!\s*(.+?)\s*!!\}/', $line, $matches)) {
                    foreach ($matches[1] as $expression) {
                        $results[] = new EchoInfo(
                            expression: $expression,
                            file:       $file,
                            line:       $lineNo + 1
                        );
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Find all safe escaped echoes: {{ ... }}
     *
     * @return EchoInfo[]
     */
    public function escapedEchoes(): array
    {
        $results = [];

        foreach ($this->bladeFiles() as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                if (preg_match_all('/\{\{\s*(.+?)\s*\}\}/', $line, $matches)) {
                    foreach ($matches[1] as $expression) {
                        $results[] = new EchoInfo(
                            expression: $expression,
                            file:       $file,
                            line:       $lineNo + 1
                        );
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @return string[]
     */
    private function bladeFiles(): array
    {
        $files = [];

        foreach ($this->viewPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            try {
                $finder = Finder::create()
                    ->files()
                    ->name('*.blade.php')
                    ->in($path);

                foreach ($finder as $file) {
                    $files[] = $file->getRealPath();
                }
            } catch (\Throwable $e) {
                // Skip inaccessible
            }
        }

        return $files;
    }
}
