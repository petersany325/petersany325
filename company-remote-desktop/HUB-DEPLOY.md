# Deploy the Hub on Linux (`hdd-land.com`)

The company website already runs on this host (cPanel). **Hub is a separate TCP service** — not Apache/nginx. Bind it on all interfaces and open **TCP 5938**.

Windows Agents and Viewers default to **`hdd-land.com:5938`**. You do not put the Hub behind HTTP.

## Binary

Copy the release Linux x86_64 `hub` onto the server (this host’s A record is `hdd-land.com` ≈ `148.251.27.33`):

```bash
sudo install -d /opt/crd
sudo install -m 0755 hub /opt/crd/hub
sudo useradd --system --home /var/lib/crd --shell /usr/sbin/nologin crd || true
sudo install -d -o crd -g crd /var/lib/crd
```

## systemd

`/etc/systemd/system/crd-hub.service`:

```ini
[Unit]
Description=Company Remote Desktop Hub
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=crd
Group=crd
WorkingDirectory=/var/lib/crd
ExecStart=/opt/crd/hub --bind 0.0.0.0 --port 5938 --data /var/lib/crd/hub-state.db
Restart=always
RestartSec=2
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now crd-hub
sudo systemctl status crd-hub
```

`--bind 0.0.0.0` is required so Agents on the internet can reach the Hub. Do not bind `127.0.0.1`.

## Firewall (TCP 5938)

cPanel / CSF (typical on this host):

```bash
# CSF: add 5938 to TCP_IN in /etc/csf/csf.conf, then:
sudo csf -r
```

firewalld:

```bash
sudo firewall-cmd --permanent --add-port=5938/tcp
sudo firewall-cmd --reload
```

ufw:

```bash
sudo ufw allow 5938/tcp
```

iptables:

```bash
sudo iptables -I INPUT -p tcp --dport 5938 -j ACCEPT
```

Confirm from another machine:

```bash
nc -vz hdd-land.com 5938
```

## Check

```bash
ss -lnt | grep 5938
journalctl -u crd-hub -e
```

`hub` should listen on `0.0.0.0:5938`. Agents then show a 9-digit ID automatically; Viewers connect by ID + password through this process.
