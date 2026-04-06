\
@echo off
setlocal

echo Simple C++ installer for Windows 11
echo.

where winget >nul 2>nul
if errorlevel 1 (
	echo ERROR: winget is required on Windows 11.
	goto :end_fail
)

echo Installing dependencies...
winget install --accept-package-agreements --accept-source-agreements Microsoft.VCRedist.2015+.x64
winget install --accept-package-agreements --accept-source-agreements Git.Git
winget install --accept-package-agreements --accept-source-agreements PHP.PHP.8.5

echo.
echo Verifying PHP 8.4+...
php -r "exit(version_compare(PHP_VERSION, '8.4.0', '>=') ? 0 : 1);"
if errorlevel 1 (
	echo ERROR: PHP 8.4 or newer is required.
	goto :end_fail
)

echo.
echo Creating user launcher directory...
if not exist "C:\Users\%USERNAME%\.d-app" mkdir "C:\Users\%USERNAME%\.d-app"

echo Updating PATH...
setx PATH "%PATH%;C:\Users\%USERNAME%\.d-app" >nul

echo.
echo Running project installer...
cd /D "%~dp0.."
php "install/install.php"
if errorlevel 1 goto :end_fail

echo.
echo Verifying php-ast in a new PHP process...
php -m | findstr /I /R "^ast$" >nul
if errorlevel 1 (
	echo ERROR: php-ast is not enabled.
	goto :end_fail
)

echo.
echo Installation finished.
echo Open a new terminal so PATH changes are visible.
goto :end_ok

:end_fail
echo.
echo Installation failed.
pause
exit /b 1

:end_ok
pause
exit /b 0
