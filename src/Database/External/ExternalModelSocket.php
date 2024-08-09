<?php

namespace FimPablo\SigExtenders\Database\External;

use FimPablo\SigExtenders\Utils\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalModelSocket
{
    protected string $url;
    protected array $defaultHeader;

    public function search(string $modelName, array $wheres, Collection|array $relations): Collection
    {
        $request = Http::withHeaders($this->defaultHeader)
            ->withOptions([
                'verify' => false,
            ])
            ->post($this->url . '/socket', [
                'model' => $modelName,
                'wheres' => $wheres,
                'relations' => $relations,
            ]);

        if ($request->status() != 200) {
            Log::error("Ocorreu um erro ao tentar buscar o modelo {$modelName}", [
                'status' => $request->status(),
                'response' => $request->json(),
            ]);

            return collect();
        }

        return collect($request->json()['data']);
    }

    public static function modelFinder($modelName)
    {
        $foundModel = self::findNamespaceForFile($modelName);

        if (!$foundModel) {
            throw new \DomainException("Model {$modelName} não encontrada no ambiente externo", 500);
        }

        $foundModel .= "\\{$modelName}";
        return new $foundModel();
    }

    private static function findNamespaceForFile($fileName)
    {
        $baseDir = app_path('Models');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir)
        );

        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
        $file = Arr::where(Arr::flatten(iterator_to_array($files)), fn($filePath) => Str::endsWith($filePath, DIRECTORY_SEPARATOR . "{$fileName}.php"));

        if (empty($file)) {
            return false;
        }

        return self::getNamespaceFromFile(Arr::first($file));
    }

    private static function getNamespaceFromFile($filePath)
    {
        $lines = file($filePath);

        foreach ($lines as $line) {
            if (preg_match('/^namespace\s+(.+);/m', $line, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }
}
