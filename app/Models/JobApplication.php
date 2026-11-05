<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Candidatura. Tem dados pessoais: o CV fica no disco privado ("local"), fora da
 * pasta pública, e é apagado com a candidatura; o número do documento fica cifrado.
 *
 * @property ApplicationStatus $status
 * @property Carbon $birth_date
 */
#[Fillable(['job_opening_id', 'name', 'birth_date', 'gender', 'document_type', 'document_number', 'qualifications', 'experience_years', 'address', 'email', 'phone', 'phone_alt', 'message', 'cv_path', 'cv_original_name', 'locale', 'status', 'notes'])]
#[Hidden(['cv_path', 'document_number'])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    public const CV_DISK = 'local';

    /** CV: PDF ou Word, até 20 MB, como no site antigo. */
    public const CV_MAX_KB = 20480;

    public const GENDERS = ['Masculino', 'Feminino'];

    public const DOCUMENT_TYPES = ['BI', 'Passaporte', 'Carta de Condução', 'DIRE'];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'birth_date' => 'date',
            'experience_years' => 'integer',
            'document_number' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (JobApplication $application) {
            Storage::disk(self::CV_DISK)->delete($application->cv_path);
        });
    }

    /**
     * @return BelongsTo<JobOpening, $this>
     */
    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function positionLabel(): string
    {
        return $this->jobOpening?->getTranslation('title', config('site.default_locale')) ?? 'Candidatura espontânea';
    }
}
