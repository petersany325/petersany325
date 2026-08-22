@echo off
chcp 65001 >nul
set "APP=%~dp0app\license-keygen.html"
where msedge >nul 2>&1 && start "" msedge --app="%APP%" && exit /b 0
start "" "%APP%"
