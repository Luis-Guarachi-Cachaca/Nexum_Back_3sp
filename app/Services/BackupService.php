<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BackupService
{
    /**
     * Genera un archivo SQL con el backup de la base de datos.
     * Intenta pg_dump primero. Si no está disponible o exec() está
     * deshabilitado, cae automáticamente al generador PHP puro.
     *
     * @return array{path: string, filename: string, method: string}
     */
    public function generate(): array
    {
        $filename = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $dir      = storage_path('app/backups');
        $path     = "{$dir}/{$filename}";

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $this->generateWithPgDump($path);
            return ['path' => $path, 'filename' => $filename, 'method' => 'pg_dump'];
        } catch (\Throwable) {
            $this->generateWithPhp($path);
            return ['path' => $path, 'filename' => $filename, 'method' => 'php'];
        }
    }

    // -------------------------------------------------------------------------
    // Método 1: pg_dump (profesional, completo)
    // -------------------------------------------------------------------------

    private function generateWithPgDump(string $path): void
    {
        if (! function_exists('exec')) {
            throw new \RuntimeException('exec() no está disponible.');
        }

        $cfg = config('database.connections.pgsql');

        putenv("PGPASSWORD={$cfg['password']}");

        $cmd = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s -F p -f %s 2>&1',
            escapeshellarg($cfg['host']),
            escapeshellarg($cfg['port'] ?? '5432'),
            escapeshellarg($cfg['username']),
            escapeshellarg($cfg['database']),
            escapeshellarg($path)
        );

        exec($cmd, $output, $exitCode);

        putenv('PGPASSWORD=');

        if ($exitCode !== 0 || ! file_exists($path) || filesize($path) === 0) {
            throw new \RuntimeException('pg_dump falló: ' . implode(' ', $output));
        }
    }

    // -------------------------------------------------------------------------
    // Método 2: PHP puro (compatible con cualquier servidor)
    // Genera CREATE TABLE + INSERT INTO por cada tabla.
    // -------------------------------------------------------------------------

    private function generateWithPhp(string $path): void
    {
        $pdo    = DB::getPdo();
        $output = [];

        $output[] = "-- Backup generado por Nexum";
        $output[] = "-- Fecha: " . now()->toDateTimeString();
        $output[] = "-- Método: PHP puro";
        $output[] = "";
        $output[] = "SET client_encoding = 'UTF8';";
        $output[] = "SET standard_conforming_strings = on;";
        $output[] = "";

        // Obtener todas las tablas del schema public
        $tables = $pdo->query(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $output[] = "-- ---------------------------------------------------";
            $output[] = "-- Tabla: {$table}";
            $output[] = "-- ---------------------------------------------------";

            // Estructura de la tabla
            $output[] = $this->getCreateTable($pdo, $table);
            $output[] = "";

            // Datos de la tabla
            $rows = $pdo->query("SELECT * FROM \"{$table}\"")->fetchAll(\PDO::FETCH_ASSOC);

            if (! empty($rows)) {
                $columns = '"' . implode('", "', array_keys($rows[0])) . '"';

                foreach ($rows as $row) {
                    $values = array_map(function ($value) use ($pdo) {
                        if ($value === null) return 'NULL';
                        if (is_numeric($value)) return $value;
                        return $pdo->quote($value);
                    }, $row);

                    $output[] = "INSERT INTO \"{$table}\" ({$columns}) VALUES (" . implode(', ', $values) . ");";
                }
            }

            $output[] = "";
        }

        file_put_contents($path, implode("\n", $output));

        if (! file_exists($path) || filesize($path) === 0) {
            throw new \RuntimeException('No se pudo generar el backup con PHP.');
        }
    }

    /**
     * Genera el CREATE TABLE para una tabla dada leyendo su estructura desde PostgreSQL.
     */
    private function getCreateTable(\PDO $pdo, string $table): string
    {
        $columns = $pdo->query("
            SELECT
                column_name,
                data_type,
                character_maximum_length,
                is_nullable,
                column_default
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = " . $pdo->quote($table) . "
            ORDER BY ordinal_position
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $defs = [];

        foreach ($columns as $col) {
            $type = strtoupper($col['data_type']);

            if ($col['character_maximum_length']) {
                $type .= "({$col['character_maximum_length']})";
            }

            $def = "\"{$col['column_name']}\" {$type}";

            if ($col['column_default']) {
                $def .= " DEFAULT {$col['column_default']}";
            }

            if ($col['is_nullable'] === 'NO') {
                $def .= ' NOT NULL';
            }

            $defs[] = "    {$def}";
        }

        return "CREATE TABLE IF NOT EXISTS \"{$table}\" (\n" . implode(",\n", $defs) . "\n);";
    }
}