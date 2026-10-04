@echo off
REM Runs Laravel's scheduler once: worker heartbeat + background email sending.
REM Schedule it every minute (run once in a Command Prompt, adjust the path if the shop lives elsewhere):
REM   schtasks /Create /SC MINUTE /MO 1 /TN "Shop scheduler" /TR "\"C:\Program Files\Ampps\www\shop\scripts\run-scheduler.bat\""
REM Uses the php on PATH; set PHP_BIN to use another one, e.g. set PHP_BIN=C:\path\to\php.exe
cd /d "%~dp0.."
if "%PHP_BIN%"=="" set PHP_BIN=php
"%PHP_BIN%" artisan schedule:run >> "storage\logs\scheduler.log" 2>&1
