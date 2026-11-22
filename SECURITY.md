# Security Policy

## Supported Versions

Security fixes are provided for the lines below.

| Version | Supported |
| ------- | --------- |
| 5.x     | Yes: bug fixes and security fixes |
| 4.x     | Security fixes only, until 2027-04-12 |
| < 4.0   | No |

5.x is the current stable line. 4.x gets fixes for security vulnerabilities only, for six months after the 5.0.0 release (until 2027-04-12); there is no 4.x maintenance branch, so a fix is released from one created from the `4.1.0` tag. Upgrade to 5.x with [UPGRADE-5.0.md](UPGRADE-5.0.md). Versions older than 4.0 do not receive security updates.

## Reporting a Vulnerability

If you discover a security vulnerability in this library, please report it responsibly:

### Private Disclosure

**DO NOT** open a public issue for security vulnerabilities.

The preferred way to report a vulnerability is [GitHub Private vulnerability reporting](https://github.com/tomaj/bank-mails-parser/security/advisories/new).

As a backup, email security details to: **tomasmajer@gmail.com**

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Response

You will receive an acknowledgement within 14 days. We then confirm the issue, agree on a disclosure date with you, and keep you informed while the fix is prepared.

### Coordinated disclosure

We follow coordinated disclosure. Please give us time to prepare a fix before discussing the issue in public. When the fix is released, we publish a GitHub Security Advisory and record the fix in the CHANGELOG with a `**Security**:` entry.

## Security Best Practices

When using this library:

1. **PGP Keys**: Store PGP private keys outside web root
2. **Credentials**: Use environment variables for sensitive configuration
3. **Validation**: Always validate extracted amounts before processing payments
4. **Error Messages**: Never expose raw email content in error messages
5. **Logging**: Log parsing failures for security monitoring
6. **Updates**: Keep the library and dependencies up to date

## Known Security Considerations

### Email Content

This library parses email content that may contain:
- Financial transaction data
- Account numbers (IBAN)
- Banking symbols (VS, KS, SS)

Always:
- Validate parsed data before use
- Sanitize output when displaying to users
- Store processed data securely
- Comply with GDPR and local data protection regulations

### PGP Decryption

The `TatraBankaStatementMailParser` decrypts PGP-encrypted emails:
- Ensure private keys are stored securely
- Use strong passphrases
- Rotate keys regularly
- Restrict file system access to key files

## Acknowledgments

We appreciate security researchers who responsibly disclose vulnerabilities. Contributors will be acknowledged in release notes (unless they prefer to remain anonymous).
