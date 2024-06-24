<?php

namespace FimPablo\SigExtenders\Auth;

use DomainException;
use FimPablo\SigExtenders\Utils\Arr;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth as BaseJWTAuth;

class JWTAuth extends BaseJWTAuth
{
    public static function getLoggedUser()
    {
        $payload = self::getTokenPayload(false);

        if (!$payload) {
            return 'sistema';
        }

        $user = Arr::where($payload, function ($value, $key) {
            return Str::startsWith($key, 'USU');
        });

        return (object) $user;
    }

    public static function getLoggedPesid()
    {
        $payload = self::getTokenPayload(false);

        if (!$payload) {
            return 'sistema';
        }

        if (!Arr::get($payload, 'PESID')) {
            throw new DomainException('Pessoa não encontrada no token da requisição', 404);
        }

        return (int) $payload['PESID'];
    }

    public static function getAcessPesid(): string|int
    {
        $payload = self::getTokenPayload(false);

        if (!$payload) {
            return 'sistema';
        }

        if (!Arr::get($payload, 'LOGGED_AT')) {
            throw new DomainException('Pessoa não encontrada no token da requisição', 404);
        }

        return (int) $payload['LOGGED_AT'];
    }

    public static function getAccessGrpid()
    {
        $payload = self::getTokenPayload(false);

        if (!$payload) {
            return null;
        }

        if (!Arr::get($payload, 'LOGGED_GRPID')) {
            throw new DomainException('GrupoOrigem não encontrado', 404);
        }

        return (int) $payload['LOGGED_GRPID'];
    }

    private static function getTokenPayload($throw = true)
    {
        if (!self::getToken() || !self::getPayload(self::getToken())) {
            if (!$throw)
                return null;

            throw new DomainException('Falha ao converter token, verifique o token informado ou logue novamente', 404);
        }

        return self::getPayload(self::getToken())->toArray();
    }

    public static function validateAccessLevel(int|array $accessLevel)
    {
        if (is_int($accessLevel)) {
            $accessLevel = [$accessLevel];
        }

        return in_array(JWTAuth::getAccessGrpid(), $accessLevel);
    }

    public static function isPersonificated()
    {
        return (int) self::getAcessPesid() != (int) self::getLoggedPesid();
    }
}
