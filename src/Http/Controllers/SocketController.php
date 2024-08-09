<?php

namespace FimPablo\SigExtenders\Http\Controllers;

use FimPablo\SigExtenders\Helpers\Utils;
use FimPablo\SigExtenders\Http\Requests\Socket\SocketRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use FimPablo\SigExtenders\Database\External\ExternalModelSocket;
use Exception;
use Illuminate\Support\Facades\Log;

class SocketController extends Controller
{
    public function __invoke(SocketRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $model = ExternalModelSocket::modelFinder($request->model);

            $builder = $model->query();

            foreach ($request->wheres as $where) {
                $method = match ($where['type']) {
                    'Basic' => 'where',
                    'In' => 'whereIn',
                    default => null,
                };

                if ($where['boolean'] === 'and not') {
                    $method = 'whereNot' . ucfirst($method);
                }

                if ($where['boolean'] === 'or') {
                    $method = 'orWhere' . ucfirst($method);
                }

                Arr::get($where, 'operator') ?
                    $builder->$method($where['column'], $where['operator'], Arr::get($where, 'value', Arr::get($where, 'values'))) :
                    $builder->$method($where['column'], Arr::get($where, 'value', Arr::get($where, 'values')));
            }

            $collect = $builder->with($request->relations ?? [])->get();

        } catch (Exception $exception) {
            Log::error('Ocorreu um erro ao tentar buscar modelo', [
                'request' => $request->all(),
                'erro' => $exception->getMessage(),
            ]);
            return Utils::exceptionReturn($exception);
        }

        return response()->json([
            'message' => 'retivied',
            'data' => $collect,
        ], 200);
    }
}
