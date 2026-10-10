; Inno Setup script — حساب HDD Windows installer
; Built by GitHub Actions or locally: iscc HesabHDD.iss

#define MyAppName "حساب HDD"
#define MyAppNameEn "Hesab HDD"
#define MyAppVersion "1.1.0"
#define MyAppPublisher "HDD Land"
#define MyAppURL "https://hdd-land.ir/hesab/"
#define MyAppExeName "HesabWin.exe"

[Setup]
AppId={{A8E3C1D2-7B4F-4E9A-9C21-8F0D1E2A3B4C}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
DefaultDirName={autopf}\HesabHDD
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
LicenseFile=
OutputDir=..\artifacts
OutputBaseFilename=Hesab-HDD-Setup-{#MyAppVersion}
SetupIconFile=..\HesabWin\Assets\app.ico
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=lowest
PrivilegesRequiredOverridesAllowed=dialog
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
UninstallDisplayIcon={app}\{#MyAppExeName}
VersionInfoVersion={#MyAppVersion}
VersionInfoCompany={#MyAppPublisher}
VersionInfoDescription={#MyAppName} Setup
VersionInfoProductName={#MyAppName}

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "ایجاد میانبر روی دسکتاپ / Create desktop shortcut"; GroupDescription: "میانبرها:"; Flags: unchecked

[Files]
; Publish output from: winclient/artifacts/publish
Source: "..\artifacts\publish\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"
Name: "{group}\تنظیمات دیتابیس"; Filename: "{app}\{#MyAppExeName}"; Parameters: "--setup"
Name: "{group}\حذف نصب {#MyAppName}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon

[Run]
Filename: "{app}\{#MyAppExeName}"; Parameters: "--setup"; Description: "اجرای راهنمای نصب دیتابیس بعد از نصب"; Flags: nowait postinstall skipifsilent
