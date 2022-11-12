@echo off
start firefox http://localhost:8000
cd C:\Program Files\hookahcenter
php artisan serve
Exit