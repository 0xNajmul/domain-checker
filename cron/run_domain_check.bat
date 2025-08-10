@echo off
set PHP_EXECUTABLE=C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe
set SCRIPT_PATH=%~dp0check_domains.php

%PHP_EXECUTABLE% %SCRIPT_PATH% >> %~dp0domain_check.log 2>&1
