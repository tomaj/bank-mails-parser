## Description

<!-- Provide a clear and concise description of your changes -->

## Type of Change

<!-- Put an 'x' in all boxes that apply -->

- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update
- [ ] Code quality improvement (refactoring, style, tests)
- [ ] New bank parser

## Changes Made

<!-- List the specific changes made in this PR -->

- 
- 
- 

## Related Issues

<!-- Link any related issues -->

Closes #
Fixes #
Related to #

## Testing

<!-- Describe how you tested your changes -->

### Test Coverage

- [ ] Added tests for new functionality
- [ ] All tests pass locally
- [ ] Code coverage did not decrease

### Test Cases

<!-- List specific test scenarios covered -->

- 
- 
- 

## For New Bank Parsers

<!-- Fill this section if you're adding a new bank parser -->

### Bank Information

- **Bank Name**: 
- **Country**: 
- **Email Format**: Plain text / HTML / PGP encrypted

### Email Format Details

<!-- Describe the structure of the bank's emails -->

### Test Data

- [ ] Added anonymized test email samples
- [ ] Added test data README
- [ ] Verified all personal data removed

## Quality Checks

<!-- Confirm all quality checks pass -->

- [ ] `composer check` passes (code style, PHPStan, tests)
- [ ] `composer infection` passes (MSI ≥ 90%)
- [ ] All CI checks pass
- [ ] No PHPStan errors (level max)
- [ ] Code style (PSR-12) compliant

## Documentation

<!-- Confirm documentation is updated -->

- [ ] README.md updated (if applicable)
- [ ] CHANGELOG.md updated
- [ ] Code comments added/updated
- [ ] CONTRIBUTING.md updated (if process changed)

## Breaking Changes

<!-- If this is a breaking change, describe what breaks and how to migrate -->

### What Breaks

<!-- Describe what existing functionality is affected -->

### Migration Guide

<!-- Provide step-by-step instructions for upgrading -->

```php
// Before
$result = $parser->oldMethod();

// After
$result = $parser->newMethod();
```

## Additional Notes

<!-- Any additional information, context, or screenshots -->

---

## Checklist

<!-- Put an 'x' in all boxes that apply before submitting -->

- [ ] I have read the [CONTRIBUTING.md](../CONTRIBUTING.md) guidelines
- [ ] My code follows the PSR-12 style guidelines
- [ ] I have performed a self-review of my code
- [ ] I have commented my code where necessary
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix/feature works
- [ ] New and existing tests pass locally
- [ ] I have removed all personal data from test samples
- [ ] I have updated the documentation accordingly
