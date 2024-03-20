<?php

namespace App\Services;

use App\Contracts\SessionInterface;
use App\DTO\DataTableQueryParamsDTO;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestService
{
    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function getReferer(ServerRequestInterface $request): string
    {
        $referer = $request->getHeader('referer')[0] ?? '';

        if (! $referer) {
            return $this->session->get('previousURL');
        }

        // extra protection layer ( referer is coming from host )
        if ($request->getUri()->getHost() !== parse_url($referer, PHP_URL_HOST)) {
            $referer = $this->session->get('previousURL');
        }

        return $referer;
    }

    public function isXhr(RequestInterface $request): bool
    {
        return $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    public function getDataTableQueryParams(ServerRequestInterface $request): DataTableQueryParamsDTO
    {
        $params = $request->getQueryParams();

        return new DataTableQueryParamsDTO(
            (int)$params['draw'],
            (int)$params['start'],
            (int)$params['length'],
            $params['columns'][$params['order'][0]['column']]['data'],
            $params['order'][0]['dir'],
            $params['search']['value'],
        );
    }

    public function getClientIp(ServerRequestInterface $request, array $trustedProxies): ?string
    {
        $serverParams = $request->getServerParams();

        if (in_array($serverParams['REMOTE_ADDR'], $trustedProxies, true)
            && isset($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);

            // the header takes many addresses we retrieve the first one as the original sender
            return trim($ips[0]);
        }

        return $serverParams['REMOTE_ADDR'] ?? null;
    }

}