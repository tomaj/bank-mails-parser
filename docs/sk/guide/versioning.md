# Verzie dokumentácie

Táto stránka vysvetľuje, ako sa verzuje dokumentácia Bank Mails Parser.

## Aktuálna verzia

Aktuálna stabilná verzia je **5.x**. Zodpovedá vydaniu 5.0.0 a novším.

## Verzie dokumentácie

Dokumentácia je viazaná na hlavnú verziu knižnice:

- **Aktuálna (5.x)** – stabilná dokumentácia na `/bank-mails-parser/`
- **4.x** – archív na `/bank-mails-parser/4.x/` (anglicky) a `/bank-mails-parser/sk/4.x/` (slovensky)

Obe verzie sa zostavujú a nasadzujú spolu z vetvy `master`.

### Prepínač verzií

Medzi verziami dokumentácie prepína rozbaľovacia ponuka v navigácii. Ukazuje:

- **5.x (aktuálna)** – nemenné API s priamym prístupom k vlastnostiam
- **4.x** – meniteľné API s gettermami a settermami

Ponuka je v anglickej aj slovenskej dokumentácii a ostáva v aktuálnom jazyku. Zo slovenskej stránky vedie položka 4.x na `/bank-mails-parser/sk/4.x/`.

## Jazyky

Angličtina je predvolený jazyk na koreňovej adrese. Slovenské stránky sú v `docs/sk/` a servírujú sa na `/bank-mails-parser/sk/` (5.x) a `/bank-mails-parser/sk/4.x/` (archív). Prepínač **EN / SK** v navigácii otvorí tú istú stránku v druhom jazyku. Prepínač verzií ostáva v aktuálnom jazyku.

## Stratégia verzovania

### Hlavné verzie (X.0.0)

Hlavná verzia môže obsahovať zlomové zmeny:

- zmeny API
- odstránenie zastaraných funkcií
- premenovanie metód alebo tried
- zmena správania

Postup prechodu je v [zozname zmien](/sk/changelog).

### Vedľajšie verzie (4.X.0)

Vedľajšia verzia pridáva funkcie a zachováva spätnú kompatibilitu:

- nové bankové parsery
- ďalšie polia v `MailContent`
- nové metódy parserov
- zrýchlenie

### Opravné verzie (4.1.X)

Opravná verzia obsahuje opravy chýb a drobné vylepšenia:

- opravy chýb
- úpravy dokumentácie
- úpravy testov
- vyššia kvalita kódu

## Údržba dokumentácie

### Aktualizácia aktuálnej verzie

Dokumentácia aktuálnej verzie je vo vetve `master` v adresári `docs/`. Slovenský text je v `docs/sk/`.

Postup:

1. Upravte anglické súbory v `docs/` a zodpovedajúce súbory v `docs/sk/`
2. Otestujte lokálne: `npm run docs:dev`
3. Zostavte: `npm run docs:build`
4. Otvorte pull request

### Archivácia hlavnej verzie

Keď vyjde nová hlavná verzia:

1. Skopírujte dokumentáciu aktuálnej verzie do verziovaného podadresára (napríklad `docs/4.x/`)
2. Archív upravte tak, aby zodpovedal API tej verzie
3. V `.vitepress/config.mts` nastavte bočné menu podľa cesty pre každú verziu
4. Doplňte položky prepínača verzií
5. Slovenský preklad aktuálnej verzie nechajte v `docs/sk/`

Príklad: Verzia 5.0.0 vyšla so zlomovými zmenami API. Dokumentácia 4.x ostala v `docs/4.x/` a bola upravená na API 4.x (gettre a settre). Keď vyjde verzia 6.0.0, vytvorte adresár `docs/5.x/` s dokumentáciou 5.x.

## Štruktúra dokumentácie

```
docs/
├── .vitepress/
│   ├── config.mts          # Konfigurácia VitePress a jazykové verzie
│   └── theme/              # Vlastná téma
├── site-nav.ts             # Navigácia, prepínač verzií a bočné menu
├── 4.x/                    # Dokumentácia 4.x (archív, anglicky)
│   ├── index.md            # Úvodná stránka 4.x
│   ├── guide/
│   │   ├── getting-started.md
│   │   ├── examples.md
│   │   ├── tatrabanka.md
│   │   ├── csob-cz.md
│   │   ├── csob-sk.md
│   │   ├── vub.md
│   │   └── adding-bank.md
│   └── api/
│       └── reference.md    # API 4.x (gettre a settre)
├── sk/                     # Slovenská dokumentácia 5.x
│   ├── index.md            # Slovenská úvodná stránka
│   ├── 4.x/                # Slovenský archív 4.x
│   ├── guide/
│   ├── api/
│   ├── contributing.md
│   └── changelog.md
├── guide/                  # Dokumentácia 5.x (aktuálna, anglicky)
│   ├── getting-started.md  # Inštalácia a rýchly štart
│   ├── examples.md         # Príklady kódu
│   ├── tatrabanka.md       # Príručka k banke
│   ├── csob-cz.md          # Príručka k banke
│   ├── csob-sk.md          # Príručka k banke
│   ├── vub.md              # Príručka k banke
│   ├── bank-detection.md   # Rozpoznanie banky
│   ├── exceptions.md       # Výnimky
│   ├── adding-bank.md      # Návod pre vývojárov
│   └── versioning.md       # Táto stránka
├── api/
│   └── reference.md        # Úplná referencia API (nemenné API 5.x)
├── contributing.md         # Ako prispieť
├── changelog.md            # História verzií
└── index.md                # Úvodná stránka (5.x)
```

## Zostavenie dokumentácie

### Lokálny vývoj

```bash
cd docs
npm install
npm run docs:dev
```

Otvorte `http://localhost:5173/bank-mails-parser/`

### Produkčné zostavenie

```bash
cd docs
npm install
npm run docs:build
```

Výstup: `docs/.vitepress/dist/`

### Kontrola odkazov

```bash
npm run docs:links
```

Príkaz zostaví stránku, skontroluje, že súbory SVG sú správne XML, a prejde výsledok kvôli nefunkčným odkazom. Tá istá kontrola beží pri pull requestoch, aj pre slovenské stránky.

## Nasadenie na GitHub Pages

Dokumentácia sa na GitHub Pages nasadí automaticky cez GitHub Actions, keď sa zmeny pushnú do vetvy `master`.

### Adresa nasadenia

**Základná adresa:** `https://tomaj.github.io/bank-mails-parser/`

- Aktuálna verzia (5.x, anglicky): `/bank-mails-parser/`
- Aktuálna verzia (5.x, slovensky): `/bank-mails-parser/sk/`
- Archív 4.x (anglicky): `/bank-mails-parser/4.x/`
- Archív 4.x (slovensky): `/bank-mails-parser/sk/4.x/`
- Report pokrytia: `/bank-mails-parser/coverage/`

Všetky verzie sa zostavujú a nasadzujú spolu v jednom procese.

## Reporty pokrytia

Reporty pokrytia kódu sa nasadzujú spolu s dokumentáciou na `/bank-mails-parser/coverage/`.

Pokrytie sa generuje z vetvy `master` a aktualizuje sa pri každom pushi, ktorý spustí testy.

## Pozri tiež

- [Ako prispieť](/sk/contributing)
- [Zoznam zmien](/sk/changelog) – história verzií a postup prechodu
- [Vydania na GitHube](https://github.com/tomaj/bank-mails-parser/releases)
