@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Arresta-Locale.ps1"
if errorlevel 1 pause
