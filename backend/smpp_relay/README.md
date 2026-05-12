# unitxt SMPP Relay Daemon

Standalone daemon that runs **outside** the unitxt Kubernetes pod, on the
host that owns the IPSec VPN tunnel to the SMSC (e.g. for Tigo TZ this is
your `Datavision-YTS Server` at public IP `41.220.143.37` — only that
source IP is whitelisted by Tigo's ACL).

## Architecture (HTTP edition)

```
┌─────────────────────┐    HTTPS     ┌──────────────────────────┐
│  unitxt (cloud)     │  ◄────────►  │  Datavision-YTS server   │
│  www.unitxt.co      │              │  41.220.143.37           │
│  ─ admin UI         │              │  ─ this relay daemon     │
│  ─ FastAPI          │              │     ▲                     │
│  ─ MongoDB (Atlas)  │              │     │ TCP/10501           │
└─────────────────────┘              │     ▼                     │
                                      │   IPSec ─► smpp01.tigo.co.tz │
                                      └──────────────────────────┘
```

The daemon does NOT touch MongoDB. It calls 4 HTTPS endpoints on the unitxt
backend:

| Endpoint | Purpose |
|---|---|
| `GET  /api/smpp-relay/providers`  | Discover active SMPP providers |
| `POST /api/smpp-relay/claim`      | Atomically claim queued messages |
| `POST /api/smpp-relay/result`     | Report submit / DLR outcomes |
| `POST /api/smpp-relay/heartbeat`  | Report bind status every 30 s |

Authentication: shared secret in the `X-Relay-Key` header (stored in
Settings Hub → Platform → `platform.relay_api_key`).

## One-time setup on the VPN host

```bash
# 1. Bring up the VPN tunnel (libreswan / Tigo IKEv2 — already done).
sudo ipsec status

# 2. Install the daemon
sudo useradd -r -m -d /opt/unitxt-smpp-relay -s /bin/bash unitxt 2>/dev/null || true
sudo cp -r /path/to/unitxt/backend/smpp_relay/* /opt/unitxt-smpp-relay/
sudo chown -R unitxt:unitxt /opt/unitxt-smpp-relay
cd /opt/unitxt-smpp-relay
sudo -u unitxt python3 -m venv venv
sudo -u unitxt venv/bin/pip install -r requirements.relay.txt

# 3. Get the relay key from the unitxt admin UI:
#    https://www.unitxt.co/admin/settings → Platform → platform.relay_api_key
#    (copy the value)

# 4. Configure
sudo cp relay.env.example relay.env
sudo nano relay.env
# Set:
#   UNITXT_BASE_URL=https://www.unitxt.co
#   UNITXT_RELAY_KEY=<paste the value from step 3>
sudo chmod 600 relay.env
sudo chown unitxt:unitxt relay.env

# 5. Start under systemd
sudo cp unitxt-smpp-relay.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now unitxt-smpp-relay
sudo journalctl -u unitxt-smpp-relay -f
```

## Expected log output

```
unitxt-smpp-relay started — base=https://www.unitxt.co
[Tigo TZ SMPP] connecting to smpp01.tigo.co.tz:10501
[Tigo TZ SMPP] bound (trx)
```

Within 30 seconds the unitxt admin UI shows the SMPP provider's bind
status as `bound`, and queued messages start flowing.

## Configuring a provider

In the unitxt admin UI → **Providers → Add provider** (or edit existing):

| Field                       | Value                                  |
|-----------------------------|----------------------------------------|
| Name                        | `Tigo TZ SMPP`                         |
| Type                        | `direct_telco`                         |
| Countries                   | `TZ`                                   |
| Channels                    | `sms`                                  |
| **Transport**               | `smpp`                                 |
| **SMPP host**               | `smpp01.tigo.co.tz`                    |
| **SMPP port**               | `10501`                                |
| **System ID**               | (provided by Tigo)                     |
| **Password**                | (provided by Tigo)                     |
| **Bind mode**               | `trx`                                  |
| **Source TON**              | `5` (alphanumeric — for Sender IDs)    |
| **Source NPI**              | `0`                                    |
| **Dest TON**                | `1` (international)                    |
| **Dest NPI**                | `1` (E.164)                            |
| **Throughput per sec**      | start at `30`, ramp up after testing   |
| **Window size**             | `10`                                   |

Save. The relay picks the new provider up within 30 s.

## Health check

```bash
sudo systemctl status unitxt-smpp-relay
sudo journalctl -u unitxt-smpp-relay --since "5 min ago"
```

In the unitxt admin UI:
* **Providers** page → bind column shows `bound` / `down`
* **Integration health** page → SMPP heartbeat age + last error
* **Messages** page → message lifecycle: `queued` → `sent` → `delivered`

## Rotating the relay key

If you ever need to rotate the key (e.g. compromised credentials):

1. unitxt admin UI → Settings Hub → Platform → `platform.relay_api_key`
2. Click edit, paste a new value (or clear it — unitxt re-generates one on
   the next relay call).
3. Update `relay.env` on the VPN host, then `systemctl restart unitxt-smpp-relay`.

## Security notes

* `relay.env` contains the relay key → chmod 600, dedicated user.
* The SMPP password is stored in the providers collection. Treat as
  sensitive: restrict admin UI access.
* The relay communicates over HTTPS only — never use plain HTTP for the
  `UNITXT_BASE_URL`.
