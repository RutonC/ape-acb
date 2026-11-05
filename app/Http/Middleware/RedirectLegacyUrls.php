<?php

namespace App\Http\Middleware;

use App\Models\JobCategory;
use App\Models\JobOpening;
use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endereços do site antigo: redirecção permanente para os novos, para não
 * perder ligações externas nem posições nos motores de busca.
 *
 * O site antigo usava /index.php/... O Nginx de produção retira o /index.php
 * antes de chegar aqui, por isso os dois formatos têm de ser reconhecidos:
 * /index.php/vagas/20-mecanica/31-vaga-de-chefe e /vagas/20-mecanica/31-vaga-de-chefe.
 */
class RedirectLegacyUrls
{
    /** Também são endereços novos: só se redirecciona quando trazem /index.php. */
    private const SHARED = [
        '' => '/',
        'vagas' => '/vagas',
        'servicos' => '/servicos',
        'noticias' => '/noticias',
        'sobre-nos' => '/sobre-nos',
        'contactos' => '/contactos',
    ];

    /** Serviços: identificador antigo => endereço novo. */
    private const SERVICES = [
        15 => '/servicos/recrutamento-e-seleccao',
        14 => '/servicos/centro-de-avaliacao',
        9 => '/servicos/estagio-profissional',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        $uri = (string) parse_url($request->getRequestUri(), PHP_URL_PATH);
        $hasIndex = (bool) preg_match('#^/index\.php(?:/|$)#', $uri);
        $path = trim((string) preg_replace('#^/index\.php#', '', $uri), '/');

        $target = $this->target($path, $hasIndex);

        // Endereço absoluto a partir do domínio: redirect('/x') manteria o /index.php do pedido
        // e /index.php redireccionaria para si próprio.
        return $target ? redirect()->away($request->getSchemeAndHttpHost().$target, 301) : $next($request);
    }

    private function target(string $path, bool $hasIndex): ?string
    {
        if ($hasIndex && array_key_exists($path, self::SHARED)) {
            return self::SHARED[$path];
        }

        // /vagas/20-mecanica/31-vaga-de-chefe-de-oficina
        if (preg_match('#^vagas/\d+-[a-z0-9-]+/\d+-([a-z0-9-]+)$#', $path, $matches)) {
            return JobOpening::where('slug', $matches[1])->exists() ? '/vagas/'.$matches[1] : '/vagas';
        }

        // /vagas/20-mecanica. Um endereço novo pode começar por um número: nesse caso não é antigo.
        if (preg_match('#^vagas/\d+-([a-z0-9-]+)$#', $path, $matches) && ! JobOpening::where('slug', substr($path, 6))->exists()) {
            return JobCategory::where('slug', $matches[1])->exists() ? '/vagas?categoria='.$matches[1] : '/vagas';
        }

        if (preg_match('#^servicos/(\d+)-#', $path, $matches)) {
            return self::SERVICES[(int) $matches[1]] ?? '/servicos';
        }

        if (preg_match('#^noticias/\d+-(.+)$#', $path, $matches) && ! Post::where('slug', substr($path, 9))->exists()) {
            return '/noticias/'.$matches[1];
        }

        return match (true) {
            str_starts_with($path, '11-sem-categoria/') => '/',
            str_starts_with($path, '13-parceiros/') => '/parceiros',
            str_starts_with($path, '21-testemunhos/') => '/sobre-nos#testemunhos',
            default => null,
        };
    }
}
