# Príklady

Každý recept na týchto stránkach je malý Composer projekt v adresári [`examples/`](https://github.com/tomaj/bank-mails-parser/tree/master/examples) repozitára. Spracuje anonymizované bankové e-maily uložené vedľa kódu a vypíše výsledok. Stránky vkladajú kód aj e-maily priamo z týchto súborov a [workflow Examples](https://github.com/tomaj/bank-mails-parser/blob/master/.github/workflows/examples.yml) spustí každý recept pri každom pull requeste a porovná jeho výstup s `expected-output.txt`, ktorý vidíte tu. Netreba sieť, IMAP server ani bankový účet.

| Recept | Čo ukazuje |
|--------|------------|
| [b-mail Tatra banky](./tatra-banka) | Notifikácie o prichádzajúcej a odchádzajúcej platbe, štruktúrovaná referencia veriteľa, `Money` v najmenších jednotkách |
| [CardPay a ComfortPay](./cardpay) | Výsledok platby kartou vo formáte `KEY=value`: `res`, `tres`, `cid`, `cc`, `sign`, čas v UTC |
| [VÚB](./vub) | Notifikácia VÚB: účet platiteľa a váš účet, `VS`, `ŠS`, `KS` |
| [ČSOB SK a CZ](./csob) | ČSOB Slovensko a ČSOB Česká republika, e-mail s dvoma transakciami a `parseAll()` |
| [Schránka s platbami](./mailbox) | Rozpoznanie banky podľa odosielateľa a tela pre priečinok súborov `.eml`, párovanie platieb s objednávkami, preskočenie cudzích a podvrhnutých odosielateľov |
| [Šifrovaný výpis kariet](./encrypted-statement) | Dešifrovanie PGP výpisu súkromným kľúčom a čítanie každého riadku |
| [Ošetrenie chýb](./error-handling) | `parse()`, `tryParse()` a `parseAll()` vedľa seba na e-mailoch, ktoré sa nedajú prečítať, a `Money`, ktoré odmietne nejednoznačné sumy |
| [Vlastný parser](./custom-parser) | Parser pre banku, ktorú knižnica nepozná, zaregistrovaný vedľa vstavaných |

## Spustenie receptu

Potrebujete PHP 8.4 alebo novšie s `mbstring` a Composer.

```bash
git clone https://github.com/tomaj/bank-mails-parser.git
cd bank-mails-parser/examples/tatra-banka
composer install
php run.php     # vypíše spracované e-maily
./e2e.sh        # spustí run.php a porovná výstup s expected-output.txt
```

`composer install` vezme `tomaj/bank-mails-parser` z Packagistu. Ak chcete recept spustiť proti lokálnej kópii repozitára, ako to robí CI, spustite z koreňa repozitára `examples/ci/recipe.sh tatra-banka` (alebo `examples/ci/recipe.sh` pre všetky). Skript skopíruje recept do dočasného adresára a Composer nasmeruje na kópiu repozitára cez path repository.

## E-maily

E-maily sú kópie fixtures z testov samotnej knižnice (`tests/fixtures/`), kde sa každý parser porovnáva s očakávaným výsledkom. Zachovávajú rozloženie, medzery a diakritiku banky, na ktorých parsery závisia. Keď pridávate banku alebo nový tvar e-mailu, najužitočnejším príspevkom je skutočný (anonymizovaný) e-mail; pozrite si [Ako prispieť](/sk/contributing).
