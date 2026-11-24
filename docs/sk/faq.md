---
title: Časté otázky
description: "Časté otázky o bank-mails-parser: podporované banky, parseAll, časové pásma, prázdne sumy, preposlané e-maily a bezpečnosť platieb."
---

# Časté otázky

## Ktoré banky a e-maily sú podporované?

Notifikácie b-mail Tatra banky (`TatraBankaMailParser`), e-maily s výsledkom CardPay a ComfortPay (`TatraBankaSimpleMailParser`), PGP šifrované výpisy kariet Tatra banky (`TatraBankaStatementMailParser`), ČSOB Česká republika (`CsobMailParser`), ČSOB Slovensko (`SkCsobMailParser`) a VÚB (`VubMailParser`). Iné banky potrebujú [vlastný parser](/sk/examples/custom-parser) a príspevok je vítaný: pozrite si [Pridanie novej banky](/sk/guide/adding-bank).

## Mám volať parse() alebo parseAll()?

`parseAll()` vo všetkom, čo spracúva schránku. E-maily ČSOB a výpisy môžu obsahovať viac transakcií; pri nich `parse()` vyhodí `MultipleTransactionsException` a `tryParse()` vráti `null`, takže cyklus postavený na nich by preskočil skutočné platby. `parse()` sa hodí tam, kde viete, že e-mail má jednu transakciu, napríklad v teste.

## Keď sa e-mail spracuje, je platba skutočná?

Sama osebe nie. Spracovanie dokazuje, že e-mail má tvar bankového e-mailu. Overte, že e-mail je pravý (DKIM pre doménu banky), overte `HMAC` z CardPay a platbu s objednávkou párujte podľa variabilného symbolu aj sumy, pretože platiteľ môže do správy napísať akýkoľvek symbol. Pozrite si [Bezpečnosť](/sk/security).

## Skutočný bankový e-mail vyhodí UnsupportedBankException. Prečo?

Väčšinou kvôli odosielateľovi. S odosielateľom sa pýtajú len parsery jeho domény, takže notifikácia preposlaná z `uctovnictvo@vasa-firma.example` sa nezhoduje so žiadnou bankou. Odovzdajte adresu banky (pôvodné `From`) alebo nastavte preposielanie tak, aby pôvodný odosielateľ zostal. Inak mohla banka zmeniť šablónu: porovnajte e-mail s ukážkami v príručke k banke a [založte issue](https://github.com/tomaj/bank-mails-parser/issues) s anonymizovanou kópiou.

## Prečo sú dátumy oproti 4.x posunuté o hodinu alebo dve?

5.x číta dátumy slovenských a českých bánk ako čas `Europe/Bratislava`, teda tak, ako ich myslí banka. 4.x používala `date.timezone` servera, takže na serveri v UTC sa každý čas posunie o +1 hodinu v zime a o +2 hodiny v lete. 14-ciferný `TIMESTAMP` z CardPay je v UTC. Pozrite si [Prechod zo 4.x](/sk/upgrade).

## Prečo je money null?

Transakcia sa rozpoznala, ale jej sumu nebolo možné použiť: `CURR` z CardPay, ktorý nie je kódom ISO 4217, alebo suma ČSOB či VÚB, ktorá chýba, je prázdna alebo nečitateľná. Zvyšok transakcie sa vráti aj tak, ako vo verzii 4.x. Notifikácia Tatra banky, `AMT` z CardPay, ktoré nie je číslo, a riadok výpisu so zlou sumou namiesto toho vyhodia `InvalidEmailFormatException`.

## Ako rozlíšim prichádzajúce a odchádzajúce platby?

Podľa znamienka: `$mail->money?->isNegative()` pre odchádzajúcu platbu, `isPositive()` pre prichádzajúcu. Samostatné pole typu neexistuje, pretože e-maily smer neoznačujú spoľahlivo.

## Môžem odovzdať surový súbor .eml alebo HTML e-mail?

Odovzdajte dekódované textové telo. Base64 alebo quoted-printable najprv dekódujte a časť `text/plain` vyberte knižnicou na e-maily; [príklad schránky](/sk/examples/mailbox) to robí pre jednoduché textové správy. Telá ČSOB, ktoré zalamujú riadky cez `<br>`, `<p>` alebo `<div>`, sa znormalizujú; iné HTML podporované nie je.

## Prečo sa variabilný symbol berie zo správy platiteľa?

Pretože to tak robila verzia 4.1.0 a integrácie sa na to spoliehajú: keď je pole symbolu v banke prázdne, veľa platiteľov napíše symbol do správy. Presné poradie uvádzajú príručky k bankám. Aj preto musíte pred označením objednávky ako zaplatenej porovnať sumu.

## Potrebujem pre výpisy gpg alebo rozšírenie PHP?

Nie. Dešifrovanie používa `singpolyma/openpgp-php`, závislosť v čistom PHP, ktorá sa inštaluje s balíkom. Potrebujete súbor so súkromným kľúčom a jeho heslo; pozrite si [príklad šifrovaného výpisu](/sk/examples/encrypted-statement).

## Ktoré verzie PHP sú podporované?

PHP 8.4, 8.5 a 8.6, všetky povinné v CI, a PHP 8.7 (nightly) sa testuje bez toho, aby zlyhalo zostavenie. 5.x vyžaduje `ext-mbstring`. 4.x podporuje staršie PHP a do 12. 4. 2027 dostáva len bezpečnostné opravy.

## Kde je dokumentácia 4.x?

V ponuke verzií alebo priamo na [4.x](/sk/4.x/). [Prechod zo 4.x](/sk/upgrade) uvádza každú zmenu zo 4.x na 5.0.
