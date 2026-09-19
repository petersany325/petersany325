Company Remote Desktop 0.3.0

Hub is already set to hdd-land.com port 5938. You do not type a server address.

1) Start CompanyRemoteDesktop.exe (or the Start Menu shortcut).
   The app connects to the Hub and shows This PC ID (example 390 367 767).
   If the Hub is unreachable it retries and shows that in the status bar.

2) Set / save an unattended password on this PC. Give your ID + that password
   to the other person.

3) Enter their Remote ID + password and click Connect.
   The Hub relays the session (screen + mouse/keyboard).

Optional separate apps in this folder:
  agent.exe   — ID / unattended password only
  viewer.exe  — connect to a remote ID only

config.json (already written):
  { "hub_host": "hdd-land.com", "hub_port": 5938 }

Server operators: see HUB-DEPLOY.md (Linux hub, systemd, firewall TCP 5938).
