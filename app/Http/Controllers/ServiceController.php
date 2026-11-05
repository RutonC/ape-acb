<?php

namespace App\Http\Controllers;

use App\Enums\ServiceKind;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Contracts\View\View;

/**
 * Catálogo público: listagem e detalhe de serviços.
 *
 * A listagem acrescenta, depois da grelha, os blocos da página do painel com
 * o mesmo endereço (ex.: "servicos" com a secção "Como contratar").
 */
class ServiceController extends Controller
{
    public function services(): View
    {
        return $this->index(ServiceKind::Service, 'servicos');
    }

    public function service(string $slug): View
    {
        return $this->show(ServiceKind::Service, $slug);
    }

    private function index(ServiceKind $kind, string $pageSlug): View
    {
        $page = Page::published()->where('slug', $pageSlug)->first();

        return view('services.index', [
            'kind' => $kind,
            'items' => Service::published()->ofKind($kind)->get(),
            'page' => $page,
            'blocks' => $page?->blocks() ?? [],
        ]);
    }

    private function show(ServiceKind $kind, string $slug): View
    {
        $service = Service::published()->where('kind', $kind)->where('slug', $slug)->firstOrFail();

        return view('services.show', [
            'service' => $service,
            'kind' => $kind,
            'others' => Service::published()->ofKind($kind)->whereKeyNot($service->getKey())->limit(3)->get(),
        ]);
    }
}
