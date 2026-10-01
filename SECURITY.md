# Security Policy

## Supported Versions

We actively support the following versions with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 4.x     | :white_check_mark: |
| 3.x     | :x:                |
| 2.x     | :x:                |
| 1.x     | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability in this library, please report it responsibly:

### Private Disclosure

**DO NOT** open a public issue for security vulnerabilities.

Instead, please email security details to: **tomasmajer@gmail.com**

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Response Timeline

- **Initial Response**: Within 48 hours
- **Fix Timeline**: Critical issues within 7 days, others within 30 days
- **Public Disclosure**: After fix is released and users have time to update

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

## Security Update Policy

- **Critical vulnerabilities**: Emergency release within 48 hours
- **High severity**: Release within 7 days
- **Medium severity**: Included in next regular release
- **Low severity**: Documented and fixed in future versions

## Acknowledgments

We appreciate security researchers who responsibly disclose vulnerabilities. Contributors will be acknowledged in release notes (unless they prefer to remain anonymous).
