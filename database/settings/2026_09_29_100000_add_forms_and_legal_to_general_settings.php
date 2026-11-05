<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.forms_recipient_email', 'recrutamento.ape@acb.co.mz');
        $this->migrator->add('general.nuit', null);
        $this->migrator->add('general.commercial_registry', null);
    }

    public function down(): void
    {
        $this->migrator->delete('general.forms_recipient_email');
        $this->migrator->delete('general.nuit');
        $this->migrator->delete('general.commercial_registry');
    }
};
