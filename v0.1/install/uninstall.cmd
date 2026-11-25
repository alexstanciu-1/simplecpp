@echo off
setlocal
cd /D "%~dp0.."
php "install/uninstall.php"
if errorlevel 1 exit /b 1
pause
