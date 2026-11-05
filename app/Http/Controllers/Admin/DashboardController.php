<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\InboxStatus;
use App\Enums\ServiceKind;
use App\Http\Controllers\Controller;
use App\Models\CompanyRegistration;
use App\Models\ContactMessage;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\JobOpening;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\Service;
use App\Models\TeamMember;
use App\Support\Localization;
use App\Support\TranslationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Início do painel: contadores, últimas mensagens e, com o inglês activo, conteúdo
 * sem versão inglesa. Cada bloco só inclui o que a área do utilizador permite.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'counts' => $this->counts($request),
                'applications' => $user->can('applications') ? $this->recentApplications() : null,
                'messages' => $user->can('messages') ? $this->recentMessages() : null,
                'translations' => $this->translations($request),
            ],
        ]);
    }

    /**
     * @return array<string, array{label: string, count: int, path: string}>
     */
    private function counts(Request $request): array
    {
        $user = $request->user();
        $items = [
            'applications' => ['Candidaturas novas', fn () => JobApplication::where('status', ApplicationStatus::New)->count(), '/candidaturas?status=new'],
            'companies' => ['Empresas por contactar', fn () => CompanyRegistration::where('status', InboxStatus::New)->count(), '/inscricoes-de-empresas?status=new'],
            'messages' => ['Mensagens novas', fn () => ContactMessage::where('status', InboxStatus::New)->count(), '/mensagens?status=new'],
            'jobs' => ['Vagas abertas', fn () => JobOpening::open()->count(), '/vagas'],
            'pages' => ['Páginas publicadas', fn () => Page::published()->count(), '/paginas'],
            'services' => ['Serviços visíveis', fn () => Service::where('kind', ServiceKind::Service)->where('is_published', true)->count(), '/servicos'],
            'blog' => ['Notícias publicadas', fn () => Post::published()->count(), '/blog/artigos'],
        ];

        $result = [];

        foreach ($items as $area => [$label, $count, $path]) {
            if ($user->can($area)) {
                $result[$area] = ['label' => $label, 'count' => $count(), 'path' => $path];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentApplications(): array
    {
        return JobApplication::with('jobOpening')->latest()->limit(5)->get()->map(fn (JobApplication $application) => [
            'id' => $application->id,
            'name' => $application->name,
            'position' => $application->positionLabel(),
            'status' => $application->status->value,
            'status_label' => $application->status->getLabel(),
            'created_at' => $application->created_at?->toIso8601String(),
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentMessages(): array
    {
        return ContactMessage::latest()->limit(5)->get()->map(fn (ContactMessage $message) => [
            'id' => $message->id,
            'name' => $message->name,
            'excerpt' => str($message->message)->limit(90)->toString(),
            'status' => $message->status->value,
            'status_label' => $message->status->getLabel(),
            'created_at' => $message->created_at?->toIso8601String(),
        ])->all();
    }

    /**
     * Conteúdo com texto em português e sem inglês, só das áreas permitidas.
     *
     * @return array<int, array{type: string, title: string, missing: string, path: string}>|null
     */
    private function translations(Request $request): ?array
    {
        if (! array_key_exists('en', config('site.locales'))) {
            return null;
        }

        $user = $request->user();

        return TranslationStatus::pending()
            ->map(fn (array $item) => [...$this->describe($item['record']), 'missing' => TranslationStatus::labels($item['missing'])])
            ->filter(fn (array $item) => $user->can($item['area']))
            ->map(fn (array $item) => collect($item)->only(['type', 'title', 'missing', 'path'])->all())
            ->values()
            ->all();
    }

    /**
     * Área, nome do tipo, título e endereço de edição no painel.
     *
     * @return array{area: string, type: string, title: string, path: string}
     */
    private function describe(Model $record): array
    {
        $title = $this->titleOf($record);

        return match (true) {
            $record instanceof Page => ['area' => 'pages', 'type' => 'Página', 'title' => $title, 'path' => "/paginas/{$record->id}"],
            $record instanceof Service => ['area' => 'services', 'type' => 'Serviço', 'title' => $title, 'path' => "/servicos/{$record->id}"],
            $record instanceof JobOpening => ['area' => 'jobs', 'type' => 'Vaga', 'title' => $title, 'path' => "/vagas/{$record->id}"],
            $record instanceof JobCategory => ['area' => 'jobs', 'type' => 'Categoria de vagas', 'title' => $title, 'path' => '/categorias-de-vagas'],
            $record instanceof Post => ['area' => 'blog', 'type' => 'Notícia', 'title' => $title, 'path' => "/blog/artigos/{$record->id}"],
            $record instanceof PostCategory => ['area' => 'blog', 'type' => 'Categoria de notícias', 'title' => $title, 'path' => '/blog/categorias'],
            $record instanceof PostTag => ['area' => 'blog', 'type' => 'Etiqueta', 'title' => $title, 'path' => '/blog/etiquetas'],
            $record instanceof TeamMember => ['area' => 'team', 'type' => 'Membro da equipa', 'title' => $title, 'path' => '/equipa'],
            default => ['area' => 'settings', 'type' => class_basename($record), 'title' => $title, 'path' => '/'],
        };
    }

    private function titleOf(Model $record): string
    {
        $locale = Localization::defaultLocale();

        foreach (['title', 'name'] as $field) {
            if (in_array($field, $record->getTranslatableAttributes(), true)) {
                return (string) $record->getTranslation($field, $locale);
            }
        }

        return (string) ($record->getAttribute('name') ?? $record->getKey());
    }
}
