@echo off
rem ======================================================================
rem  MS Football Academy - upload the project to the hosting (FTP via WinSCP)
rem
rem    deploy.bat            upload the changed files, then clear Laravel's caches
rem    deploy.bat preview    only SHOW what would be uploaded (changes nothing)
rem    deploy.bat check      test the login and the server folder (changes nothing)
rem    deploy.bat deploy /y  upload without the "are you sure" question
rem
rem  First time: install WinSCP (https://winscp.net, free) and copy
rem  deploy\config.example.bat to deploy\config.bat, then fill it in.
rem
rem  Safe by design:
rem   - only NEW or CHANGED files are uploaded; nothing on the server is deleted
rem     except Laravel's own cache files (they rebuild themselves)
rem   - never uploaded: .env, storage\ (logs, sessions), public\uploads\ (photos),
rem     .git, node_modules, tests, deploy\, database\sql\, local caches
rem   - database changes are NOT done here: run the new database\sql\*.sql file
rem     in phpMyAdmin first when an update comes with one
rem ======================================================================
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
set "ROOT=%CD%"
set "MODE=%~1"
if "%MODE%"=="" set "MODE=deploy"
set "ASK=1"
if /i "%~2"=="/y" set "ASK=0"

if /i "%MODE%"=="deploy" goto :mode_ok
if /i "%MODE%"=="preview" goto :mode_ok
if /i "%MODE%"=="check" goto :mode_ok
echo.
echo  Unknown option "%MODE%". Use: deploy.bat  ^|  deploy.bat preview  ^|  deploy.bat check
goto :fail
:mode_ok

echo.
echo  ==============================================================
echo   MS Football Academy - %MODE%
echo  ==============================================================
echo.

rem ---------------- settings ----------------
if not exist "%ROOT%\artisan" (
    echo  This file must stay in the project folder, next to "artisan".
    goto :fail
)
if not exist "%ROOT%\deploy\config.bat" (
    echo  Settings not found.
    echo  Copy  deploy\config.example.bat  to  deploy\config.bat  and fill it in.
    goto :fail
)
set "FTP_SITE=" & set "FTP_HOST=" & set "FTP_PORT=21" & set "FTP_USER=" & set "FTP_PASS=" & set "FTP_TLS=1"
set "REMOTE_APP=" & set "REMOTE_PUBLIC=" & set "BUILD_ASSETS=0" & set "SYNC_VENDOR=0" & set "WINSCP="
call "%ROOT%\deploy\config.bat"

if not defined WINSCP set "WINSCP=%ProgramFiles(x86)%\WinSCP\WinSCP.com"
if not exist "%WINSCP%" if exist "%ProgramFiles%\WinSCP\WinSCP.com" set "WINSCP=%ProgramFiles%\WinSCP\WinSCP.com"
if not exist "%WINSCP%" if exist "%LOCALAPPDATA%\Programs\WinSCP\WinSCP.com" set "WINSCP=%LOCALAPPDATA%\Programs\WinSCP\WinSCP.com"
if not exist "%WINSCP%" (
    echo  WinSCP is not installed. Download it free from https://winscp.net and install it,
    echo  or set WINSCP in deploy\config.bat to the full path of WinSCP.com.
    goto :fail
)
if not defined REMOTE_APP (
    echo  REMOTE_APP is empty in deploy\config.bat - the project folder on the server.
    goto :fail
)
if not defined FTP_SITE if not defined FTP_HOST (
    echo  No login in deploy\config.bat: fill FTP_SITE, or FTP_HOST + FTP_USER + FTP_PASS.
    goto :fail
)
if not "%ROOT: =%"=="%ROOT%" (
    echo  The project folder path contains a space: "%ROOT%"
    echo  Move the project to a folder without spaces, for example F:\Nexas\ms-academy.
    goto :fail
)
rem no trailing slash on server paths ("/" alone stays: the FTP account opens straight in the project)
if not "%REMOTE_APP%"=="/" if "%REMOTE_APP:~-1%"=="/" set "REMOTE_APP=%REMOTE_APP:~0,-1%"
set "RA=%REMOTE_APP%"
if "%REMOTE_APP%"=="/" set "RA="
rem (an empty value cannot be cut, so skip it - batch expands %%...%% before "if defined")
if not defined REMOTE_PUBLIC goto :public_path_ok
if "%REMOTE_PUBLIC:~-1%"=="/" set "REMOTE_PUBLIC=%REMOTE_PUBLIC:~0,-1%"
:public_path_ok

if defined FTP_SITE (echo  Login     : WinSCP site "%FTP_SITE%") else (echo  Login     : %FTP_USER% @ %FTP_HOST%:%FTP_PORT%)
echo  Project   : %ROOT%
echo  Server    : %REMOTE_APP%
if defined REMOTE_PUBLIC echo  Public    : %REMOTE_PUBLIC%
if "%SYNC_VENDOR%"=="1" (echo  vendor    : included ^(SYNC_VENDOR=1 - slow^)) else (echo  vendor    : skipped ^(set SYNC_VENDOR=1 after composer changes^))
echo.

rem ---------------- before uploading ----------------
if /i not "%MODE%"=="deploy" goto :build_done
if "%ASK%"=="1" (
    choice /c YN /n /m "  Upload the changed files to the LIVE site now? [Y/N] "
    if errorlevel 2 (
        echo.
        echo  Cancelled. Nothing was uploaded.
        goto :end
    )
)
if "%BUILD_ASSETS%"=="1" (
    echo.
    echo  Building CSS/JS ^(npm run build^)...
    call npm run build
    if errorlevel 1 (
        echo  The build failed. Nothing was uploaded.
        goto :fail
    )
)
:build_done

rem ---------------- WinSCP script (temporary file, deleted afterwards) ----------------
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmmss"') do set "STAMP=%%i"
if not defined STAMP set "STAMP=%RANDOM%"
set "LOGDIR=%ROOT%\deploy\logs"
if not exist "%LOGDIR%" mkdir "%LOGDIR%"
set "LOG=%LOGDIR%\%STAMP%-%MODE%.log"
set "SCRIPT=%TEMP%\msa-deploy-%RANDOM%%RANDOM%.txt"

rem never uploaded (full local paths, so nothing inside vendor\ is caught by mistake)
set "SKIP=%ROOT%\.git\; %ROOT%\node_modules\; %ROOT%\storage\; %ROOT%\tests\; %ROOT%\deploy\; %ROOT%\.claude\; %ROOT%\.vscode\; %ROOT%\.idea\; %ROOT%\.fleet\; %ROOT%\database\sql\; %ROOT%\public\uploads\; %ROOT%\public\storage\; %ROOT%\public\hot; %ROOT%\bootstrap\cache\*.php; %ROOT%\.env; %ROOT%\.env.*; %ROOT%\database\database.sqlite; %ROOT%\deploy.bat; %ROOT%\CLAUDE.md; %ROOT%\*.log; %ROOT%\.phpunit.result.cache"
rem vendor\ (thousands of library files) is slow to compare over FTP and only changes when
rem composer packages change - set SYNC_VENDOR=1 in deploy\config.bat for that one upload
if not "%SYNC_VENDOR%"=="1" set "SKIP=%SKIP%; %ROOT%\vendor\"
if defined REMOTE_PUBLIC set "SKIP=%SKIP%; %ROOT%\public\"
set "SKIP_PUBLIC=%ROOT%\public\index.php; %ROOT%\public\.htaccess; %ROOT%\public\uploads\; %ROOT%\public\storage\; %ROOT%\public\hot"

> "%SCRIPT%" echo option batch abort
>> "%SCRIPT%" echo option confirm off
if defined FTP_SITE (
    >> "%SCRIPT%" echo open "%FTP_SITE%"
) else if "%FTP_TLS%"=="1" (
    >> "%SCRIPT%" echo open ftp://%FTP_HOST%:%FTP_PORT%/ -username="%FTP_USER%" -password="%FTP_PASS%" -passive=on -explicit -certificate="*"
) else (
    >> "%SCRIPT%" echo open ftp://%FTP_HOST%:%FTP_PORT%/ -username="%FTP_USER%" -password="%FTP_PASS%" -passive=on
)
rem stop straight away when the folder is not the Laravel project
>> "%SCRIPT%" echo cd "%REMOTE_APP%"
>> "%SCRIPT%" echo stat artisan

if /i "%MODE%"=="check" (
    >> "%SCRIPT%" echo pwd
    >> "%SCRIPT%" echo ls
    >> "%SCRIPT%" echo option batch continue
    >> "%SCRIPT%" echo stat .env
    if not defined REMOTE_PUBLIC >> "%SCRIPT%" echo stat public/index.php
    if defined REMOTE_PUBLIC >> "%SCRIPT%" echo stat "%REMOTE_PUBLIC%/index.php"
)
if /i "%MODE%"=="preview" (
    >> "%SCRIPT%" echo synchronize remote -preview -criteria=time -filemask="|%SKIP%" "%ROOT%" "%REMOTE_APP%"
    if defined REMOTE_PUBLIC >> "%SCRIPT%" echo synchronize remote -preview -criteria=time -filemask="|%SKIP_PUBLIC%" "%ROOT%\public" "%REMOTE_PUBLIC%"
)
if /i "%MODE%"=="deploy" (
    >> "%SCRIPT%" echo synchronize remote -transfer=binary -criteria=time -filemask="|%SKIP%" "%ROOT%" "%REMOTE_APP%"
    if defined REMOTE_PUBLIC >> "%SCRIPT%" echo synchronize remote -transfer=binary -criteria=time -filemask="|%SKIP_PUBLIC%" "%ROOT%\public" "%REMOTE_PUBLIC%"
    rem clear Laravel's caches - same as view:clear / config:clear / route:clear; they rebuild by themselves
    >> "%SCRIPT%" echo option batch continue
    >> "%SCRIPT%" echo rm "%RA%/bootstrap/cache/*.php"
    >> "%SCRIPT%" echo rm "%RA%/storage/framework/views/*.php"
)
>> "%SCRIPT%" echo exit

rem ---------------- run ----------------
echo  Connecting...
echo.
call "%WINSCP%" /script="%SCRIPT%" /log="%LOG%"
set "RC=%ERRORLEVEL%"
del /q "%SCRIPT%" >nul 2>&1
echo.

if not "%RC%"=="0" (
    echo  ------------------------------------------------------------
    echo   FAILED. Nothing more was done. Details are in:
    echo   %LOG%
    echo   Common causes: wrong login, wrong REMOTE_APP folder ^(run: deploy.bat check^),
    echo   or the internet connection.
    echo  ------------------------------------------------------------
    goto :fail
)

if /i "%MODE%"=="check" (
    echo  Login works and "%REMOTE_APP%" is the Laravel project.
    echo  If ".env" was reported missing above, create it on the server first.
)
if /i "%MODE%"=="preview" echo  Above is what WOULD be uploaded. Nothing was changed.
if /i "%MODE%"=="deploy" (
    echo  ------------------------------------------------------------
    echo   Done. Changed files are on the server and the caches are cleared.
    echo   - Did this update include a new database\sql\*.sql file?
    echo     Run it in phpMyAdmin ^(backup first^) if you have not yet.
    echo   - Open https://msfootballacademy.com and check that it works.
    echo  ------------------------------------------------------------
)
goto :end

:fail
endlocal
echo.
pause
exit /b 1

:end
endlocal
echo.
pause
exit /b 0
