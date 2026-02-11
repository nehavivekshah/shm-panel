@echo off
echo Starting PHP-CGI for Nginx...
echo.
echo Please ensure you have configured nginx.conf to include 'nginx_shm.conf'
echo or manually added the server block.
echo.
echo Listening on 127.0.0.1:9000...
php-cgi.exe -b 127.0.0.1:9000
pause
