<?php

namespace FimPablo\SigExtenders\Auth;

use DomainException;
use FimPablo\SigExtenders\Utils\Arr;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth as BaseJWTAuth;

/**
 * Autenticação JWT.
 *
 * Extende a classe Tymon\JWTAuth para adicionar funcionalidades especificas necessárias para os sistemas.
 */
class JWTAuth extends BaseJWTAuth
{
    /**
     * Extrai do token o usuário logado.
     * 
     * Entende-se ocmo usuário do SSO logado
     *
     * @return string | object string 'sistema' ou objeto de usuário.
     */
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

    /**
     * Recolhe o UUID da pessoa logada.
     * Entende-se como pessoa logada aquela que acessou o sistema, NÂO o perfil selecionado.
     *
     * @return string PESID.
     */
    public static function getLoggedPesid()
    {
        $payload = self::getTokenPayload(false);

        if (!$payload) {
            return 'sistema';
        }

        if (!Arr::get($payload, 'PESID')) {
            throw new DomainException('Pessoa não encontrada no token da requisição', 404);
        }

        return $payload['PESID'];
    }

    /**
     * Recolhe o UUID da pessoa perfilada.
     * Entende-se como pessoa perfilada o perfil selecionado NÂO aquela que acessou o sistema.
     *
     * @return string PESID.
     */
    public static function getAcessPesid(): string|int
    {
        $payload = self::getTokenPayload(false);

        if (!$payload) {
            return 'sistema';
        }

        if (!Arr::get($payload, 'LOGGED_AT')) {
            throw new DomainException('Pessoa não encontrada no token da requisição', 404);
        }

        return $payload['LOGGED_AT'];
    }

    /**
     * Recolhe o UUID do grupo da pessoa perfilada.
     * 
     * @return string GRPUUID.
     */
    public static function getAccessGrpid()
    {
        $payload = self::getTokenPayload(false);

        if (!$payload) {
            return null;
        }

        if (!Arr::get($payload, 'LOGGED_GRPID')) {
            throw new DomainException('GrupoOrigem não encontrado', 404);
        }

        return $payload['LOGGED_GRPID'];
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

    /**
     * Verifica se o nível de acesso do perfil acessado está entre os iformados.
     *
     * @param int|array $accessLeval Níveis de acesso.
     * @return bool Está entre os niveis informados.
     */
    public static function validateAccessLevel(int|array $accessLevel)
    {
        if (is_int($accessLevel)) {
            $accessLevel = [$accessLevel];
        }

        return in_array(JWTAuth::getAccessGrpid(), $accessLevel);
    }

    /**
     * Verifica se a pessoa logada está personificada
     * 
     * @return boolean está personificado.
     */
    public static function isPersonificated()
    {
        return self::getAcessPesid() != self::getLoggedPesid();
    }
}
