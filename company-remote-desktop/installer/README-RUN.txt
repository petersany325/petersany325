Company Remote Desktop 0.2.0 — AnyDesk-like IDs (company Hub)

1) Company server (or this PC for a demo):
   hub.exe --bind 0.0.0.0 --port 5938 --data hub-state.db

   Firewall on the Hub server:
   netsh advfirewall firewall add rule name="CRD Hub" dir=in action=allow protocol=TCP localport=5938

2) Training PC:
   Edit config.json so hub_host is the Hub IP/hostname, then run agent.exe
   First launch shows a new ID (example 390 367 767). Copy it. Set the access password.

3) Instructor / trainee:
   viewer.exe --id 390367767 --hub HUB_IP --port 5938 --password THE_ACCESS_PASSWORD
   Or fill ID + Hub + password in the Viewer window.

You do not need the training PC LAN IP. The Hub relays the session.

config.json example:
  { "hub_host": "127.0.0.1", "hub_port": 5938 }
