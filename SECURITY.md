# Security policy

This package exists so printers do not have to be reachable from the internet, so a way past its
token check or a way to read another administration's jobs counts as a security issue.

## Supported versions

Only the latest minor release of 1.x receives security fixes. Upgrade before reporting.

## Reporting a vulnerability

Please do **not** open a public issue. Report it privately instead:

- via [GitHub private vulnerability reporting](https://github.com/ArvidDeJong/printserver/security/advisories/new), or
- by email to info@arvid.nl.

Include the package version, the Laravel version, and the request that gets past the token check.

You will get a reply within a week. Once a fix is released, the advisory is published and you are
credited, unless you prefer not to be.

## Out of scope

A token in the hands of someone else lets them claim jobs; keep it out of your repository and
rotate it when it leaks. Printers that are still reachable through a port forward are not covered
by this package: closing that forward is part of the install, described in
[Security](https://arviddejong.github.io/printserver/security.html).
