<?php
// Extraire les stubs du PHAR phpUnit
$pharPath = './tools/phpUnit.phar';

if (!file_exists($pharPath)) {
    die("PHAR not found at $pharPath\n");
}

// Créer le dossier s'il n'existe pas
@mkdir('./vendor-stubs', 0755, true);

// Extraire le PHAR
$phar = new Phar($pharPath);
$phar->extractTo('./vendor-stubs');

echo "Stubs extracted to ./vendor-stubs\n";