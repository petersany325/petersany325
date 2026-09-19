; Company Remote Desktop 0.2 — Agent + Viewer (Hub address at install time)
Unicode true
ManifestDPIAware true

!include "MUI2.nsh"
!include "x64.nsh"
!include "FileFunc.nsh"
!include "nsDialogs.nsh"
!include "LogicLib.nsh"

!define PRODUCT_NAME "Company Remote Desktop"
!define PRODUCT_PUBLISHER "Company Remote Desktop"
!define PRODUCT_VERSION "0.2.0"
!define PRODUCT_DIR_REGKEY "Software\Microsoft\Windows\CurrentVersion\App Paths\crd-agent.exe"
!define UNINST_KEY "Software\Microsoft\Windows\CurrentVersion\Uninstall\CompanyRemoteDesktop"

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

Var HubHostCtl
Var HubPortCtl
Var HubHostVal
Var HubPortVal

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "${PRODUCT_NAME}"
!define MUI_WELCOMEPAGE_TEXT "Installs Agent (training PC) and Viewer (instructor).$\r$\n$\r$\nThe Agent registers with your company Hub and gets a unique ID — like AnyDesk, without AnyDesk cloud.$\r$\n$\r$\nRun Hub on a company server first (hub.exe from the release zip)."
!define MUI_FINISHPAGE_NOAUTOCLOSE
!define MUI_FINISHPAGE_TITLE "Installation complete"
!define MUI_FINISHPAGE_TEXT "Installed to $INSTDIR$\r$\n$\r$\n1) Start Agent on the training PC. Copy the ID.$\r$\n2) In Viewer enter that ID + access password.$\r$\n$\r$\nHub default port is 5938. Open TCP 5938 on the Hub server.$\r$\n$\r$\nعامل: ID را بدهید. بیننده: با ID وصل شوید."
!define MUI_FINISHPAGE_RUN "$INSTDIR\agent.exe"
!define MUI_FINISHPAGE_RUN_TEXT "Run Agent now"
!define MUI_FINISHPAGE_RUN_NOTCHECKED

!insertmacro MUI_PAGE_WELCOME
Page custom HubPage HubPageLeave
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
  StrCpy $HubHostVal "127.0.0.1"
  StrCpy $HubPortVal "5938"
FunctionEnd

Function HubPage
  nsDialogs::Create 1018
  Pop $0
  ${NSD_CreateLabel} 0 0 100% 36u "Company Hub — the server that issues IDs and relays sessions. Use 127.0.0.1 only for a same-PC demo."
  ${NSD_CreateLabel} 0 50u 90u 12u "Hub host / IP"
  ${NSD_CreateText} 100u 48u 220u 12u $HubHostVal
  Pop $HubHostCtl
  ${NSD_CreateLabel} 0 74u 90u 12u "Hub port"
  ${NSD_CreateText} 100u 72u 80u 12u $HubPortVal
  Pop $HubPortCtl
  nsDialogs::Show
FunctionEnd

Function HubPageLeave
  ${NSD_GetText} $HubHostCtl $HubHostVal
  ${NSD_GetText} $HubPortCtl $HubPortVal
FunctionEnd

Section "Program files (required)" SecCore
  SectionIn RO
  SetOutPath "$INSTDIR"
  File "payload\agent.exe"
  File "payload\viewer.exe"
  File "payload\README-RUN.txt"
  FileOpen $0 "$INSTDIR\config.json" w
  FileWrite $0 '{"hub_host":"$HubHostVal","hub_port":$HubPortVal}$\r$\n'
  FileClose $0

  CreateDirectory "$SMPROGRAMS\${PRODUCT_NAME}"
  CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\Agent.lnk" "$INSTDIR\agent.exe" "" "$INSTDIR\agent.exe" 0
  CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\Viewer.lnk" "$INSTDIR\viewer.exe" "" "$INSTDIR\viewer.exe" 0
  CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\Uninstall.lnk" "$INSTDIR\Uninstall.exe"

  WriteUninstaller "$INSTDIR\Uninstall.exe"

  SetRegView 64
  WriteRegStr HKLM "${UNINST_KEY}" "DisplayName" "${PRODUCT_NAME}"
  WriteRegStr HKLM "${UNINST_KEY}" "DisplayVersion" "${PRODUCT_VERSION}"
  WriteRegStr HKLM "${UNINST_KEY}" "Publisher" "${PRODUCT_PUBLISHER}"
  WriteRegStr HKLM "${UNINST_KEY}" "InstallLocation" "$INSTDIR"
  WriteRegStr HKLM "${UNINST_KEY}" "UninstallString" '"$INSTDIR\Uninstall.exe"'
  WriteRegStr HKLM "${UNINST_KEY}" "QuietUninstallString" '"$INSTDIR\Uninstall.exe" /S'
  WriteRegStr HKLM "${UNINST_KEY}" "DisplayIcon" "$INSTDIR\agent.exe"
  WriteRegDWORD HKLM "${UNINST_KEY}" "NoModify" 1
  WriteRegDWORD HKLM "${UNINST_KEY}" "NoRepair" 1
  WriteRegStr HKLM "${PRODUCT_DIR_REGKEY}" "" "$INSTDIR\agent.exe"
  WriteRegStr HKLM "${PRODUCT_DIR_REGKEY}" "Path" "$INSTDIR"

  ${GetSize} "$INSTDIR" "/S=0K" $0 $1 $2
  IntFmt $0 "0x%08X" $0
  WriteRegDWORD HKLM "${UNINST_KEY}" "EstimatedSize" "$0"
SectionEnd

Section "Desktop shortcuts" SecDesktop
  CreateShortCut "$DESKTOP\CRD Agent.lnk" "$INSTDIR\agent.exe" "" "$INSTDIR\agent.exe" 0
  CreateShortCut "$DESKTOP\CRD Viewer.lnk" "$INSTDIR\viewer.exe" "" "$INSTDIR\viewer.exe" 0
SectionEnd

Section "un.Uninstall"
  SetRegView 64
  Delete "$INSTDIR\agent.exe"
  Delete "$INSTDIR\viewer.exe"
  Delete "$INSTDIR\config.json"
  Delete "$INSTDIR\README-RUN.txt"
  Delete "$INSTDIR\Uninstall.exe"
  RMDir "$INSTDIR"

  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Agent.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Viewer.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Host.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Uninstall.lnk"
  RMDir "$SMPROGRAMS\${PRODUCT_NAME}"

  Delete "$DESKTOP\CRD Agent.lnk"
  Delete "$DESKTOP\CRD Viewer.lnk"
  Delete "$DESKTOP\CRD Host.lnk"

  DeleteRegKey HKLM "${UNINST_KEY}"
  DeleteRegKey HKLM "${PRODUCT_DIR_REGKEY}"
SectionEnd

LangString DESC_SecCore ${LANG_ENGLISH} "Agent, Viewer, config.json, Start Menu shortcuts, Add/Remove Programs uninstaller."
LangString DESC_SecDesktop ${LANG_ENGLISH} "Optional shortcuts on the desktop."
!insertmacro MUI_FUNCTION_DESCRIPTION_BEGIN
  !insertmacro MUI_DESCRIPTION_TEXT ${SecCore} $(DESC_SecCore)
  !insertmacro MUI_DESCRIPTION_TEXT ${SecDesktop} $(DESC_SecDesktop)
!insertmacro MUI_FUNCTION_DESCRIPTION_END
