<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

/**
 * Páginas públicas.
 *
 * As páginas criadas no painel são mostradas em /{slug}. As rotas fixas
 * (sobre-nos, contactos, …) mostram a página do painel com o mesmo endereço
 * quando existe e está publicada; caso contrário, um conteúdo provisório que
 * as fases seguintes substituem (ver docs/PLANO.md).
 */
class PageController extends Controller
{
    public function home(): View
    {
        $page = Page::published()->where('is_homepage', true)->first();

        return $page ? $this->render($page) : view('pages.home');
    }

    public function show(string $slug): View
    {
        $page = Page::published()->where('slug', $slug)->where('is_homepage', false)->firstOrFail();

        return $this->render($page);
    }

    public function preview(Page $page, string $locale): View
    {
        app()->setLocale($locale);

        return $this->render($page, preview: true);
    }

    public function about(): View
    {
        return $this->pageOrPlaceholder('sobre-nos', 'Sobre Nós');
    }

    public function partners(): View
    {
        return $this->pageOrPlaceholder('parceiros', 'Parceiros');
    }

    public function privacy(): View
    {
        return $this->pageOrPlaceholder('politica-de-privacidade', 'Política de Privacidade');
    }

    public function terms(): View
    {
        return $this->pageOrPlaceholder('termos-de-utilizacao', 'Termos de Utilização');
    }

    private function render(Page $page, bool $preview = false): View
    {
        $blocks = $page->blocks();

        return view('pages.show', [
            'page' => $page,
            'blocks' => $blocks,
            'preview' => $preview,
            'showBanner' => ! $page->is_homepage && ($blocks[0]['type'] ?? null) !== 'hero',
        ]);
    }

    private function pageOrPlaceholder(string $slug, string $title): View
    {
        $page = Page::published()->where('slug', $slug)->first();

        return $page ? $this->render($page) : $this->placeholder($title);
    }

    private function placeholder(string $title): View
    {
        return view('pages.placeholder', [
            'title' => __($title),
            'breadcrumbs' => [['label' => __($title)]],
        ]);
    }
}
