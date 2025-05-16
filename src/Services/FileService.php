<?php

namespace Composite\LaravelAutoCrud\Services;

use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Symfony\Component\String\b;

class FileService
{
    public function createFromStub(array $modelData, string $stubType, string $basePath, string $suffix, bool $overwrite = false, ?callable $dataCallback = null, bool $pathFromApp = true, string $extension = 'php', string $fileName = null): string
    {
        $stubPath = __DIR__ . "/../Stubs/{$stubType}.stub";
        $namespace = 'App\\' . str_replace('/', '\\', $basePath);

        if ($modelData['folders']) {
            $namespace .= '\\' . str_replace('/', '\\', $modelData['folders']);
        }

        $filePath = $this->generateFilePath($modelData, $basePath, $suffix, $pathFromApp, $extension, $fileName);

        if (!$overwrite) {
            if (file_exists($filePath)) {
                $overwrite = confirm(
                    label: ucfirst($suffix) . ' file already exists, do you want to overwrite it? ' . $filePath
                );
                if (!$overwrite) {
                    return $namespace . '\\' . $modelData['modelName'] . $suffix;
                }
            }
        }

        File::ensureDirectoryExists(dirname($filePath), 0777, true);

        $data = $dataCallback ? $dataCallback($modelData) : [];
        $content = $this->generateContent($stubPath, $modelData, $namespace, $suffix, $data);

        File::put($filePath, $content);

        info("Created: $filePath");

        return $namespace . '\\' . $modelData['modelName'] . $suffix;
    }

    private function generateFilePath(array $modelData, string $basePath, string $suffix, bool $pathFromApp, string $extension, string $fileName = null): string
    {
        $fileName = $fileName ?? $modelData['modelName'];
        if ($modelData['folders']) {
            $path = "{$basePath}/{$modelData['folders']}/{$fileName}{$suffix}.{$extension}";
            if ($pathFromApp) {
                return app_path($path);
            }
            return base_path($path);
        }

        $path = "{$basePath}/{$fileName}{$suffix}.{$extension}";
        if ($pathFromApp) {
            return app_path($path);
        }
        return base_path($path);
    }

    private function generateContent(string $stubPath, array $modelData, string $namespace, string $suffix, array $data = []): string
    {
        $replacements = [
            '{{ class }}' => $modelData['modelName'] . $suffix,
            '{{ namespace }}' => $namespace,
            ...$data,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), file_get_contents($stubPath));
    }
}
