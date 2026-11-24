#!/usr/bin/env python3
"""Checks that the docs and examples/ stay in sync.

- Every `<<< @/...` import points at an existing file.
- Every recipe in examples/ (except ci/) has run.php, e2e.sh and expected-output.txt, and its
  run.php and expected-output.txt are imported by an English and a Slovak page.
- Every English page outside the 4.x archive has a Slovak page at the same path under sk/.

PHP blocks written inline are run by tests/DocsSnippetsTest.php.
"""
import re
import sys
from pathlib import Path

docs = Path(__file__).resolve().parent.parent
examples = docs.parent / "examples"
errors = []
imported = {"en": set(), "sk": set()}

for page in sorted(docs.rglob("*.md")):
    parts = page.relative_to(docs).parts
    if "node_modules" in parts or ".vitepress" in parts:
        continue
    locale = "sk" if parts[0] == "sk" else "en"
    for target in re.findall(r"^<<< @/(\S+?)(?:\{[^}]*\})?(?:\s|$)", page.read_text(encoding="utf-8"), re.M):
        path = (docs / target.split("#", 1)[0]).resolve()
        if not path.is_file():
            errors.append(f"{page.relative_to(docs)}: imported file {target} does not exist")
        imported[locale].add(path)

for recipe in sorted(p for p in examples.iterdir() if p.is_dir() and p.name != "ci"):
    for name in ("run.php", "e2e.sh", "expected-output.txt", "composer.json"):
        if not (recipe / name).is_file():
            errors.append(f"examples/{recipe.name}: {name} is missing")
    for name in ("run.php", "expected-output.txt"):
        for locale in ("en", "sk"):
            if (recipe / name).resolve() not in imported[locale]:
                errors.append(f"examples/{recipe.name}/{name} is not imported by any {locale} page")

for page in sorted(docs.rglob("*.md")):
    relative = page.relative_to(docs)
    parts = relative.parts
    if parts[0] in ("sk", "node_modules", "4.x") or ".vitepress" in parts:
        continue
    if not (docs / "sk" / relative).is_file():
        errors.append(f"{relative} has no Slovak page sk/{relative}")

for error in errors:
    print(error, file=sys.stderr)
if errors:
    sys.exit(1)
print(f"ok {len(imported['en'])} files imported by English pages, {len(imported['sk'])} by Slovak pages")
