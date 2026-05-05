# unitxt SMPP Relay Daemon

This is a **standalone daemon** that runs *outside* the unitxt Kubernetes pod.
It must be deployed on the host that owns the IPSec VPN tunnel to the SMSC
(e.g. for Tigo TZ this is your `Datavision-YTS Server` at public IP
`41.220.143.37` — only that source IP is whitelisted by Tigo's ACL).

## Architecture

```
   ┌─────────────────────────┐                 ┌──────────────────────┐
   │  unitxt (Kubernetes)    │                 │  Datavision-YTS      │
   │  ─ admin UI             │     MongoDB     │  ─ libreswan VPN     │
   │  ─ FastAPI              │ ◄─── shared ──► │  ─ unitxt-smpp-relay │
   │  ─ writes smpp_outbox   │                 │     (this daemon)    │
   └─────────────────────────┘                 │     ▲                │
                                               │     │ TCP/10501       │
                                               │     ▼                │
                                               │   IPSec ─► smpp01.tigo.co.tz │
                                               └──────────────────────┘
```

Every queued SMS becomes a row in `db.smpp_outbox`. The relay drains those
rows, holds the SMPP bind, submits PDUs, and writes DLRs back into
`db.messages`. The unitxt admin UI shows bind health via
`providers.last_smpp_heartbeat`.

## One-time setup on the VPN host

```bash
# 1. Bring up the VPN tunnel (libreswan / Tigo IKEv2 — already done).
sudo ipsec status

# 2. Install the daemon
sudo useradd -r -m -d /opt/unitxt-smpp-relay -s /bin/bash unitxt
sudo cp -r /path/to/unitxt/backend/smpp_relay/* /opt/unitxt-smpp-relay/
cd /opt/unitxt-smpp-relay
sudo -u unitxt python3 -m venv venv
sudo -u unitxt venv/bin/pip install -r requirements.relay.txt

# 3. Configure
sudo cp relay.env.example relay.env
sudo nano relay.env     # paste MONGO_URL + DB_NAME (same as unitxt's)
sudo chmod 600 relay.env
sudo chown unitxt:unitxt relay.env

# 4. Start under systemd
sudo cp unitxt-smpp-relay.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now unitxt-smpp-relay
sudo journalctl -u unitxt-smpp-relay -f
```

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
| **System type**             | leave blank unless Tigo specifies      |
| **Bind mode**               | `trx` (default)                        |
| **Source TON**              | `5` (alphanumeric — for Sender IDs)    |
| **Source NPI**              | `0`                                    |
| **Dest TON**                | `1` (international)                    |
| **Dest NPI**                | `1` (E.164)                            |
| **Throughput per sec**      | start at `30`, ramp up after testing   |
| **Window size**             | `10` (uncommitted submit_sm cap)       |

Hit **Save**. The relay will pick the new provider up within 30 s and
attempt to bind. The bind status appears at `Integration health → Tigo TZ`.

## Health check

```bash
sudo systemctl status unitxt-smpp-relay
sudo journalctl -u unitxt-smpp-relay --since "5 min ago"

# In MongoDB:
db.providers.findOne({transport: "smpp"}, {smpp_bind_status:1, last_smpp_heartbeat:1})
db.smpp_outbox.aggregate([{$group:{_id:"$status", n:{$sum:1}}}])
```

## Failure handling

* **Bind drops**: the relay marks `smpp_bind_status: down`, attempts a fresh
  bind on the next 30 s reconcile cycle.
* **Outbox row stuck in `sending`**: typically means the daemon crashed
  mid-submit. Restart the daemon — un-claimed rows older than 5 minutes
  should be moved back to `queued` by an operator.
* **No DLR for hours**: confirm Tigo is configured to send DLRs to your bind.
  Submit IDs without a DLR remain `sent` (not `delivered`).

## Security notes

* The relay reads MongoDB directly — use a **dedicated read/write user**
  scoped to `db.providers`, `db.messages`, and `db.smpp_outbox` only.
* `relay.env` contains the MongoDB credentials — chmod 600 + dedicated user.
* The SMPP password is stored in the providers collection. Treat it as
  sensitive: restrict admin UI access to this collection.
