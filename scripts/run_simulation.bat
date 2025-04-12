@echo off
:loop
php -f "%~dp0data_simulator.php"
timeout /t 10 /nobreak
goto loop 