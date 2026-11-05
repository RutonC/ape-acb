<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\JobApplicationRequest;
use App\Mail\FormCopy;
use App\Mail\JobApplicationReceived;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\JobOpening;
use App\Models\Page;
use App\Support\CompanyProfile;
use App\Support\JobLocations;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Portal de vagas: listagem com filtros, detalhe e candidatura com CV privado.
 */
class JobController extends Controller
{
    public function index(Request $request): View
    {
        $query = JobOpening::query()->with('category');

        $this->applyFilters($query, $request);

        $page = Page::published()->where('slug', 'vagas')->first();

        return view('jobs.index', [
            'page' => $page,
            'blocks' => $page?->blocks() ?? [],
            'jobs' => $query->openFirst()->paginate(12)->withQueryString(),
            'categories' => JobCategory::query()->withCount('jobs')->orderBy('sort_order')->get(),
            'locations' => JobLocations::all(),
            'contracts' => JobOpening::query()->get()->map(fn (JobOpening $job) => $job->getTranslation('contract_type', app()->getLocale()))->filter()->unique()->sort()->values(),
            'openCount' => JobOpening::open()->count(),
            'closedCount' => JobOpening::closed()->count(),
            'layout' => $request->query('vista') === 'lista' ? 'list' : 'grid',
        ]);
    }

    public function show(string $slug): View
    {
        $job = JobOpening::with('category')->where('slug', $slug)->firstOrFail();

        return view('jobs.show', [
            'job' => $job,
            'similar' => JobOpening::query()->with('category')
                ->whereKeyNot($job->getKey())
                ->when($job->job_category_id, fn (Builder $query, int $category) => $query->orderByRaw('CASE WHEN job_category_id = ? THEN 0 ELSE 1 END', [$category]))
                ->openFirst()
                ->limit(4)
                ->get(),
            'documentTypes' => JobApplication::DOCUMENT_TYPES,
            'genders' => JobApplication::GENDERS,
        ]);
    }

    public function apply(Request $request): View
    {
        $page = Page::published()->where('slug', 'candidatura')->first();

        return view('jobs.apply', [
            'page' => $page,
            'openings' => JobOpening::open()->openFirst()->get(),
            'selected' => JobOpening::open()->where('slug', is_string($request->query('vaga')) ? $request->query('vaga') : '')->first(),
            'documentTypes' => JobApplication::DOCUMENT_TYPES,
            'genders' => JobApplication::GENDERS,
        ]);
    }

    public function store(JobApplicationRequest $request): RedirectResponse
    {
        $opening = JobOpening::find($request->validated('job_opening_id'));

        // Robôs que preenchem a armadilha recebem a mesma resposta, sem nada ser guardado.
        if (! $request->isSpam()) {
            $file = $request->file('cv');

            $application = JobApplication::create([
                ...$request->safe()->except(['cv', 'consent', 'website', 'send_copy']),
                'cv_path' => $file->storeAs('candidaturas', Str::uuid().'.'.$file->extension(), JobApplication::CV_DISK),
                'cv_original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
                'locale' => app()->getLocale(),
                'status' => ApplicationStatus::New,
            ]);

            $this->notify($application, $request->boolean('send_copy'));
        }

        return redirect()
            ->to(($opening ? $opening->url() : lroute('apply')).'#candidatura')
            ->with('application_sent', true);
    }

    /**
     * @param  Builder<JobOpening>  $query
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        // Só texto: um endereço manipulado (?q[]=x) não pode provocar um erro.
        $text = fn (string $key): string => is_string($value = $request->query($key)) ? trim($value) : '';
        $locale = app()->getLocale();

        if (($search = $text('q')) !== '') {
            // Os campos são JSON: conforme a base de dados, os acentos ficam escritos ou como \uXXXX.
            $term = Str::lower(addcslashes($search, '%_'));
            $forms = array_unique(['%'.$term.'%', '%'.trim((string) json_encode($term), '"').'%']);

            $query->where(function (Builder $query) use ($forms) {
                foreach ($forms as $form) {
                    $query->orWhereRaw('LOWER(title) LIKE ?', [$form])->orWhereRaw('LOWER(summary) LIKE ?', [$form]);
                }
            });
        }

        if (($category = $text('categoria')) !== '') {
            $query->whereHas('category', fn (Builder $query) => $query->where('slug', $category));
        }

        if (($location = $text('local')) !== '') {
            $query->where("location->{$locale}", $location);
        }

        if (($contract = $text('tipo')) !== '') {
            $query->where("contract_type->{$locale}", $contract);
        }

        match ($text('estado')) {
            'abertas' => $query->open(),
            'encerradas' => $query->closed(),
            default => null,
        };
    }

    private function notify(JobApplication $application, bool $sendCopy): void
    {
        $recipient = app(CompanyProfile::class)->toArray()['forms_recipient_email'];

        try {
            if (filled($recipient)) {
                Mail::to($recipient)->send(new JobApplicationReceived($application));
            }

            if ($sendCopy) {
                Mail::to($application->email)->send(new FormCopy('candidatura'));
            }
        } catch (Throwable $exception) {
            // A candidatura já está guardada no painel; uma falha de e-mail não deve perdê-la.
            Log::error('Falha ao enviar notificação de candidatura', ['id' => $application->id, 'erro' => $exception->getMessage()]);
        }
    }
}
