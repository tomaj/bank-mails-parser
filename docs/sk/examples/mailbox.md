# Schránka s platbami

Bežná integrácia: schránka (alebo preposielanie pošty) dostáva notifikácie všetkých bánk, ktoré používate, a job prečíta nové e-maily, nájde banku a každú platbu spáruje s otvorenou objednávkou. Rozpoznanie robí `BankMailsParser`. Tento recept číta priečinok súborov `.eml`: štyri bankové e-maily, newsletter a notifikáciu Tatra banky odoslanú z podobne vyzerajúcej domény.

Zdroj: [`examples/mailbox`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/mailbox)

## Schránka

| Súbor | Od | Obsah |
|-------|----|-------|
| `01-tatra-banka.eml` | `b-mail@tatrabanka.sk` | notifikácia b-mail, 12.31 EUR |
| `02-cardpay.eml` | `cardpay@tatrabanka.sk` | výsledok CardPay, 33.99 EUR |
| `03-vub.eml` | `notifikacie@vub.sk` | platba VÚB, 13.37 EUR |
| `04-csob-sk.eml` | `info24@csob.sk` | SEPA platba ČSOB SK, 1150.00 EUR |
| `05-newsletter.eml` | `news@shop.example` | nie je bankový e-mail |
| `06-forged-sender.eml` | `b-mail@tatrabanka.sk.evil.example` | znova e-mail Tatra banky, z inej domény |

Adresy odosielateľov sú príklady; použite tie, z ktorých vaša banka naozaj posiela. Jeden z e-mailov aj s hlavičkami:

<<< @/../examples/mailbox/mailbox/01-tatra-banka.eml{text}

## Kód

<<< @/../examples/mailbox/run.php

## Výstup

<div class="example-output">

<<< @/../examples/mailbox/expected-output.txt{text}

</div>

## Ako fasáda rozhoduje

1. **Odosielateľ zúži kandidátov.** Každý parser uvádza domény svojej banky (`bankDomains()`). S odosielateľom sa pýtajú len parsery, ktorých doména sa zhoduje s hostiteľom odosielateľa (samotná doména alebo subdoména). `b-mail@tatrabanka.sk.evil.example` sa nezhoduje so žiadnou bankou, takže podvrhnutý e-mail je `UnsupportedBankException`, hoci jeho telo je dokonalá notifikácia Tatra banky.
2. **Telo vyberie parser.** `supports()` každého zostávajúceho parsera kontroluje vlastné riadky hlavičky banky. `tatrabanka.sk` má tri parsery (b-mail, CardPay, výpisy); rozhodne medzi nimi telo.
3. **Musí sa zhodovať práve jeden.** Žiadna zhoda je `UnsupportedBankException`, viac ako jedna je `AmbiguousBankException`. Fasáda nikdy neberie „prvý, ktorý sedí“.

<div class="diagram">
  <img class="diagram-light" src="/diagrams/parse-flow-sk-light.svg" alt="Postup BankMailsParser: parsery sa vyfiltrujú podľa domény odosielateľa, každého sa opýta supports(), pri žiadnej zhode vyhodí UnsupportedBankException, pri viacerých AmbiguousBankException, inak vráti výsledok parseAll() jediného parsera">
  <img class="diagram-dark" src="/diagrams/parse-flow-sk-dark.svg" alt="Postup BankMailsParser: parsery sa vyfiltrujú podľa domény odosielateľa, každého sa opýta supports(), pri žiadnej zhode vyhodí UnsupportedBankException, pri viacerých AmbiguousBankException, inak vráti výsledok parseAll() jediného parsera">
</div>

## Párovanie platieb s objednávkami

Recept spáruje transakciu s objednávkou, len keď sedí variabilný symbol **aj** suma, a výsledok CardPay iný ako `OK` považuje za nezaplatený. Platba ČSOB vyššie má správny symbol, ale zlú sumu, takže sa nahlási, namiesto toho aby sa zaúčtovala. Je to zámer:

- Variabilný symbol môže pochádzať zo správy, ktorú napísal platiteľ. Symbol vašej objednávky môže napísať ktokoľvek.
- Hlavičku From táto knižnica neoveruje. Skôr než odosielateľovi uveríte, skontrolujte výsledok DKIM/SPF e-mailu (váš poštový server ho pridá ako `Authentication-Results`) a overte podpisy CardPay. Pozrite si [Bezpečnosť](/sk/security).
- V cykle cez schránku `parseAll()`, nie `tryParse()`: e-mail ČSOB s dvoma transakciami spôsobí, že `parse()` vyhodí výnimku a `tryParse()` vráti `null`, a dve platby by ste potichu preskočili.
- E-mail, ktorý vyhodí `InvalidEmailFormatException`, si nechajte pre človeka. Je to bankový e-mail, ktorý parser nedokázal prečítať celý, a knižnica nehádá.

Čítanie skutočnej pošty: funkcia `readMail()` vyššie zvládne len textové správy. Na MIME e-maily (telá v base64 alebo quoted-printable, HTML časti) použite knižnicu ako `php-mime-mail-parser` alebo `zbateson/mail-mime-parser`, pre IMAP [tomaj/imap-mail-downloader](https://github.com/tomaj/imap-mail-downloader), a odovzdajte dekódovanú časť `text/plain`.
