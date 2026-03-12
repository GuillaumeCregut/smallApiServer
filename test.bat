@echo off
if "%1"=="" (echo "test tous les fichiers") else (echo "test fichier %1")
php ./tools/phpUnit.phar %1 -c ./tools/phpUnit.xml --display-warnings --display-all-issues --bootstrap ./tests/bootstrap.php