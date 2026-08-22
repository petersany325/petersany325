const { app, BrowserWindow, Menu, dialog, shell, ipcMain } = require("electron");
const path = require("path");
const fs = require("fs");

const APP_DEFAULT = "D:\\test windex\\WindexWD-Prototype-Test";
const PROJECT_DEFAULT = "D:\\test windex";
let mainWindow = null;

function licensePath() {
  return path.join(app.getPath("userData"), "license.dat");
}

function settingsPath() {
  return path.join(app.getPath("userData"), "settings.json");
}

function bundledProjectPaths() {
  const p = path.join(__dirname, "..", "project-path.json");
  try {
    return JSON.parse(fs.readFileSync(p, "utf8"));
  } catch {
    return {};
  }
}

function defaultSettings() {
  const bundled = bundledProjectPaths();
  return {
    appRoot: bundled.appRoot || APP_DEFAULT,
    projectRoot: bundled.projectRoot || PROJECT_DEFAULT,
    packRoot: bundled.packRoot || path.join(PROJECT_DEFAULT, "FW"),
    backupRoot: bundled.backupRoot || path.join(PROJECT_DEFAULT, "Backup"),
    fwSource: "pack"
  };
}

function readSettings() {
  const base = defaultSettings();
  try {
    return { ...base, ...JSON.parse(fs.readFileSync(settingsPath(), "utf8")) };
  } catch {
    return base;
  }
}

function writeSettings(data) {
  const merged = { ...readSettings(), ...(data || {}) };
  fs.mkdirSync(app.getPath("userData"), { recursive: true });
  fs.writeFileSync(settingsPath(), JSON.stringify(merged, null, 2), "utf8");
  return merged;
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1440,
    height: 920,
    minWidth: 1100,
    minHeight: 700,
    title: "Windex WD",
    backgroundColor: "#071012",
    autoHideMenuBar: false,
    icon: path.join(__dirname, "..", "assets", "app-icon.png"),
    webPreferences: {
      preload: path.join(__dirname, "preload.js"),
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: false
    }
  });

  mainWindow.loadFile(path.join(__dirname, "..", "index.html"));

  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    // Open keygen / external links in a child window inside the app
    if (url.startsWith("file:") || url.includes("license-keygen")) {
      const child = new BrowserWindow({
        width: 640,
        height: 720,
        parent: mainWindow,
        title: "Windex WD — Seller Keygen",
        icon: path.join(__dirname, "..", "assets", "app-icon.png"),
        webPreferences: {
          preload: path.join(__dirname, "preload.js"),
          contextIsolation: true,
          nodeIntegration: false
        }
      });
      child.setMenuBarVisibility(false);
      child.loadFile(path.join(__dirname, "..", "license-keygen.html"));
      return { action: "deny" };
    }
    shell.openExternal(url);
    return { action: "deny" };
  });
}

function buildMenu() {
  const template = [
    {
      label: "File",
      submenu: [
        {
          label: "License Settings…",
          accelerator: "CmdOrCtrl+L",
          click: () => mainWindow?.webContents.send("open-license-settings")
        },
        {
          label: "Project Paths…",
          click: () => mainWindow?.webContents.send("open-license-settings", { tab: "paths" })
        },
        { type: "separator" },
        {
          label: "Open App Folder",
          click: () => {
            const s = readSettings();
            const root = s.appRoot || APP_DEFAULT;
            fs.mkdirSync(root, { recursive: true });
            shell.openPath(root);
          }
        },
        {
          label: "Open Data Folder (FW / Backup)",
          click: () => {
            const s = readSettings();
            const root = s.projectRoot || PROJECT_DEFAULT;
            fs.mkdirSync(path.join(root, "FW"), { recursive: true });
            fs.mkdirSync(path.join(root, "Backup"), { recursive: true });
            shell.openPath(root);
          }
        },
        {
          label: "Open License Folder",
          click: () => shell.openPath(app.getPath("userData"))
        },
        { type: "separator" },
        { role: "quit", label: "Exit" }
      ]
    },
    {
      label: "View",
      submenu: [
        { role: "reload" },
        { role: "toggleDevTools" },
        { type: "separator" },
        { role: "resetZoom" },
        { role: "zoomIn" },
        { role: "zoomOut" },
        { type: "separator" },
        { role: "togglefullscreen" }
      ]
    },
    {
      label: "Help",
      submenu: [
        {
          label: "Seller Keygen…",
          click: () => {
            const child = new BrowserWindow({
              width: 640,
              height: 720,
              parent: mainWindow,
              title: "Windex WD — Seller Keygen",
              icon: path.join(__dirname, "..", "assets", "app-icon.png"),
              webPreferences: {
                preload: path.join(__dirname, "preload.js"),
                contextIsolation: true,
                nodeIntegration: false
              }
            });
            child.setMenuBarVisibility(false);
            child.loadFile(path.join(__dirname, "..", "license-keygen.html"));
          }
        },
        {
          label: "About Windex WD",
          click: () => {
            dialog.showMessageBox(mainWindow, {
              type: "info",
              title: "About Windex WD",
              message: "Windex WD",
              detail:
                "HamGap · Professional factory desk prototype\n" +
                "Win7 / Win10 / Win11 x64\n\n" +
                `Version ${app.getVersion()}\n` +
                `App: ${(readSettings().appRoot || APP_DEFAULT)}\n` +
                `Data: ${(readSettings().projectRoot || PROJECT_DEFAULT)}\n` +
                `License: ${licensePath()}`
            });
          }
        }
      ]
    }
  ];
  Menu.setApplicationMenu(Menu.buildFromTemplate(template));
}

function registerIpc() {
  ipcMain.handle("license:read", () => {
    try {
      return fs.readFileSync(licensePath(), "utf8");
    } catch {
      return null;
    }
  });

  ipcMain.handle("license:write", (_e, text) => {
    fs.mkdirSync(app.getPath("userData"), { recursive: true });
    fs.writeFileSync(licensePath(), String(text || ""), "utf8");
    return true;
  });

  ipcMain.handle("license:clear", () => {
    try {
      fs.unlinkSync(licensePath());
    } catch {
      /* missing ok */
    }
    return true;
  });

  ipcMain.handle("license:path", () => licensePath());

  ipcMain.handle("settings:read", () => readSettings());
  ipcMain.handle("settings:write", (_e, data) => writeSettings(data || {}));

  ipcMain.handle("app:info", () => ({
    name: app.getName(),
    version: app.getVersion(),
    userData: app.getPath("userData"),
    licensePath: licensePath(),
    isPackaged: app.isPackaged,
    platform: process.platform
  }));
}

const gotLock = app.requestSingleInstanceLock();
if (!gotLock) {
  app.quit();
} else {
  app.on("second-instance", () => {
    if (mainWindow) {
      if (mainWindow.isMinimized()) mainWindow.restore();
      mainWindow.focus();
    }
  });

  app.whenReady().then(() => {
    registerIpc();
    buildMenu();
    createWindow();
  });

  app.on("window-all-closed", () => {
    if (process.platform !== "darwin") app.quit();
  });
}
