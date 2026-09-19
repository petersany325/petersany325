; Company Remote Desktop — Windows x64 installer (NSIS 3)
Unicode true
ManifestDPIAware true

!include "MUI2.nsh"
!include "x64.nsh"
!include "FileFunc.nsh"

!define PRODUCT_NAME "Company Remote Desktop"
!define PRODUCT_PUBLISHER "Company Remote Desktop"
!define PRODUCT_VERSION "0.1.0"
!define PRODUCT_DIR_REGKEY "Software\Microsoft\Windows\CurrentVersion\App Paths\crd-host.exe"
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

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "${PRODUCT_NAME}"
!define MUI_WELCOMEPAGE_TEXT "This installs Host and Viewer on this PC.$\r$\n$\r$\nRun Host on the training PC and Viewer on the trainee/instructor PC. Default TCP port is 5938.$\r$\n$\r$\nClick Next to continue."
!define MUI_FINISHPAGE_NOAUTOCLOSE
!define MUI_FINISHPAGE_TITLE "Installation complete"
!define MUI_FINISHPAGE_TEXT "Installed to $INSTDIR$\r$\n$\r$\nTraining PC (Host):$\r$\n  host.exe --port 5938 --password TrainRoom1$\r$\n$\r$\nViewer:$\r$\n  viewer.exe --host 192.168.1.40 --port 5938 --password TrainRoom1$\r$\n$\r$\nFirewall: allow inbound TCP 5938 on the Host PC.$\r$\n$\r$\nسیستم آموزش: host.exe --port 5938 --password TrainRoom1$\r$\nکارآموز: viewer.exe --host IP --port 5938 --password TrainRoom1"
!define MUI_FINISHPAGE_RUN "$INSTDIR\host.exe"
!define MUI_FINISHPAGE_RUN_TEXT "Run Host now"
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
  File "payload\host.exe"
  File "payload\viewer.exe"
  File "payload\README-RUN.txt"

  CreateDirectory "$SMPROGRAMS\${PRODUCT_NAME}"
  CreateShortCut "$SMPROGRAMS\${PRODUCT_NAME}\Host.lnk" "$INSTDIR\host.exe" "" "$INSTDIR\host.exe" 0
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
  WriteRegStr HKLM "${UNINST_KEY}" "DisplayIcon" "$INSTDIR\host.exe"
  WriteRegDWORD HKLM "${UNINST_KEY}" "NoModify" 1
  WriteRegDWORD HKLM "${UNINST_KEY}" "NoRepair" 1
  WriteRegStr HKLM "${PRODUCT_DIR_REGKEY}" "" "$INSTDIR\host.exe"
  WriteRegStr HKLM "${PRODUCT_DIR_REGKEY}" "Path" "$INSTDIR"

  ${GetSize} "$INSTDIR" "/S=0K" $0 $1 $2
  IntFmt $0 "0x%08X" $0
  WriteRegDWORD HKLM "${UNINST_KEY}" "EstimatedSize" "$0"
SectionEnd

Section "Desktop shortcuts" SecDesktop
  CreateShortCut "$DESKTOP\CRD Host.lnk" "$INSTDIR\host.exe" "" "$INSTDIR\host.exe" 0
  CreateShortCut "$DESKTOP\CRD Viewer.lnk" "$INSTDIR\viewer.exe" "" "$INSTDIR\viewer.exe" 0
SectionEnd

Section "un.Uninstall"
  SetRegView 64
  Delete "$INSTDIR\host.exe"
  Delete "$INSTDIR\viewer.exe"
  Delete "$INSTDIR\README-RUN.txt"
  Delete "$INSTDIR\Uninstall.exe"
  RMDir "$INSTDIR"

  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Host.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Viewer.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_NAME}\Uninstall.lnk"
  RMDir "$SMPROGRAMS\${PRODUCT_NAME}"

  Delete "$DESKTOP\CRD Host.lnk"
  Delete "$DESKTOP\CRD Viewer.lnk"

  DeleteRegKey HKLM "${UNINST_KEY}"
  DeleteRegKey HKLM "${PRODUCT_DIR_REGKEY}"
SectionEnd

LangString DESC_SecCore ${LANG_ENGLISH} "Host and Viewer applications, Start Menu shortcuts, and Add/Remove Programs uninstaller."
LangString DESC_SecDesktop ${LANG_ENGLISH} "Optional shortcuts on the desktop."
!insertmacro MUI_FUNCTION_DESCRIPTION_BEGIN
  !insertmacro MUI_DESCRIPTION_TEXT ${SecCore} $(DESC_SecCore)
  !insertmacro MUI_DESCRIPTION_TEXT ${SecDesktop} $(DESC_SecDesktop)
!insertmacro MUI_FUNCTION_DESCRIPTION_END
