@echo off
setlocal

echo Prism++ installer for Windows
echo.

where winget >nul 2>nul
if errorlevel 1 (
	echo ERROR: winget is required.
	goto :end_fail
)

echo Installing dependencies...
call :ensure_winget_package Microsoft.VCRedist.2015+.x64 "Microsoft Visual C++ Redistributable" required
if errorlevel 1 goto :end_fail

call :ensure_git_present
if errorlevel 1 goto :end_fail

call :ensure_winget_package Ninja-build.Ninja Ninja required
if errorlevel 1 goto :end_fail

call :ensure_winget_package PHP.PHP.8.5 "PHP 8.5" required
if errorlevel 1 goto :end_fail

echo.
echo Verifying PHP 8.4+...
php -r "exit(version_compare(PHP_VERSION, '8.4.0', '>=') ? 0 : 1);"
if errorlevel 1 (
	echo ERROR: PHP 8.4 or newer is required.
	goto :end_fail
)

echo.
echo Running repo-based user-local installer...
cd /D "%~dp0.."
php "install/install.php"
if errorlevel 1 goto :end_fail

echo.
echo Verifying php-ast in a new PHP process...
php -r "exit(extension_loaded('ast') ? 0 : 1);"
if errorlevel 1 (
	echo ERROR: php-ast is not enabled in a fresh PHP process.
	echo NOTE: unrelated PHP startup warnings such as missing php_yaml_*.dll do not by themselves mean php-ast failed.
	goto :end_fail
)

echo.
echo Installation finished.
echo Open a new terminal so PATH changes are visible.
goto :end_ok

:ensure_git_present
where git >nul 2>nul
if errorlevel 1 (
	echo Git was not found on PATH. Installing Git...
	call :ensure_winget_package Git.Git Git required
	if errorlevel 1 exit /b 1
	exit /b 0
)

echo Git already found on PATH. Skipping Git install/update.
exit /b 0

:ensure_winget_package
set "PACKAGE_ID=%~1"
set "PACKAGE_LABEL=%~2"
set "PACKAGE_MODE=%~3"

echo Ensuring %PACKAGE_LABEL%...
call :is_winget_package_installed %PACKAGE_ID%
if errorlevel 0 (
	echo %PACKAGE_LABEL% already installed. Skipping install/update.
	exit /b 0
)

winget install --id %PACKAGE_ID% --exact --accept-package-agreements --accept-source-agreements
if errorlevel 0 exit /b 0

call :is_winget_package_installed %PACKAGE_ID%
if errorlevel 0 (
	echo %PACKAGE_LABEL% is installed after winget reported a non-zero exit code. Continuing.
	exit /b 0
)

if /I "%PACKAGE_MODE%"=="optional" (
	echo WARNING: Unable to install %PACKAGE_LABEL% with winget.
	exit /b 0
)

echo ERROR: Failed to install %PACKAGE_LABEL% with winget.
exit /b 1

:is_winget_package_installed
set "PACKAGE_ID=%~1"
winget list --id %PACKAGE_ID% --exact --accept-source-agreements >nul 2>nul
exit /b %ERRORLEVEL%

:end_fail
echo.
echo Installation failed.
pause
exit /b 1

:end_ok
pause
exit /b 0
