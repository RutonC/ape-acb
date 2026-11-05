<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Exporta a base de dados actual (SQLite) para um ficheiro .sql que pode
 * ser importado directamente em MySQL/MariaDB (ex.: MySQL Workbench ou
 * phpMyAdmin): estrutura gerada pelas migrations com a gramática MySQL do
 * Laravel (sem precisar de servidor MySQL) + todos os dados.
 */
class ExportMysqlDump extends Command
{
    protected $signature = 'db:export-mysql
        {--output= : Caminho do ficheiro .sql (por omissão storage/app/private/backups)}
        {--database=ape : Nome da base de dados MySQL a criar/usar no ficheiro}';

    protected $description = 'Exporta a base de dados (estrutura e dados) para um ficheiro SQL compatível com MySQL';

    /** Tabelas temporárias: só a estrutura é exportada. */
    private const SKIP_DATA = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs'];

    private const BATCH = 100;

    public function handle(): int
    {
        $source = DB::connection();

        if ($source->getDriverName() !== 'sqlite') {
            $this->error('A ligação actual não é SQLite; use o mysqldump do próprio servidor.');

            return self::FAILURE;
        }

        $database = $this->option('database');
        $output = $this->option('output') ?: storage_path('app/private/backups/ape-mysql-'.now()->format('Ymd-His').'.sql');
        File::ensureDirectoryExists(dirname($output));

        $schema = $this->mysqlSchema();
        $tables = $this->tables($source);

        $handle = fopen($output, 'w');
        fwrite($handle, $this->header($database));

        foreach ($schema as $statement) {
            fwrite($handle, $statement.";\n\n");
        }

        $rows = 0;

        foreach ($tables as $table) {
            if (in_array($table, self::SKIP_DATA, true)) {
                continue;
            }

            $rows += $this->writeData($handle, $source, $table);
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($handle);

        $this->components->info(sprintf('%d tabelas, %d registos exportados.', count($tables), $rows));
        $this->line("  Ficheiro: <comment>{$output}</comment>");
        $this->line('  Lembrete: copie também storage/app/public (imagens) e storage/app/private (CV) e use o mesmo APP_KEY.');

        return self::SUCCESS;
    }

    /**
     * CREATE TABLE/INDEX/FOREIGN KEY de todas as migrations, na gramática MySQL.
     * A ligação corre em modo "pretend": gera o SQL sem contactar nenhum servidor.
     *
     * @return array<int, string>
     */
    private function mysqlSchema(): array
    {
        $config = [...config('database.connections.mysql'), 'database' => $this->option('database'), 'name' => 'mysql_export'];
        config(['database.connections.mysql_export' => $config]);

        // Ligação MySQL 8 "offline": a gramática consulta a versão do servidor
        // (isMaria) em alguns tipos de coluna; aqui responde sem ligar a nada.
        DB::extend('mysql_export', fn () => new class(fn () => throw new \RuntimeException('Sem servidor MySQL: só modo pretend.'), $config['database'], '', $config) extends MySqlConnection
        {
            public function isMaria(): bool
            {
                return false;
            }

            public function getServerVersion(): string
            {
                return '8.0.36';
            }
        });

        /** @var Connection $mysql */
        $mysql = DB::connection('mysql_export');
        $previous = DB::getDefaultConnection();
        DB::setDefaultConnection('mysql_export');

        $statements = [];

        try {
            $queries = $mysql->pretend(function () {
                foreach ($this->migrationFiles() as $file) {
                    $migration = require $file;
                    $migration->up();
                }
            });

            foreach ($queries as $query) {
                $statements[] = $this->bindings($query['query'], $query['bindings']);
            }
        } finally {
            DB::setDefaultConnection($previous);
        }

        // A tabela "migrations" é criada pelo artisan migrate, não por uma migration.
        array_unshift($statements, 'create table `migrations` (`id` int unsigned not null auto_increment primary key, `migration` varchar(255) not null, `batch` int not null) default character set utf8mb4 collate \'utf8mb4_unicode_ci\'');

        return array_map(fn (string $sql) => preg_replace('/^create table /i', 'create table if not exists ', $sql), $statements);
    }

    /**
     * @return array<int, string>
     */
    private function migrationFiles(): array
    {
        $files = File::glob(database_path('migrations/*.php'));
        sort($files);

        return $files;
    }

    /**
     * @param  array<int, mixed>  $bindings
     */
    private function bindings(string $sql, array $bindings): string
    {
        foreach ($bindings as $binding) {
            $sql = preg_replace('/\?/', $this->value($binding), $sql, 1);
        }

        return $sql;
    }

    /**
     * @return array<int, string>
     */
    private function tables(Connection $source): array
    {
        return collect($source->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name"))
            ->pluck('name')
            ->all();
    }

    /**
     * @param  resource  $handle
     */
    private function writeData($handle, Connection $source, string $table): int
    {
        $count = 0;
        $columns = null;
        $batch = [];

        foreach ($source->table($table)->cursor() as $row) {
            $row = (array) $row;
            $columns ??= implode(', ', array_map(fn (string $column) => "`{$column}`", array_keys($row)));
            $batch[] = '('.implode(', ', array_map(fn ($value) => $this->value($value), $row)).')';
            $count++;

            if (count($batch) === self::BATCH) {
                fwrite($handle, "insert into `{$table}` ({$columns}) values\n".implode(",\n", $batch).";\n\n");
                $batch = [];
            }
        }

        if ($batch !== []) {
            fwrite($handle, "insert into `{$table}` ({$columns}) values\n".implode(",\n", $batch).";\n\n");
        }

        return $count;
    }

    /**
     * Valor literal em SQL MySQL (escape equivalente a mysql_real_escape_string).
     */
    private function value(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            default => "'".strtr((string) $value, [
                '\\' => '\\\\',
                "\0" => '\\0',
                "\n" => '\\n',
                "\r" => '\\r',
                "'" => "\\'",
                '"' => '\\"',
                "\x1a" => '\\Z',
            ])."'",
        };
    }

    private function header(string $database): string
    {
        $date = now()->toDateTimeString();

        return <<<SQL
-- Associação Comercial da Beira: exportação para MySQL ({$date})
-- Gerado por: php artisan db:export-mysql
-- Importar no MySQL 8 / MariaDB 10.5+ (Workbench: Server > Data Import, ou mysql < ficheiro.sql)

CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `{$database}`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


SQL;
    }
}
