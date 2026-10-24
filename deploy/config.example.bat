@echo off
rem ======================================================================
rem  Upload settings for deploy.bat
rem
rem  1. Copy this file to   deploy\config.bat   (same folder)
rem  2. Fill in your details below
rem  deploy\config.bat is private: it is in .gitignore and is never uploaded.
rem ======================================================================


rem ---- How to log in (choose A or B) -----------------------------------

rem  A) RECOMMENDED: a site saved in WinSCP (password stored safely by WinSCP)
rem     Open WinSCP > New Site > File protocol: FTP, Encryption: TLS/SSL Explicit
rem     Host: ftp.msfootballacademy.com (or the server name from cPanel > FTP Accounts),
rem     user + password from cPanel > Save (tick "Save password") > name it: msacademy
rem     Log in once with the WinSCP window to accept the certificate.
set "FTP_SITE=msacademy"

rem  B) Or type the login here (leave FTP_SITE empty to use this).
rem     The password must not contain " or ! . Write % as %% .
set "FTP_HOST="
set "FTP_PORT=21"
set "FTP_USER="
set "FTP_PASS="
rem     1 = encrypted FTP (Explicit TLS, recommended), 0 = plain FTP
set "FTP_TLS=1"


rem ---- Where the project is on the server ------------------------------
rem  Path as the FTP login sees it. The project is in /home/zpjpjqpazx/msacademy.
rem   - FTP account whose Directory IS that folder:   REMOTE_APP=/
rem   - main cPanel login (starts in /home/zpjpjqpazx): REMOTE_APP=/msacademy
rem  Run   deploy.bat check   to make sure this folder holds Laravel (artisan file).
set "REMOTE_APP=/"

rem  Only if Laravel's public files are in a different folder instead of
rem  REMOTE_APP/public - for example the domain folder /msfootballacademy.com
rem  (then use the main cPanel login so both folders can be reached). Leave empty otherwise.
rem  index.php and .htaccess there are never overwritten.
set "REMOTE_PUBLIC="


rem ---- Optional --------------------------------------------------------
rem  1 = run "npm run build" before uploading (only needed after changing
rem  files in resources\js or resources\scss; needs Node.js + node_modules)
set "BUILD_ASSETS=0"

rem  1 = also upload vendor\ (PHP libraries). Only needed after composer.json /
rem  composer.lock changed - it is slow over FTP. Set back to 0 afterwards.
set "SYNC_VENDOR=0"

rem  Where WinSCP is installed (leave empty for the normal install folder)
set "WINSCP="
