# Security Policy

## Supported Versions

We actively support and provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 4.x     | :white_check_mark: |
| 3.x     | :x:                |
| 2.x     | :x:                |
| < 2.0   | :x:                |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security vulnerability in this project, please follow these steps:

### Private Disclosure

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to the maintainer. Include the following information:

- Description of the vulnerability
- Steps to reproduce the issue
- Potential impact
- Any suggested fixes (if available)

### What to Expect

- **Acknowledgment**: You will receive an acknowledgment of your report within 48 hours
- **Assessment**: We will assess the vulnerability and determine its severity
- **Fix**: If confirmed, we will work on a fix and prepare a security release
- **Disclosure**: We will coordinate the public disclosure of the vulnerability

### Security Best Practices

When using this library:

1. **Keep Dependencies Updated**: Regularly update to the latest version
2. **Validate Input**: Always validate email content before parsing
3. **Sanitize Output**: Sanitize any data extracted from emails before use
4. **Use HTTPS**: Always use secure connections when downloading emails
5. **Audit Dependencies**: Regularly audit your dependencies for known vulnerabilities

## Security Features

This library includes several security features:

- **Strong Typing**: PHP 8.0+ strict types prevent many common vulnerabilities
- **Input Validation**: Robust email parsing with proper error handling  
- **No Code Execution**: The library only parses data, never executes code
- **Dependency Scanning**: Automated dependency vulnerability scanning in CI/CD

## Automated Security Scanning

This project uses automated security scanning tools:

- **Trivy**: Vulnerability scanning for dependencies and code
- **Composer Audit**: Check for known vulnerabilities in PHP dependencies
- **GitHub Security Advisories**: Automated monitoring for security issues