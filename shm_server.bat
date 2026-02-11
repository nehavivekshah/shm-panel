@echo off
echo SHM Panel Local Server
echo ======================
echo.
echo 1. Ensure you have added the following line to C:\Windows\System32\drivers\etc\hosts:
echo    127.0.0.1 vivzon.cloud
echo.
echo 2. Access the panel at: http://vivzon.cloud:8000/install.php
echo.
echo Starting PHP Server on port 8000...
php -S 0.0.0.0:8000
pause
