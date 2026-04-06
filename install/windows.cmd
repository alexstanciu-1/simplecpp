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
echo Creating user bin directory...
if not exist "C:\Users\%USERNAME%\.d-app" mkdir "C:\Users\%USERNAME%\.d-app"

echo Updating PATH...
setx PATH "%PATH%;C:\Users\%USERNAME%\.d-app" >nul

echo.
echo Running project installer...
cd /D "%~dp0.."
php "install.php"

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
