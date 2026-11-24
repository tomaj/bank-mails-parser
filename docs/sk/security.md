---
title: Bezpečnosť
description: "Ako nahlásiť zraniteľnosť v bank-mails-parser a čo knižnica kontroluje a čo nie, kým zaúčtujete platbu."
---

# Bezpečnosť

Notifikačný e-mail je vstup z internetu. Ktokoľvek vám môže poslať e-mail, ktorý vyzerá ako bankový, a časť každého skutočného bankového e-mailu je text, ktorý napísal platiteľ. Táto stránka uvádza, pred čím knižnica chráni, čo nechá na vás a ako nahlásiť zraniteľnosť.

## Čo knižnica kontroluje

- **Bankovými údajmi sú len vlastné riadky banky.** Polia, ktoré píše platiteľ (`Informacia pre prijemcu`, `Zpráva příjemci`, `Účel platby`, `Identifikace`, `názov protiúčtu`, `informácia pre príjemcu`, referencie a ďalšie uvedené v príručkách k bankám), sa vymažú skôr, než sa prečíta suma, mena, účty a dátum. Hlavička transakcie alebo suma napísaná do správy sa nemôže stať platbou a pri rozpoznávaní nemôže vybrať banku.
- **Nejednoznačnosť je chyba.** Keď ten istý e-mail prijmú dva parsery, fasáda vyhodí `AmbiguousBankException` a nevyberá. Kľúč CardPay zopakovaný s inou hodnotou, e-mail ČSOB, ktorého zostatky nesedia, a transakcia ČSOB s druhým riadkom zostatku sa odmietnu s `InvalidEmailFormatException`.
- **Pri peniazoch sa nehádá.** Sumy sú celé čísla v najmenších jednotkách. `Money` odmietne nejednoznačné oddeľovače (`1.234`), nadbytočné nenulové desatinné miesta a hodnoty, ktoré sa nezmestia do celého čísla; nič sa nezaokrúhľuje.
- **Citovaná história nie je platba.** Riadky, ktoré začínajú `>`, sa ignorujú.
- **Odosielateľ zúži rozpoznávanie.** S odosielateľom sa pýtajú len parsery, ktorých domény sa zhodujú s hostiteľom odosielateľa.

## Čo musíte skontrolovať vy

- **Kto e-mail poslal.** Knižnica neoveruje DKIM, SPF ani DMARC; hlavička From je len reťazec. Bankové notifikácie prijímajte, len keď `Authentication-Results` od vášho poštového servera hovorí, že DKIM pre doménu banky prešiel, alebo ich prijímajte na adrese, ktorú pozná len banka. Potom odovzdajte odosielateľa, aby sa rozpoznávanie obmedzilo na túto banku.
- **Podpisy CardPay a ComfortPay.** `sign` obsahuje hodnotu `HMAC` (alebo `SIGN`) tak, ako prišla. Neoveruje sa, pretože knižnica nemá kľúč vašej platobnej brány. Overte ho kľúčom brány rovnako, ako overujete návratovú URL CardPay (napríklad cez [omnipay-tatra](https://github.com/tomaj/omnipay-tatra)).
- **Sumu, nielen symbol.** Variabilný symbol môže pochádzať zo správy, ktorú napísal platiteľ (`Informacia pre prijemcu` Tatra banky, `Identifikace` a `Zpráva příjemci` ČSOB CZ, `informácia pre príjemcu` ČSOB SK), presne ako vo verzii 4.1.0. Platbu s objednávkou spárujte, len keď sedí symbol **aj** suma, a ďalší identifikátor, ak ho máte. Pozrite si [príklad schránky](/sk/examples/mailbox).
- **E-maily ČSOB bez riadkov zostatku.** E-mail s viacerými transakciami a bez riadku zostatku sa spracuje ako vo verzii 4.1.0. Bez zostatkov od banky sa transakcia podvrhnutá do textu platiteľa za prázdnym riadkom nedá odlíšiť od skutočnej. Takéto platby odsúhlaste s výpisom alebo cez API banky.
- **Idempotenciu.** Tá istá platba môže prísť dvakrát (preposlaný e-mail, opakovaný job). Zaúčtujte ju raz, napríklad cez jedinečný kľúč z údajov transakcie.

## Kľúče a osobné údaje

- Súkromný PGP kľúč k výpisom Tatra banky držte mimo webového koreňa a mimo repozitára, čitateľný len pre proces, ktorý spracúva poštu; cestu a heslo čítajte z úložiska tajomstiev.
- Bankové e-maily obsahujú čísla účtov, mená a správy k platbám. Ukladajte a logujte ich ako osobné údaje podľa GDPR a nedávajte surové telá e-mailov do chybových hlásení, ktoré vidia používatelia alebo služby tretích strán. Správy výnimiek tejto knižnice neobsahujú adresu odosielateľa ani telo e-mailu (neplatná suma sa cituje).

## Podporované verzie

Bezpečnostné opravy sa poskytujú pre tieto rady:

| Verzia | Podpora |
| ------ | ------- |
| 5.x    | Áno: opravy chýb a bezpečnostné opravy |
| 4.x    | Len bezpečnostné opravy, do 12. 4. 2027 |
| < 4.0  | Nie |

5.x je aktuálna stabilná rada. 4.x dostáva opravy len bezpečnostných zraniteľností, šesť mesiacov po vydaní 5.0.0 (do 12. 4. 2027); vetva údržby 4.x neexistuje, takže oprava sa vydá z vetvy vytvorenej zo značky `4.1.0`. Na 5.x prejdite podľa [UPGRADE-5.0.md](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md) alebo stránky [Prechod zo 4.x](/sk/upgrade). Verzie staršie ako 4.0 bezpečnostné aktualizácie nedostávajú.

## Nahlásenie zraniteľnosti

Ak v tejto knižnici nájdete bezpečnostnú zraniteľnosť, nahláste ju zodpovedne.

### Súkromné nahlásenie

Pre bezpečnostné zraniteľnosti **NEZAKLADAJTE** verejné issue.

Uprednostňovaný spôsob je [súkromné nahlásenie zraniteľnosti na GitHube](https://github.com/tomaj/bank-mails-parser/security/advisories/new).

Záložne pošlite podrobnosti e-mailom na **tomasmajer@gmail.com**.

Uveďte:
- opis zraniteľnosti
- kroky na zopakovanie
- možný dopad
- návrh opravy (ak ho máte)

### Odpoveď

Potvrdenie dostanete do 14 dní. Potom problém potvrdíme, dohodneme s vami dátum zverejnenia a počas prípravy opravy vás budeme informovať.

### Koordinované zverejnenie

Postupujeme podľa koordinovaného zverejnenia. Pred verejnou diskusiou nám, prosím, dajte čas pripraviť opravu. Keď opravu vydáme, zverejníme GitHub Security Advisory a opravu zapíšeme do CHANGELOG ako položku `**Security**:`.

## Odporúčané postupy

Pri používaní knižnice:

1. **PGP kľúče**: súkromné PGP kľúče držte mimo webového koreňa
2. **Prístupové údaje**: citlivú konfiguráciu dávajte do premenných prostredia
3. **Overovanie**: spracované sumy vždy overte skôr, než platbu spracujete
4. **Chybové hlásenia**: nikdy v nich nezobrazujte surový obsah e-mailu
5. **Logovanie**: zlyhania spracovania logujte kvôli bezpečnostnému monitoringu
6. **Aktualizácie**: knižnicu aj závislosti udržiavajte aktuálne

## Známe bezpečnostné aspekty

### Obsah e-mailov

Knižnica spracúva obsah e-mailov, ktorý môže obsahovať:
- údaje o finančných transakciách
- čísla účtov (IBAN)
- platobné symboly (VS, KS, SS)

Vždy:
- spracované údaje pred použitím overte
- výstup zobrazovaný používateľom ošetrite
- spracované údaje ukladajte bezpečne
- dodržiavajte GDPR a miestne predpisy o ochrane údajov

### Dešifrovanie PGP

`TatraBankaStatementMailParser` dešifruje PGP šifrované e-maily:
- súkromné kľúče ukladajte bezpečne
- používajte silné heslá
- kľúče pravidelne meňte
- prístup k súborom s kľúčmi v súborovom systéme obmedzte

## Poďakovanie

Vážime si bezpečnostných výskumníkov, ktorí zraniteľnosti nahlasujú zodpovedne. Prispievateľov uvedieme v poznámkach k vydaniu (ak si neželajú zostať v anonymite).
