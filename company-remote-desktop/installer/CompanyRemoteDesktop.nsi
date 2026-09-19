; Company Remote Desktop 0.3 — combined UI, Hub baked to hdd-land.com:5938
Unicode true
ManifestDPIAware true

!include "MUI2.nsh"
!include "x64.nsh"
!include "FileFunc.nsh"
!include "LogicLib.nsh"

!define PRODUCT_NAME "Company Remote Desktop"
!define PRODUCT_PUBLISHER "Company Remote Desktop"
!define PRODUCT_VERSION "0.3.0"
!define PRODUCT_DIR_REGKEY "Software\Microsoft\Windows\CurrentVersion\App Paths\CompanyRemoteDesktop.exe"
!define UNINST_KEY "Software\Microsoft\Windows\CurrentVersion\Uninstall\CompanyRemoteDesktop"
!define HUB_HOST "hdd-land.com"
!define HUB_PORT "5938"

Name "${PRODUCT_NAME}"
OutFile "CompanyRemoteDesktop-Setup-x64.exe"
InstallDir "$PROGRAMFILES64\Company Remote Desktop"
InstallDirRegKey HKLM "${UNINST_KEY}" "InstallLocation"
RequestExecutionLevel admin
SetCompressor /SOLID lzma
ShowInstDetails show
ShowUninstDetails show

VIProductVersion "${PRODUCT_VERSION}.0"
VIAddVersionKey /LANG=1033 "ProductName" "${PRODUCT_NAME}"
VIAddVersionKey /LANG=1033 "FileDescription" "${PRODUCT_NAME} Setup"
VIAddVersionKey /LANG=1033 "FileVersion" "${PRODUCT_VERSION}"
VIAddVersionKey /LANG=1033 "ProductVersion" "${PRODUCT_VERSION}"
VIAddVersionKey /LANG=1033 "LegalCopyright" "Internal company use"

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "${PRODUCT_NAME}"
!define MUI_WELCOMEPAGE_TEXT "Installs Company Remote Desktop on this PC.$\r$\n$\r$\nOne window: your ID (this PC) and Connect to a remote ID — like AnyDesk.$\r$\n$\r$\nHub is already set to ${HUB_HOST}:${HUB_PORT}. You do not type a server address."
!define MUI_FINISHPAGE_NOAUTOCLOSE
!define MUI_FINISHPAGE_TITLE "Installation complete"
!define MUI_FINISHPAGE_TEXT "Installed to $INSTDIR$\r$\n$\r$\n1) Start Company Remote Desktop. Your ID appears automatically.$\r$\n2) Give that ID + your unattended password to the other person.$\r$\n3) Enter their ID + password and click Connect.$\r$\n$\r$\nHub: ${HUB_HOST}:${HUB_PORT}"
!define MUI_FINISHPAGE_RUN "$INSTDIR\CompanyRemoteDesktop.exe"
!define MUI_FINISHPAGE_RUN_TEXT "Run Company Remote Desktop now"
!define MUI_FINISHPAGE_RUN_NOTCHECKED

!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_COMPONENTS
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES
!insertmacro MUI_LANGUAGE "English"

Function .onInit
  ${IfNot} ${RunningX64}
    MessageBox MB_OK|MB_ICONSTOP "This installer is for 64-bit Windows."
    Abort
  ${EndIf}
  SetRegView 64
FunctionEnd

Section "Program files (required)" SecCore
  SectionIn RO
  SetOutPath "$INSTDIR"
  File "payload\CompanyRemoteDesktop.exe"
  File "payload\agent.exe"
  File "payload\viewer.exe"
  File "payload\README-RUN.txt"
  FileOpen $0 "$INSTDIR\config.json" w
  FileWrite $0 '{"hub_host":"${HUB_HOST}","hub_port":${HUB_PORT}}$\r$\n'
  FileClose $0

  CreateDirectory "$SMPROGRAMS\${PRODUCT_NAME}"
  CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\Company Remote Desktop.lnk" "$INSTDIR\CompanyRemoteDesktop.exe" "" "$INSTDIR\CompanyRemoteDesktop.exe" 0
  CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\Uninstall.lnk" "$INSTDIR\Uninstall.exe"

  WriteUninstaller "$INSTDIR\Uninstall.exe"

  SetRegView 64
  WriteRegStr HKLM "${UNINST_KEY}" "DisplayName" "${PRODUCT_NAME}"
  WriteRegStr HKLM "${UNINST_KEY}" "DisplayVersion" "${PRODUCT_VERSION}"
  WriteRegStr HKLM "${UNINST_KEY}" "Publisher" "${PRODUCT_PUBLISHER}"
  WriteRegStr HKLM "${UNINST_KEY}" "InstallLocation" "$INSTDIR"
  WriteRegStr HKLM "${UNINST_KEY}" "UninstallString" '"$INSTDIR\Uninstall.exe"'
  WriteRegStr HKLM "${UNINST_KEY}" "QuietUninstallString" '"$INSTDIR\Uninstall.exe" /S'
  WriteRegStr HKLM "${UNINST_KEY}" "DisplayIcon" "$INSTDIR\CompanyRemoteDesktop.exe"
  WriteRegDWORD HKLM "${UNINST_KEY}" "NoModify" 1
  WriteRegDWORD HKLM "${UNINST_KEY}" "NoRepair" 1
  WriteRegStr HKLM "${PRODUCT_DIR_REGKEY}" "" "$INSTDIR\CompanyRemoteDesktop.exe"
  WriteRegStr HKLM "${PRODUCT_DIR_REGKEY}" "Path" "$INSTDIR"

  ${GetSize} "$INSTDIR" "/S=0K" $0 $1 $2
  IntFmt $0 "0x%08X" $0
  WriteRegDWORD HKLM "${UNINST_KEY}" "EstimatedSize" "$0"
SectionEnd

Section "Desktop shortcut" SecDesktop
  CreateShortCut "$DESKTOP\Company Remote Desktop.lnk" "$INSTDIR\CompanyRemoteDesktop.exe" "" "$INSTDIR\CompanyRemoteDesktop.exe" 0
SectionEnd

Section "un.Uninstall"
  SetRegView 64
  Delete "$INSTDIR\CompanyRemoteDesktop.exe"
  Delete "$INSTDIR\agent.exe"
  Delete "$INSTDIR\viewer.exe"
  Delete "$INSTDIR\config.json"
  Delete "$INSTDIR\README-RUN.txt"
  Delete "$INSTDIR\Uninstall.exe"
  RMDir "$INSTDIR"

  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Company Remote Desktop.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Agent.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Viewer.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Host.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Uninstall.lnk"
  RMDir "$SMPROGRAMS\${PRODUCT_NAME}"

  Delete "$DESKTOP\Company Remote Desktop.lnk"
  Delete "$DESKTOP\CRD Agent.lnk"
  Delete "$DESKTOP\CRD Viewer.lnk"
  Delete "$DESKTOP\CRD Host.lnk"

  DeleteRegKey HKLM "${UNINST_KEY}"
  DeleteRegKey HKLM "${PRODUCT_DIR_REGKEY}"
SectionEnd

LangString DESC_SecCore ${LANG_ENGLISH} "Company Remote Desktop (My ID + Connect to ID), config.json for hdd-land.com:5938, Start Menu shortcut, uninstaller."
LangString DESC_SecDesktop ${LANG_ENGLISH} "Optional shortcut on the desktop."
!insertmacro MUI_FUNCTION_DESCRIPTION_BEGIN
  !insertmacro MUI_DESCRIPTION_TEXT ${SecCore} $(DESC_SecCore)
  !insertmacro MUI_DESCRIPTION_TEXT ${SecDesktop} $(DESC_SecDesktop)
!insertmacro MUI_FUNCTION_DESCRIPTION_END
