@echo off
REM Centinela de errores — doble clic para ver el reporte.
REM Tambien acepta argumentos:  centinela.bat resolver ab12cd34ef "nota del fix"
REM                             centinela.bat ignorar  ab12cd34ef "por que es ruido"
REM                             centinela.bat --dias=30
docker exec medisoft_hoteles_app php /var/www/html/tools/centinela.php %*
echo.
pause
