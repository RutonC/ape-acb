<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Valores iniciais recolhidos em ape.acb.co.mz. A morada escrita e o horário não
     * constam do site antigo e preenchem-se em Definições > Site e contactos.
     */
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'APE, Agência Privada de Emprego');
        $this->migrator->add('general.tagline', [
            'pt' => 'Crescendo juntos!',
        ]);
        $this->migrator->add('general.footer_about', [
            'pt' => 'Empresa privada moçambicana, criada em 2017 como parte integrante da Associação Comercial da Beira, com sede na cidade da Beira.',
        ]);
        $this->migrator->add('general.logo_id', null);
        $this->migrator->add('general.logo_white_id', null);
        $this->migrator->add('general.address', 'Beira, Sofala, Moçambique');
        $this->migrator->add('general.address_short', 'Beira, Sofala, Moçambique');
        $this->migrator->add('general.phones', ['+258 23 326 246', '+258 84 373 2764']);
        $this->migrator->add('general.email', 'info.ape@acb.co.mz');
        $this->migrator->add('general.whatsapp', null);
        $this->migrator->add('general.hours', ['pt' => '']);
        $this->migrator->add('general.map_embed_url', 'https://maps.google.com/maps?q=-19.83418732566112,34.836112262128154&t=m&z=17&output=embed');
        $this->migrator->add('general.facebook', null);
        $this->migrator->add('general.linkedin', null);
        $this->migrator->add('general.instagram', null);
    }
};
