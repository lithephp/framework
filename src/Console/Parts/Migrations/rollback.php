<?php

use Lithe\Database\Manager as DB;

function rollbackMigration($migration, \Symfony\Component\Console\Style\SymfonyStyle $io): bool
{
    $path = $migration['migration'];

    // Verifica se o arquivo de migração existe
    if (!file_exists($path)) {
        $io->writeln(sprintf("\r %s .......................................................................................... Migration not found", basename($path)));
        Migration::delete($migration['id']);
        return false;
    }

    $migrationClass = include $path;

    // Verifica se o arquivo incluído retorna um objeto válido
    if (!is_object($migrationClass)) {
        $io->writeln("<error>Failed to load migration class from '$path'.</error>");
        return false;
    }

    // Executa o método down da classe de migração e exclui o registro da migração
    $migrationClass->down(DB::connection());
    Migration::delete($migration['id']);

    $io->writeln(sprintf("\r %s .......................................................................................... <info>DONE</info>", basename($path)));

    return true;
}