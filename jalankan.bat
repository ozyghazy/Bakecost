@echo off
title BAKECOST - Web App Runner
color 0e
echo ====================================================
echo        BAKECOST - Menjalankan Aplikasi Web
echo ====================================================
echo.
echo Pastikan MySQL di XAMPP Control Panel sudah di-klik START!
echo.
pause

echo.
echo Menjalankan server PHP lokal di http://localhost:8000/bakecost/ ...
echo (Jangan tutup jendela command prompt ini selama menggunakan aplikasi)
echo.

start http://localhost:8000/bakecost/
"C:\xampp\php\php.exe" -S localhost:8000 -t "%~dp0.."

