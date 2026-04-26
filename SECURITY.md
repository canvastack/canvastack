# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 1.1.x   | ✅ Actively supported |
| 1.0.x   | ❌ End of life — critical vulnerabilities, upgrade immediately |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Email us at: **security@canvastack.com**

Include as much of the following as possible:
- Type of issue (XSS, SQL injection, path traversal, etc.)
- Full paths of source file(s) related to the issue
- Location of the affected source code (tag/branch/commit or direct URL)
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

We will acknowledge your report within **48 hours** and aim to provide a fix within **7 days** for critical issues.

## Security Features in v1.1.0

- XSS protection via automatic HTML escaping on all user input
- Multi-layer file upload validation (extension, MIME type, size, path traversal)
- SQL injection prevention with parameterized queries and encrypted AJAX payloads
- CSRF protection with automatic token generation and validation
- Path traversal protection using `realpath()` validation
- Dangerous attribute blocking (`onclick`, `onerror`, `onload`, etc.)
- Encryption integrity checking with HMAC for model names and AJAX queries
- Security event logging for audit trails

## Known Vulnerabilities (Fixed)

### v1.0.x — Please Upgrade

| Severity | Type | Fixed In |
|----------|------|----------|
| Critical | XSS in form elements (22 methods) | v1.1.0 |
| High | File upload validation bypass | v1.1.0 |
| Medium | Path traversal in file operations | v1.1.0 |
| Medium | Attribute injection | v1.1.0 |
| Medium | Model name tampering | v1.1.0 |
