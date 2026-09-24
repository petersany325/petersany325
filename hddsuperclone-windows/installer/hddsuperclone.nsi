; HDDSuperClone for Windows — branded NSIS installer
; Built from Linux or Windows: makensis /DEXE_PATH=... /DOUT_FILE=... installer/hddsuperclone.nsi

!include "MUI2.nsh"
!include "FileFunc.nsh"
!include "x64.nsh"
!insertmacro GetSize

!ifndef APP_VERSION
  !define APP_VERSION "2.4.0"
!endif
!ifndef EXE_PATH
  !define EXE_PATH "..\build-win\hddsuperclone-windows.exe"
!endif
!ifndef SRC_DIR
  !define SRC_DIR ".."
!endif
!ifndef OUT_FILE
  !define OUT_FILE "HDDSuperClone-Windows-Setup.exe"
!endif

Name "HDDSuperClone for Windows"
OutFile "${OUT_FILE}"
Unicode True
SetCompressor /SOLID lzma
RequestExecutionLevel admin
InstallDir "$PROGRAMFILES64\HDDSuperClone"
InstallDirRegKey HKLM "Software\HDDSuperClone" "InstallDir"
ShowInstDetails show
BrandingText "HDDSuperClone for Windows ${APP_VERSION}  —  sector-level clone / recovery"

!define MUI_ABORTWARNING
!define MUI_ICON "${NSISDIR}\Contrib\Graphics\Icons\orange-install.ico"
!define MUI_UNICON "${NSISDIR}\Contrib\Graphics\Icons\orange-uninstall.ico"
!define MUI_HEADERIMAGE
!define MUI_HEADERIMAGE_BITMAP "${NSISDIR}\Contrib\Graphics\Header\orange.bmp"
!define MUI_WELCOMEFINISHPAGE_BITMAP "${NSISDIR}\Contrib\Graphics\Wizard\orange.bmp"
!define MUI_UNWELCOMEFINISHPAGE_BITMAP "${NSISDIR}\Contrib\Graphics\Wizard\orange.bmp"
!define MUI_FINISHPAGE_RUN "$INSTDIR\hddsuperclone-windows.exe"
!define MUI_FINISHPAGE_RUN_TEXT "Launch HDDSuperClone (Administrator)"
!define MUI_FINISHPAGE_NOAUTOCLOSE

!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_LICENSE "${SRC_DIR}\LICENSE"
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES
!insertmacro MUI_LANGUAGE "English"

VIProductVersion "${APP_VERSION}.0"
VIAddVersionKey "ProductName" "HDDSuperClone for Windows"
VIAddVersionKey "CompanyName" "HDDSuperClone Windows port"
VIAddVersionKey "FileDescription" "HDDSuperClone for Windows Setup"
VIAddVersionKey "FileVersion" "${APP_VERSION}"
VIAddVersionKey "LegalCopyright" "GPL-2. Original HDDSuperClone (C) Scott Dwyer"

Section "Install"
  SetOutPath "$INSTDIR"
  File "${EXE_PATH}"
  File "${SRC_DIR}\LICENSE"
  File "${SRC_DIR}\README.md"
  File "${SRC_DIR}\THIRD_PARTY.md"
  File "${SRC_DIR}\resources\carve_signatures.txt"
  SetOutPath "$INSTDIR\scripts"
  File /r "${SRC_DIR}\scripts\*.*"
  SetOutPath "$INSTDIR\driver\hscahci"
  File /nonfatal "${SRC_DIR}\driver\hscahci\README.md"

  WriteUninstaller "$INSTDIR\Uninstall.exe"
  WriteRegStr HKLM "Software\HDDSuperClone" "InstallDir" "$INSTDIR"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone" "DisplayName" "HDDSuperClone for Windows"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone" "UninstallString" '"$INSTDIR\Uninstall.exe"'
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone" "DisplayIcon" "$INSTDIR\hddsuperclone-windows.exe"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone" "Publisher" "HDDSuperClone Windows port (GPL-2)"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone" "DisplayVersion" "${APP_VERSION}"
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone" "NoModify" 1
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone" "NoRepair" 1
  ${GetSize} "$INSTDIR" "/S=0K" $0 $1 $2
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone" "EstimatedSize" $0

  CreateDirectory "$SMPROGRAMS\HDDSuperClone"
  CreateShortCut "$SMPROGRAMS\HDDSuperClone\HDDSuperClone.lnk" "$INSTDIR\hddsuperclone-windows.exe" "" "$INSTDIR\hddsuperclone-windows.exe" 0
  CreateShortCut "$SMPROGRAMS\HDDSuperClone\Uninstall.lnk" "$INSTDIR\Uninstall.exe"
  CreateShortCut "$DESKTOP\HDDSuperClone.lnk" "$INSTDIR\hddsuperclone-windows.exe" "" "$INSTDIR\hddsuperclone-windows.exe" 0
SectionEnd

Section "Uninstall"
  Delete "$DESKTOP\HDDSuperClone.lnk"
  Delete "$SMPROGRAMS\HDDSuperClone\HDDSuperClone.lnk"
  Delete "$SMPROGRAMS\HDDSuperClone\Uninstall.lnk"
  RMDir "$SMPROGRAMS\HDDSuperClone"
  Delete "$INSTDIR\hddsuperclone-windows.exe"
  Delete "$INSTDIR\LICENSE"
  Delete "$INSTDIR\README.md"
  Delete "$INSTDIR\THIRD_PARTY.md"
  Delete "$INSTDIR\carve_signatures.txt"
  Delete "$INSTDIR\Uninstall.exe"
  RMDir /r "$INSTDIR\scripts"
  RMDir /r "$INSTDIR\driver"
  RMDir "$INSTDIR"
  DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\HDDSuperClone"
  DeleteRegKey HKLM "Software\HDDSuperClone"
SectionEnd
