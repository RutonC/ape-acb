<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seo.default_title', [
            'pt' => 'APE, Agência Privada de Emprego | Vagas e recrutamento na Beira',
        ]);
        $this->migrator->add('seo.default_description', [
            'pt' => 'Agência Privada de Emprego da Associação Comercial da Beira: vagas de emprego, recrutamento e selecção, centro de avaliação e estágio profissional.',
        ]);
        $this->migrator->add('seo.og_image_id', null);
        $this->migrator->add('seo.ga4_measurement_id', null);
        $this->migrator->add('seo.google_site_verification', null);
    }
};
