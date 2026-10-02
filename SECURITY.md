# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| Latest  | ✅        |

We support the latest released version of ShipReady. Patch releases are issued for verified security vulnerabilities.

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub Issues.**

To report a security vulnerability, email us at: **security@nivoin.dev**

Include:
- A description of the vulnerability and its impact
- Steps to reproduce
- The version of ShipReady affected
- Any suggested fix (optional)

We will acknowledge receipt within 48 hours and aim to issue a fix within 14 days for critical issues.

## Scope

ShipReady is a **static analysis tool** — it reads your codebase without executing application code, touching the database, or making HTTP requests (except `ship:probe` which is an explicit opt-in command). The primary security surface is:

- **Malicious composer packages in the analyzed app** — ShipReady reads PHP source; a crafted PHP file cannot execute arbitrary code during static analysis.
- **Baseline file tampering** — The `.ship-ready-baseline.json` file should be committed to source control and treated as a trusted artifact.
- **MCP server (`ship:mcp`)** — The MCP stdio server runs as a child process of your AI agent and inherits its permissions. It should only be exposed to trusted AI clients.

## Disclosure Policy

We follow [Responsible Disclosure](https://en.wikipedia.org/wiki/Responsible_disclosure). We will credit researchers who report valid vulnerabilities in our release notes unless they prefer to remain anonymous.
