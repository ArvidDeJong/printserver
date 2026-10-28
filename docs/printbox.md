---
title: "The box on location"
nav_order: 4
description: "Set up the print box: Linux with CUPS, a queue per printer, the agent that polls the endpoints and the systemd service that keeps it running."
---

# The box on location

One box per location, not per printer. It needs a wired network connection, Linux, CUPS and a small
agent that polls the endpoints of your application.

Anything from a Raspberry Pi 4 upwards does the job: 2 GB of memory is plenty, and an SSD or eMMC
outlives an SD card by years. A second hand thin client with an Intel N4000 or newer is just as
suitable and costs less than a Pi with decent storage.

## The printers in CUPS

Every printer gets a queue named exactly like the key you use when you queue a job. For a printer
that speaks IPP, which is almost every network printer made after 2015:

```bash
sudo lpadmin -p PRINTER01 -E -v ipp://192.168.1.20/ipp/print -m everywhere
```

A label printer gets a raw queue, so CUPS passes the bytes through untouched:

```bash
sudo lpadmin -p ZEBRA01 -E -v socket://192.168.1.30:9100 -o printer-is-shared=false
```

Check what CUPS made of them with `lpstat -l -p PRINTER01,ZEBRA01` (comma separated, a space only
reports the first one).

## The agent

The agent is a small program that loops:

1. `POST <base>/claim` with the token as a bearer token.
2. For every job in the answer, `GET <base>/file/{uuid}`.
3. Hand the bytes to `lp -d <printer>`.
4. `POST <base>/done/{uuid}`, or `POST <base>/failed/{uuid}` with the reason.

A poll every three seconds is a sensible default: fast enough that a counter clerk does not notice,
slow enough to be invisible in your logs.

Its configuration holds the url, the prefix, the token and the printers:

```json
{
  "base_url": "https://example.com",
  "base_path": "/print",
  "token": "nSbQ...",
  "interval": 3,
  "printers": {
    "PRINTER01": "192.168.1.20",
    "ZEBRA01": "192.168.1.30"
  }
}
```

Keep the file readable only by the user that runs the agent: it holds the token.

## Keep it running

A systemd service with `Restart=always` is all that is needed. After a power cut the box comes back
up, the agent starts and the jobs that were waiting come out. Nothing has to be done by hand.

```ini
[Unit]
Description=Print box
After=network-online.target cups.service

[Service]
ExecStart=/usr/bin/python3 /opt/printbox/agent.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

## Keep the box out of reach too

- No port forward to the box. It only makes outgoing connections.
- Give the box a fixed address, so the printers and the firewall rules keep pointing at the right
  machine.
- A management interface such as Cockpit belongs behind a rule that only allows the local network.
- In CUPS, `Allow from` the local network only, and leave `Listen` on the local addresses.
