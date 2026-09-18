@echo off
REM Windows Task Scheduler helper for XAMPP installs.
REM Adjust the PHP path below if your XAMPP install differs from the default.
REM Create a scheduled task that runs this script every 15-30 minutes:
REM   schtasks /Create /SC MINUTE /MO 15 /TN "SocialAffairsRecalculate" /TR "C:\xampp\htdocs\social-affairs\cron\run_recalculate.bat"

set PHP_BIN=C:\xampp\php\php.exe
set SCRIPT_DIR=%~dp0

"%PHP_BIN%" "%SCRIPT_DIR%recalculate.php" >> "%SCRIPT_DIR%..\storage\logs\cron.log" 2>&1
