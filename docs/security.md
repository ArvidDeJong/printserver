---
title: "Security"
nav_order: 7
description: "Tokens, the administration lock, allowed addresses, and the port forwards and printer filters to close once darvis/printserver runs."
---

# Security

## One token per box
{: #one-token-per-box }

A token is `name:administration:token`:

- **name** ends up in `claimed_by`, so a job tells you which box printed it.
- **administration** is the lock. A box of one branch that points at the installation of another
  gets a `403` instead of its work, and `claim` only ever returns the jobs of its own
  administration. With one location you may leave it out, and a token is then `name:token`.
- **token** is compared with `hash_equals`, so the comparison takes the same time whatever the
  value.

Generate one with `php artisan print:token <name>`. Keep the `.env` out of your repository and
rotate a token when it has been somewhere it should not be, such as a chat or a ticket: run
`php artisan print:token <name>` again and put the new token on the box. The command replaces the
old entry of that box, so the old token stops working at once.

To withdraw a box, remove its entry from `PRINTSERVER_TOKENS`. It then gets a `401` and stops
printing, and the other boxes do not notice.

## Allowed addresses

A location with a fixed address gets a second lock:

```dotenv
PRINTSERVER_ALLOWED_IPS=203.0.113.10
```

The address is checked before the token is read. Behind a proxy or a load balancer this only works
when `TrustProxies` is right, otherwise every request appears to come from the proxy.

## What to close once this runs

The reason to install this package is usually a port forward to a printer. Close it, but not on the
first day: let the boxes run for a week, so the jobs that only happen monthly have come past.

1. Remove the forwards to port 9100 on the router.
2. Set an address filter on every printer itself, so only the box may talk to it. A printer that is
   only reachable from one address cannot be driven by anything else on that network either.
3. Check the printers from outside, for instance with an open port scan on the public address of
   the location.

## The box

- No port forward to the box. It only makes outgoing connections.
- A management interface such as Cockpit belongs behind a rule that allows the local network only.
- The configuration file holds the token: readable only by the user that runs the agent.
- In CUPS, `Allow from` the local network only.

## What a token can do

A token can claim jobs, read their content and report them as printed or failed. It cannot create
jobs, read another administration's work or reach anything else in your application. A leaked
token therefore costs you the documents of that location until you rotate it, which is exactly why
the files are dropped as soon as a job is printed.
