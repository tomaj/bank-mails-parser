# Vlastný parser

Banka, ktorú knižnica zatiaľ nepodporuje, potrebuje triedu, ktorá implementuje `ParserInterface`. Zaregistrujte ju na fasáde a zúčastní sa rozpoznávania rovnako ako vstavané parsery. Tento recept pridá parser pre vymyslenú „Example banka“ v mennom priestore aplikácie.

Zdroj: [`examples/custom-parser`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/custom-parser)

## E-mail

<<< @/../examples/custom-parser/mails/example-bank.txt{text}

## Parser

<<< @/../examples/custom-parser/src/ExampleBankMailParser.php

`ParserInterface` vyžaduje tri metódy:

| Metóda | Zmluva |
|--------|--------|
| `bankDomains(): list<string>` | Hostitelia, z ktorých banka posiela. S odosielateľom sa fasáda tohto parsera opýta, len keď je hostiteľ odosielateľa jedným z nich alebo jeho subdoménou. Prázdny zoznam znamená, že parser sa použije len vtedy, keď sa odosielateľ neodovzdá |
| `supports($content, $sender, $subject): bool` | Či je telo e-mailom tejto banky. Hľadajte riadok, ktorý píše len banka, ukotvený na začiatku riadku, a nevyhadzujte výnimky |
| `parseAll($content): list<MailContent>` | Každá transakcia. Keď je e-mail rozpoznaný, ale nedá sa prečítať, vyhoďte `InvalidEmailFormatException` |

Trait `ParsesBankMail` poskytuje `parseAll()` (jednu transakciu z vášho `parse()`), `tryParse()`, prázdne `bankDomains()` a `money()`, ktoré zmení neplatnú sumu na `InvalidEmailFormatException`, namiesto toho aby unikla `InvalidArgumentException`. Jeho ostatné metódy sú interné.

## Kód

<<< @/../examples/custom-parser/run.php

## Výstup

<div class="example-output">

<<< @/../examples/custom-parser/expected-output.txt{text}

</div>

## Poznámky

- `registerParser()` pridá parser na koniec, `prependParser()` na začiatok. Keď sa zhoduje len jeden parser, poradie nič nerozhoduje; je to poradie zoznamu, ktorý vráti `getParsers()`.
- `new BankMailsParser([$parser])` použije len parsery, ktoré odovzdáte; `new BankMailsParser()` (alebo `null`) zaregistruje vstavané.
- Ak váš `supports()` prijme aj e-mail inej banky, fasáda preň vyhodí `AmbiguousBankException`. Kontrolu urobte špecifickú a odovzdávajte odosielateľa, aby sa najprv uplatnil filter domén.
- Stojí za zdieľanie? [Pridanie novej banky](/sk/guide/adding-bank) opisuje, ako prispieť parserom do knižnice, s fixtures a testami.
