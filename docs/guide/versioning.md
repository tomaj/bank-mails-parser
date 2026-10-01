# Versioning Documentation

This page explains how documentation versioning works for Bank Mails Parser.

## Current Version

The current stable version is **5.x**, which corresponds to version 5.0.0 and later releases.

## Documentation Versions

Documentation is version-specific and matches the library's major version:

- **Latest (5.x)** - Current stable documentation at `/bank-mails-parser/`
- **4.x** - Archived in `docs-archive/4.x` branch (link in version dropdown)
- **Archived versions** - Previous major versions at `/bank-mails-parser/{version}/`

### Version Selector

Use the version dropdown in the navigation bar to switch between documentation versions.

## Versioning Strategy

### Major Versions (X.0.0)

Major versions may include breaking changes:

- API changes
- Removed deprecated features
- Renamed methods or classes
- Changed behavior

See [CHANGELOG](/changelog) for migration guides.

### Minor Versions (4.X.0)

Minor versions add new features while maintaining backward compatibility:

- New bank parsers
- Additional fields in `MailContent`
- New parser methods
- Performance improvements

### Patch Versions (4.1.X)

Patch versions include bug fixes and minor improvements:

- Bug fixes
- Documentation improvements
- Test improvements
- Code quality enhancements

## Maintaining Documentation

### Updating Current Version

Documentation for the current version lives in the `master` branch under `docs/`.

To update:

1. Edit files in `docs/` directory
2. Test locally: `npm run docs:dev`
3. Build: `npm run docs:build`
4. Submit pull request

### Archiving Major Versions

When a new major version is released:

1. Create a `docs-archive/{previous-version}.x` branch from the last release of the previous major version
2. The GitHub Actions workflow automatically builds and deploys archived versions
3. Archived versions are accessible via version dropdown

Example: Version 5.0.0 was released, and `docs-archive/4.x` branch was created from commit before the 5.0 changes. When version 6.0.0 is released, create `docs-archive/5.x` branch from the last 5.x commit.

## Documentation Structure

```
docs/
├── .vitepress/
│   ├── config.mts          # VitePress configuration
│   └── theme/              # Custom theme
├── guide/
│   ├── getting-started.md  # Installation & quickstart
│   ├── examples.md         # Code examples
│   ├── tatrabanka.md       # Bank-specific guide
│   ├── csob-cz.md          # Bank-specific guide
│   ├── csob-sk.md          # Bank-specific guide
│   ├── vub.md              # Bank-specific guide
│   ├── adding-bank.md      # Developer guide
│   └── versioning.md       # This page
├── api/
│   └── reference.md        # Complete API reference
├── contributing.md         # Contributing guidelines
├── changelog.md            # Version history
└── index.md                # Homepage
```

## Building Documentation

### Local Development

```bash
cd docs
npm install
npm run docs:dev
```

Visit `http://localhost:5173/bank-mails-parser/`

### Production Build

```bash
cd docs
npm install
npm run docs:build
```

Output: `docs/.vitepress/dist/`

### Dead Link Check

```bash
npm run docs:build
# Check build output for warnings about dead links
```

## GitHub Pages Deployment

Documentation is automatically deployed to GitHub Pages via GitHub Actions when changes are pushed to:

- `master` branch (current version)
- `docs-archive/*` branches (archived versions)

### Deployment URL

**Base URL:** `https://tomaj.github.io/bank-mails-parser/`

- Current version: `/bank-mails-parser/`
- Coverage report: `/bank-mails-parser/coverage/`
- Archived 3.x: `/bank-mails-parser/3.x/` (when available)

## Coverage Reports

Code coverage reports are deployed alongside documentation at `/bank-mails-parser/coverage/`.

Coverage is generated from the `master` branch and updated on every push that runs tests.

## See Also

- [Contributing Guide](/contributing) - How to contribute
- [Changelog](/changelog) - Version history and migration guides
- [GitHub Releases](https://github.com/tomaj/bank-mails-parser/releases) - Release notes
