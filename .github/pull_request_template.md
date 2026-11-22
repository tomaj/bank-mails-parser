## Description

<!-- What does this change, and why? Link the issue: "Closes #123". -->

## Type of change

- [ ] Bug fix (non-breaking)
- [ ] New feature (non-breaking)
- [ ] New bank parser
- [ ] Breaking change (target the next major version)
- [ ] Documentation

## Testing

- [ ] `composer check` (code style, PHPStan, Rector, PHPUnit)
- [ ] `composer infection`
- [ ] New or changed mails are in `tests/fixtures/<bank>/` with a reviewed `.expected.json` (`composer fixtures:update`)

<!-- Note anything CI cannot cover, such as a real bank mail you could not include. -->

## Documentation

- [ ] `CHANGELOG.md` updated for a user-visible change
- [ ] Docs (`docs/`, English and Slovak) or `README.md` updated, if the public usage changed
- [ ] No documentation update is needed

## Checklist

- [ ] I have read [CONTRIBUTING.md](../CONTRIBUTING.md)
- [ ] Tests cover the change
- [ ] Test mails contain no real personal data
- [ ] This does not include a security report that belongs in [private vulnerability reporting](https://github.com/tomaj/bank-mails-parser/security/advisories/new)
