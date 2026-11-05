<?php

namespace App\Models;

use App\Enums\InboxStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Inscrição de empresa ("Inscrever minha empresa"): a APE contacta depois.
 *
 * @property InboxStatus $status
 */
#[Fillable(['company_name', 'name', 'phone', 'email', 'status', 'notes'])]
class CompanyRegistration extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => InboxStatus::class];
    }
}
