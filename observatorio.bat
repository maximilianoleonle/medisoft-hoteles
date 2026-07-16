@echo off
REM Observatorio del Copiloto — doble clic para ver el reporte.
REM Tambien acepta argumentos:  observatorio.bat atendida ab12cd34ef "que se le enseno"
REM                             observatorio.bat ignorar  ab12cd34ef "por que es ruido"
REM                             observatorio.bat --dias=90
docker exec medisoft_hoteles_app php /var/www/html/tools/observatorio_copiloto.php %*
echo.
pause
