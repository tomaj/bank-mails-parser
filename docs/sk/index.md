---
layout: home

hero:
  name: Bank Mails Parser
  text: Bankové notifikácie, spracované
  tagline: E-maily o platbách z Tatra banky, VÚB a ČSOB premení na typové PHP objekty. Suma v najmenších jednotkách, variabilný symbol, účty a dátum a jasná výnimka, keď sa e-mailu nedá veriť.
  image:
    src: /illustrations/hero-light.svg
    alt: Notifikácia Tatra banky sa spracuje na objekt MailContent so sumou 12.31 EUR a variabilným symbolom 1234056789
  actions:
    - theme: brand
      text: Začíname
      link: /sk/guide/getting-started
    - theme: alt
      text: Príklady
      link: /sk/examples/
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/bank-mails-parser

features:
  - icon:
      src: /icons/banks.svg
      alt: Banka
      width: 42px
      height: 42px
    title: Štyri banky, šesť formátov
    details: b-mail Tatra banky, výsledky CardPay a ComfortPay a PGP výpisy kariet, VÚB, ČSOB Slovensko a ČSOB Česká republika.
    link: /sk/guide/tatrabanka
    linkText: Príručky k bankám
  - icon:
      src: /icons/detection.svg
      alt: Obálka s lupou
      width: 42px
      height: 42px
    title: Rozpoznanie banky
    details: BankMailsParser vyberie parser podľa domény odosielateľa a tela e-mailu a odmietne hádať, keď sa zhodujú dva parsery.
    link: /sk/guide/bank-detection
    linkText: Ako rozpoznanie funguje
  - icon:
      src: /icons/money.svg
      alt: Mince
      width: 42px
      height: 42px
    title: Peniaze bez floatov
    details: Sumy sú celé čísla v najmenších jednotkách s menou podľa ISO 4217. Nejednoznačné oddeľovače ako 1.234 sa odmietnu, nikdy sa nehádajú.
    link: /sk/api/reference#money
    linkText: Money a Currency
  - icon:
      src: /icons/transactions.svg
      alt: Riadky transakcií
      width: 42px
      height: 42px
    title: Viac transakcií v jednom e-maile
    details: parseAll() vráti každú transakciu z e-mailu ČSOB alebo z výpisu a pri ČSOB overí reťaz zostatkov medzi nimi.
    link: /sk/examples/csob
    linkText: Príklad ČSOB
  - icon:
      src: /icons/pgp.svg
      alt: Zámok
      width: 42px
      height: 42px
    title: PGP výpisy kariet
    details: Odovzdajte fasáde dešifrátor so súkromným kľúčom a prečíta šifrovaný výpis kariet Tatra banky riadok po riadku.
    link: /sk/examples/encrypted-statement
    linkText: Príklad výpisu
  - icon:
      src: /icons/tested.svg
      alt: Štít so značkou
      width: 42px
      height: 42px
    title: Otestované na skutočných e-mailoch
    details: 92 anonymizovaných bankových e-mailov s očakávaným výsledkom, prípady podvrhnutých polí, 97 % pokrytie, 95 % mutačné skóre a každý príklad na tomto webe beží v CI.
    link: /sk/guide/testing
    linkText: Testovanie integrácie
---

<div class="home-more">

## Ako to vyzerá

Notifikácia b-mail z Tatra banky:

```text
16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava
```

Odovzdajte telo a odosielateľa, čítajte typové vlastnosti:

```php
use Tomaj\BankMailsParser\BankMailsParser;

$mail = new BankMailsParser()->parse($emailBody, 'b-mail@tatrabanka.sk');

echo $mail->money?->format(), "\n";                          // suma a mena
echo $mail->money?->amount, "\n";                            // celé číslo, najmenšie jednotky
echo $mail->vs, "\n";                                        // variabilný symbol
echo $mail->transactionDate?->format('Y-m-d H:i P'), "\n";   // Europe/Bratislava
echo $mail->accountNumber, "\n";
```

<!-- snippet: output -->
```text
12.31 EUR
1231
1234056789
2015-01-16 12:51 +01:00
SK9812353347235
```

## Kam ďalej

- [Začíname](/sk/guide/getting-started) nainštaluje balík a spracuje prvý e-mail.
- [Príklady](/sk/examples/) sú celé spustiteľné recepty: jeden pre každú banku, celá schránka spárovaná s objednávkami, šifrovaný výpis, ošetrenie chýb a vlastný parser.
- [Rozpoznanie banky](/sk/guide/bank-detection) vysvetľuje, ako fasáda vyberá parser a prečo treba vždy odovzdať odosielateľa.
- [Bezpečnosť](/sk/security) uvádza, čo knižnica kontroluje a čo musíte skontrolovať vy, kým zaúčtujete platbu.
- [Referencia API](/sk/api/reference) opisuje každú verejnú triedu a vlastnosť.

</div>
