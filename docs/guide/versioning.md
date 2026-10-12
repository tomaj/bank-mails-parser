# Versioning Documentation

This page explains how documentation versioning works for Bank Mails Parser.

## Current Version

The current stable version is **5.x**, which corresponds to version 5.0.0 and later releases.

## Documentation Versions

Documentation is version-specific and matches the library's major version:

- **Latest (5.x)** - Current stable documentation at `/bank-mails-parser/`
- **4.x** - Archived documentation at `/bank-mails-parser/4.x/`

Both versions are built and deployed together from the `master` branch.

### Version Selector

Use the version dropdown in the navigation bar to switch between documentation versions. The dropdown shows:
- **5.x (latest)** - Immutable API with direct property access
- **4.x** - Mutable API with getters/setters

The same dropdown is on Slovak pages. From a Slovak page, 4.x opens the Slovak archive at `/bank-mails-parser/sk/4.x/`.

## Languages

English is the default locale, served at the site root. Slovak pages live in `docs/sk/` and are served at `/bank-mails-parser/sk/` (5.x) and `/bank-mails-parser/sk/4.x/` (archive). The **EN / SK** control in the navigation bar opens the same page in the other language. The version menu stays in the current language.

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

Documentation for the current version lives in the `master` branch under `docs/`. The Slovak text lives in `docs/sk/`.

To update:

1. Edit the English files in `docs/` and the matching files in `docs/sk/`
2. Test locally: `npm run docs:dev`
3. Build: `npm run docs:build`
4. Submit pull request

### Archiving Major Versions

When a new major version is released:

1. Copy the current version documentation to a versioned subdirectory (e.g., `docs/4.x/`)
2. Adapt the archived version content to match that version's API
3. Update `.vitepress/config.mts` and `site-nav.ts` to configure path-based sidebars for each version
4. Add version dropdown navigation items
5. Keep the Slovak translation of the current version in `docs/sk/`

Example: Version 5.0.0 was released with breaking API changes. The 4.x documentation was preserved in `docs/4.x/` and adapted to show the 4.x API (getters/setters). When version 6.0.0 is released, create `docs/5.x/` directory with the 5.x documentation.

## Documentation Structure

```
docs/
├── .vitepress/
│   ├── config.mts          # VitePress configuration and locales
│   └── theme/              # Custom theme
├── site-nav.ts             # Navigation, version menu, and sidebars
├── 4.x/                    # Version 4.x documentation (archived, English)
│   ├── index.md            # 4.x homepage
│   ├── guide/
│   │   ├── getting-started.md
│   │   ├── examples.md
│   │   ├── tatrabanka.md
│   │   ├── csob-cz.md
│   │   ├── csob-sk.md
│   │   ├── vub.md
│   │   └── adding-bank.md
│   └── api/
│       └── reference.md    # 4.x API (getters/setters)
├── sk/                     # Slovak documentation
│   ├── index.md            # Slovak 5.x homepage
│   ├── 4.x/                # Slovak 4.x archive
│   ├── guide/
│   ├── api/
│   ├── contributing.md
│   └── changelog.md
├── guide/                  # Version 5.x documentation (current, English)
│   ├── getting-started.md  # Installation & quickstart
│   ├── examples.md         # Code examples
│   ├── tatrabanka.md       # Bank-specific guide
│   ├── csob-cz.md          # Bank-specific guide
│   ├── csob-sk.md          # Bank-specific guide
│   ├── vub.md              # Bank-specific guide
│   ├── bank-detection.md   # Bank detection
│   ├── exceptions.md       # Exceptions
│   ├── adding-bank.md      # Developer guide
│   └── versioning.md       # This page
├── api/
│   └── reference.md        # Complete API reference (5.x immutable API)
├── contributing.md         # Contributing guidelines
├── changelog.md            # Version history
└── index.md                # Homepage (5.x)
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
npm run docs:links
```

The command builds the site, checks that SVG files are well-formed XML, and crawls the result for broken links. The same check runs on pull requests, including the Slovak pages.

## GitHub Pages Deployment

Documentation is automatically deployed to GitHub Pages via GitHub Actions when changes are pushed to the `master` branch.

### Deployment URL

**Base URL:** `https://tomaj.github.io/bank-mails-parser/`

- Current version (5.x, English): `/bank-mails-parser/`
- Current version (5.x, Slovak): `/bank-mails-parser/sk/`
- Archived 4.x (English): `/bank-mails-parser/4.x/`
- Archived 4.x (Slovak): `/bank-mails-parser/sk/4.x/`
- Coverage report: `/bank-mails-parser/coverage/`

All versions are built and deployed together in a single build process.

## Coverage Reports

Code coverage reports are deployed alongside documentation at `/bank-mails-parser/coverage/`.

Coverage is generated from the `master` branch and updated on every push that runs tests.

## See Also

- [Contributing Guide](/contributing) - How to contribute
- [Changelog](/changelog) - Version history and migration guides
- [GitHub Releases](https://github.com/tomaj/bank-mails-parser/releases) - Release notes
