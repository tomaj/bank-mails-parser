# Zoznam zmien

V tomto súbore sú podstatné zmeny projektu.

Formát vychádza z [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
a projekt dodržiava [sémantické verziovanie](https://semver.org/spec/v2.0.0.html).

## [5.0.0] - 2026-10-12

Poznámky k 5.0.0 sú v [CHANGELOG.md](https://github.com/tomaj/bank-mails-parser/blob/master/CHANGELOG.md) v repozitári. Zhrnutie:

- `MailContent::$money` je `?Money` (`amount` v najmenších jednotkách, `toDecimalString()` pre desatinný reťazec).
- `transactionDate` je `?DateTimeImmutable`.
- `parse()` vyhodí výnimku. Viac ako jedna transakcia vyhodí `MultipleTransactionsException`; použite `parseAll()`. `tryParse()` vráti `null`.
- `BankMailsParser` rozpozná banku. Odovzdajte odosielateľa. Parser PGP výpisov sa zaregistruje len vtedy, keď je odovzdaný dešifrátor. Pozrite si [Rozpoznanie banky](/sk/guide/bank-detection) a [Výnimky](/sk/guide/exceptions).

## [4.1.0] - 2026-05-19

### Pridané
- Testovacie dáta VUB z pôvodného repozitára

### Zmenené
- **Parser TatraBanky**: explicitné formáty dátumu, aby sa nové varianty dátumu v e-mailoch TatraBanky čítali predvídateľne (`strtotime` ostal ako záloha) ([#20](https://github.com/tomaj/bank-mails-parser/pull/20))

## [4.0.0] - 2025-08-29

### Pridané
- **VubMailParser** pre e-maily banky VUB
- Testy a dokumentácia parsera VUB
- Rozsiahle vylepšenia dokumentácie v README
- Prehľadná tabuľka podporovaných bánk s vlajkami krajín
- Návod Rýchly štart a dokumentácia spracovania chýb
- Úplná dokumentácia API MailContent so všetkými gettermami
- Bezpečnostné zásady a návod, ako prispieť
- **Širšie pokrytie testami** – pribudlo viac ako 15 testov hraničných prípadov a chýb
- **MailContentTest** – samostatná testovacia trieda pre hraničné prípady MailContent
- **Testy odolnosti parserov** – neplatný vstup, prázdny obsah, poškodené dáta
- **Podpora atribútov PHP 8** – súčasné atribúty #[Test] a #[CoversClass]
- Report pokrytia na GitHub Pages s HTML reportmi
- Automatická aktualizácia odznaku pokrytia v README
- Odznak GitHub Actions CI

### Zmenené
- **Zlomová zmena**: minimálna verzia PHP stúpla zo 7.4 na 8.2
- **Zlomová zmena**: názvy testovacích metód – predpona „test“ sa odstránila, namiesto nej sú atribúty #[Test]
- **Zjednotenie štruktúry adresárov** – `src/Parsers/` sa presunulo do `src/Parser/`, aby sedelo na namespace
- **Štruktúra adresára testov** – `tests/Parsers/` sa presunulo do `tests/Parser/`
- **Modernizácia testov** – anotácie @test nahradili atribúty PHP 8 #[Test]
- Všetky GitHub Actions sa aktualizovali na v4, aby zmizli upozornenia na zastarané verzie
- Lepšia konfigurácia PHPUnit so správnymi filtrami pokrytia
- PHPUnit sa aktualizoval z 9.6.25 na 11.5.35 (vtedy aktuálna verzia)
- CI testuje už len PHP 8.2, 8.3 a 8.4
- **Odstránený nepoužívaný Makefile** – GitHub Actions volajú príkazy z vendor/bin priamo

### Odstránené
- Odznaky Code Climate (služba skončila)
- **Zlomová zmena**: podpora PHP 7.4, 8.0 a 8.1
- **Zlomová zmena**: anotácie @test v docblocku (nahradené atribútmi #[Test])

## [3.0.0] - 2025-08-28

### Pridané
- **CsobMailParser** pre e-maily českej ČSOB s podporou viacerých transakcií
- **SkCsobMailParser** pre e-maily slovenskej ČSOB
- **TatraBankaStatementMailParser** pre šifrované PGP výpisy
- Pomocná trieda **TatraBankaMailDecryptor** na dešifrovanie PGP
- Dôkladné testy všetkých nových parserov
- Závislosť `singpolyma/openpgp-php` pre funkcie PGP
- README s príkladmi použitia všetkých parserov
- Workflow GitHub Actions CI/CD s linterom (PHP_CodeSniffer), statickou analýzou (PHPStan) a testami (PHPUnit)
- Konfigurácia nástroja PHPStan

### Zmenené
- Podpora sa rozšírila z TatraBanky na viac bánk
- Opis projektu zodpovedá podpore viacerých bánk
- **Zlomová zmena**: minimálna verzia PHP stúpla zo 7.2 na 7.4

### Odstránené
- Konfigurácia Travis CI (nahradili ju GitHub Actions)

## [2.8.0] - 2021-12-01

### Pridané
- Podpora čísla zdrojového účtu ([#12](https://github.com/tomaj/bank-mails-parser/pull/12))

## [2.7.0] - 2021-09-15

### Pridané
- Čítanie variabilného symbolu aj z ďalších neplatných formátov ([#11](https://github.com/tomaj/bank-mails-parser/pull/11))
- Lepšie hľadanie variabilného symbolu v správe pre príjemcu a v účele platby

### Opravené
- Názvy testov a odstránené číslovanie vzorov, aby sa text lepšie čítal
- Čítanie variabilného symbolu z formátu správy pre príjemcu

## [2.6.0] - 2020-05-20

### Pridané
- Orezanie prečítaných hodnôt o biele znaky
- Spoľahlivejšie čítanie variabilného symbolu

### Opravené
- Test variabilného symbolu v správe pre príjemcu
- Odstránené nepoužívané premenné

## [2.5.0] - 2019-08-15

### Pridané
- **Spracovanie parametra RC** v e-mailoch ComfortPay ([#9](https://github.com/tomaj/bank-mails-parser/pull/9))

### Zmenené
- Aktualizované minimálne požiadavky na verziu PHP
- Lepšia konfigurácia zostavenia Travis CI

## [2.4.0] - 2019-06-10

### Pridané
- **Spracovanie parametra TXN** v e-mailoch ComfortPay ([#8](https://github.com/tomaj/bank-mails-parser/pull/8))
- Dokumentácia parametra TXN v README

## [2.3.0] - 2019-03-20

### Opravené
- **Regulárny výraz CardPay HMAC** pre voliteľné polia ([#7](https://github.com/tomaj/bank-mails-parser/pull/7))
- Lepšia podpora e-mailov o neúspešnej platbe ([#6](https://github.com/tomaj/bank-mails-parser/pull/6))

### Zmenené
- Z Travis CI sa odstránilo PHP 5.4 a 5.5
- Aktualizovaná konfigurácia zostavenia

## [2.2.0] - 2018-12-01

### Pridané
- **Podpora poľa description** ([#5](https://github.com/tomaj/bank-mails-parser/pull/5))
- Širšie možnosti spracovania e-mailov

### Opravené
- Úpravy štýlu kódu
- Aktualizovaná dokumentácia v README

## [2.1.0] - 2018-08-15

### Pridané
- Podpora **parametra CC** v potvrdzujúcich e-mailoch HMAC ([#4](https://github.com/tomaj/bank-mails-parser/pull/4))
- Parameter CC v e-mailoch HMAC je voliteľný

### Opravené
- Čítanie variabilného symbolu zo správy pre príjemcu ([#2](https://github.com/tomaj/bank-mails-parser/pull/2))
- Lepšie hľadanie VS v celom obsahu správy

## [2.0.0] - 2018-05-01

### Pridané
- Podpora **prísnych typov** (`declare(strict_types=1)`)
- Požiadavka **PHP 7.1+**
- Širšia podpora potvrdzujúcich e-mailov **TatraBanka HMAC**
- **Parameter AC** pre e-maily TatraBanky
- **Dátum transakcie** v jednoduchom parseri e-mailov
- Spracovanie **e-mailov ComfortPay**

### Zmenené
- **Zlomová zmena:** celý kód TatraBanky sa presunul do menného priestoru `TatraBanka`
- **Zlomová zmena:** `ParserInterface` už nevracia `false`, len `?MailContent`
- **Zlomová zmena:** minimálna verzia PHP stúpla na 7.1
- PHPUnit sa aktualizoval na verziu 9
- Kód sa zmodernizoval a prešiel na prísne typy

### Odstránené
- Podpora PHP 5.x a 7.0
- Staré metódy spracovania

## [1.1.0] - 2017-03-15

### Pridané
- **TatraBanka SimpleMailParser** pre e-maily ComfortPay
- Ďalšie gettre (CID, Sign, RES)
- Podpora opakovaných platieb
- Lepšie rozpoznanie formátu e-mailu

### Vylepšené
- Kvalita kódu a súlad s PSR
- Pokrytie testami
- Dokumentácia

## [1.0.0] - 2016-08-20

### Pridané
- Prvé vydanie
- **TatraBankaMailParser** na základné spracovanie e-mailov TatraBanky
- Trieda **MailContent** pre prečítané dáta
- **ParserInterface** kvôli rozširovaniu
- Podpora spracovania slovenských bankových e-mailov
- Základné pokrytie testami
- Napojenie na Travis CI
- Napojenie na Code Climate

### Funkcie
- Čítanie variabilného symbolu (VS)
- Čítanie špecifického symbolu (ŠS)
- Čítanie konštantného symbolu (KS)
- Čítanie sumy a meny
- Čítanie dátumu transakcie
- Čítanie čísla účtu
- Čítanie správy pre príjemcu

[5.0.0]: https://github.com/tomaj/bank-mails-parser/compare/4.1.0...5.0.0
[4.1.0]: https://github.com/tomaj/bank-mails-parser/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/tomaj/bank-mails-parser/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/tomaj/bank-mails-parser/compare/2.8.0...3.0.0
[2.8.0]: https://github.com/tomaj/bank-mails-parser/compare/2.7.0...2.8.0
[2.7.0]: https://github.com/tomaj/bank-mails-parser/compare/2.6.0...2.7.0
[2.6.0]: https://github.com/tomaj/bank-mails-parser/compare/2.5.0...2.6.0
[2.5.0]: https://github.com/tomaj/bank-mails-parser/compare/2.4.0...2.5.0
[2.4.0]: https://github.com/tomaj/bank-mails-parser/compare/2.3.0...2.4.0
[2.3.0]: https://github.com/tomaj/bank-mails-parser/compare/2.2.0...2.3.0
[2.2.0]: https://github.com/tomaj/bank-mails-parser/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/tomaj/bank-mails-parser/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/tomaj/bank-mails-parser/compare/1.1.0...2.0.0
[1.1.0]: https://github.com/tomaj/bank-mails-parser/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/tomaj/bank-mails-parser/releases/tag/1.0.0
