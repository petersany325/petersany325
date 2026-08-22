@echo off
chcp 65001 >nul
cd /d "%~dp0"
for %%F in (app.js index.html license.js project-path.json PROJECT-PATH.txt family-fw-reference.json styles.css) do (
  if exist "resources\app\%%F" copy /Y "resources\app\%%F" "%%F" >nul 2>&1
)
if exist "resources\app\electron\main.js" copy /Y "resources\app\electron\main.js" "electron\main.js" >nul 2>&1
echo Synced app sources from resources\app to wd-factory-desk (dev mirror).
