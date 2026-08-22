const { contextBridge, ipcRenderer } = require("electron");

contextBridge.exposeInMainWorld("windexDesktop", {
  isDesktop: true,
  readLicense: () => ipcRenderer.invoke("license:read"),
  writeLicense: (text) => ipcRenderer.invoke("license:write", text),
  clearLicense: () => ipcRenderer.invoke("license:clear"),
  licensePath: () => ipcRenderer.invoke("license:path"),
  readSettings: () => ipcRenderer.invoke("settings:read"),
  writeSettings: (data) => ipcRenderer.invoke("settings:write", data),
  appInfo: () => ipcRenderer.invoke("app:info"),
  onOpenLicenseSettings: (cb) => {
    ipcRenderer.on("open-license-settings", (_e, payload) => cb(payload || {}));
  }
});
