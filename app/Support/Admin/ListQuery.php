<?php

namespace App\Support\Admin;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Listagens do painel (tabela genérica do React): pesquisa, filtros,
 * ordenação e paginação lidos da query string, sempre por listas
 * permitidas.
 *
 *   ?search=texto&sort=-created_at&filter[role]=2&page=2&per_page=30
 *
 * Campos traduzíveis (JSON) pesquisam-se com "campo->pt".
 */
class ListQuery
{
    /** @var array<int, string> */
    private array $searchable = [];

    /** @var array<int, string> */
    private array $sortable = [];

    private ?string $defaultSort = null;

    /** @var array<string, Closure(Builder, string): void> */
    private array $filters = [];

    public function __construct(private Builder $query, private Request $request) {}

    public static function for(Builder $query, Request $request): static
    {
        return new static($query, $request);
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function search(array $columns): static
    {
        $this->searchable = $columns;

        return $this;
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function sort(array $columns, ?string $default = null): static
    {
        $this->sortable = $columns;
        $this->defaultSort = $default;

        return $this;
    }

    /**
     * @param  Closure(Builder, string): void  $apply
     */
    public function filter(string $name, Closure $apply): static
    {
        $this->filters[$name] = $apply;

        return $this;
    }

    public function paginate(int $default = 15): LengthAwarePaginator
    {
        $this->applySearch();
        $this->applyFilters();
        $this->applySort();

        return $this->query
            ->paginate(min(max($this->request->integer('per_page', $default), 5), 100))
            ->withQueryString();
    }

    private function applySearch(): void
    {
        $term = trim((string) $this->request->query('search', ''));

        if ($term === '' || $this->searchable === []) {
            return;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        $this->query->where(function (Builder $query) use ($like) {
            foreach ($this->searchable as $column) {
                $query->orWhere($column, 'like', $like);
            }
        });
    }

    private function applyFilters(): void
    {
        $values = (array) $this->request->query('filter', []);

        foreach ($this->filters as $name => $apply) {
            $value = $values[$name] ?? null;

            if (is_string($value) && $value !== '') {
                $apply($this->query, $value);
            }
        }
    }

    private function applySort(): void
    {
        $sort = (string) $this->request->query('sort', '') ?: $this->defaultSort;

        if (! $sort) {
            return;
        }

        $column = ltrim($sort, '-');

        if (! in_array($column, $this->sortable, true)) {
            $sort = $this->defaultSort;
            $column = $sort ? ltrim($sort, '-') : null;
        }

        if ($column) {
            $this->query->orderBy($column, str_starts_with((string) $sort, '-') ? 'desc' : 'asc')->orderBy($this->query->getModel()->getKeyName());
        }
    }
}
