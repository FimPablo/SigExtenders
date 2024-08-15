<?php

namespace FimPablo\SigExtenders\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class Utils
{
    public static function paginatedReturn(string $message, LengthAwarePaginator $data): JsonResponse
    {
        $data = $data->toArray();
        if (!count($data['data'] ?? [])) {
            return response()->json([
                'message' => 'Registros não encontrados!',
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        $requestReturn = [
            'message' => $message,
            'data' => $data['data'] ?? [],
            'currentPage' => $data['current_page'],
            'totalPage' => $data['last_page'],
            'totalPerPage' => count($data['data']),
            'total' => $data['total'],
        ];

        return response()->json($requestReturn);
    }

    public static function generateHistoryColumnsOnMigration(string $prefixColumnName, Blueprint &$table): void
    {
        $prefixColumnName = Str::upper($prefixColumnName);
        $table->dateTime("{$prefixColumnName}INCLUIDOEM");
        $table->uuid("{$prefixColumnName}INCLUIDOPOR");

        $table->dateTime("{$prefixColumnName}ALTERADOEM")->nullable();
        $table->uuid("{$prefixColumnName}ALTERADOPOR")->nullable();

        $table->smallInteger("{$prefixColumnName}EXCLUIDO")->default(0)->nullable();

        $table->index("{$prefixColumnName}EXCLUIDO", "{$prefixColumnName}EXCLUIDO");
    }

    public static function cleanMaskProtocol(string $protocol): int
    {
        return (int) preg_replace('/[^0-9]/', '', $protocol);
    }

    public static function objectfy(array $data): \stdClass
    {
        $objeto = new \stdClass();

        foreach ($data as $key => $value) {
            $objeto->$key = $value;
        }

        return $objeto;
    }

    public static function enumValues($enumerator): array
    {
        return Arr::map($enumerator::cases(), fn($case) => $case->value);
    }

    public static function exceptionReturn(\Throwable $e): JsonResponse
    {
        $code = $e->getCode();

        if ($e->getCode() == 0) {
            $code = 500;
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return response()->json([
                'message' => 'Alguns parametros foram enviados incorretamente. Por favor, verifique e tente novamente.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($e instanceof ValidateException) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], $e->getCode());
        }

        if ($e instanceof QueryException) {
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $message = 'Ocorreu um erro interno do servidor. Por favor, tente novamente mais tarde.';

            if (env('APP_DEBUG')) {
                $message = $e->getMessage();
            }

            abort(response()->json([
                'message' => $message,
            ], 500));
        }

        return response()->json([
            'message' => $e->getMessage(),
        ], $code);
    }

    public static function validateReturn(string $returnMessage, $data, int $httpStatus = Response::HTTP_OK): JsonResponse
    {
        if (!$data || (is_iterable($data) && count($data) == 0)) {
            return response()->json([
                'message' => 'Registros não encontrados!',
                'data' => [],
            ], $httpStatus);
        }

        return response()->json([
            'message' => $returnMessage,
            'data' => $data,
        ], $httpStatus);
    }

    public static function upperSnakeToLowerCamel($string)
    {
        $string = strtolower($string);
        $string = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $string = lcfirst($string);

        return $string;
    }
}
