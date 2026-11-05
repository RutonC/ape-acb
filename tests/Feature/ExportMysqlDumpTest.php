<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExportMysqlDumpTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_mysql_schema_and_data_to_a_sql_file(): void
    {
        Post::factory()->create(['title' => ['pt' => "Artigo com 'aspas', acentos (ção) e \\ barra"]]);
        $output = storage_path('framework/testing/export-'.uniqid().'.sql');

        $this->artisan('db:export-mysql', ['--output' => $output, '--database' => 'acb_teste'])
            ->assertSuccessful();

        $sql = file_get_contents($output);
        unlink($output);

        $this->assertStringContainsString('CREATE DATABASE IF NOT EXISTS `acb_teste`', $sql);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS = 0;', $sql);

        // Uma tabela MySQL por cada tabela da base actual.
        $tables = collect(DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))->pluck('name');
        foreach ($tables as $table) {
            $this->assertStringContainsString("create table if not exists `{$table}`", $sql, $table);
        }

        // Tipos da gramática MySQL e dados com escape correcto.
        $this->assertStringContainsString('auto_increment primary key', $sql);
        $this->assertStringContainsString('`title` json not null', $sql);
        $this->assertStringContainsString('insert into `posts`', $sql);
        $this->assertStringContainsString("\\'aspas\\'", $sql);
        $this->assertStringContainsString('\\\\\\\\ barra', $sql);
        $this->assertStringContainsString('insert into `migrations`', $sql);

        // Tabelas temporárias só com estrutura.
        $this->assertStringNotContainsString('insert into `sessions`', $sql);
        $this->assertDoesNotMatchRegularExpression('/^select /mi', $sql);
    }
}
