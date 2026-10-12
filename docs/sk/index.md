---
layout: home

hero:
  name: "Bank Mails Parser"
  text: "Spracovanie bankových potvrdení"
  tagline: PHP knižnica, ktorá z notifikačných e-mailov slovenských a českých bánk vyťahuje údaje o transakciách
  image:
    src: /logo.svg
    alt: Bank Mails Parser
  actions:
    - theme: brand
      text: Začať
      link: /sk/guide/getting-started
    - theme: alt
      text: Zobraziť na GitHube
      link: https://github.com/tomaj/bank-mails-parser

features:
  - icon: 🏦
    title: Viacero bánk
    details: Vstavané parsery pre TatraBanku, ČSOB (CZ/SK) a VÚB, aj pre rôzne formáty e-mailov
  - icon: 🔐
    title: E-maily šifrované cez PGP
    details: Dešifrovanie a spracovanie PGP výpisov z TatraBanky cez vstavanú podporu OpenPGP
  - icon: 📊
    title: Viac transakcií v jednej správe
    details: E-mail s viacerými platbami spracujete naraz metódou parseMulti()
  - icon: ⚡
    title: Moderné PHP 8.4+
    details: Nemenné value objekty pre PHP 8.4+, prísne typovanie a spracovanie cez výnimky
  - icon: 🧪
    title: Dôkladne otestované
    details: Cieľom je pokryť aspoň 97 % príkazov a v mutačných testoch dosiahnuť 89 % MSI a 90 % covered MSI
  - icon: 🎯
    title: Typovo bezpečné API
    details: Silno typované objekty MailContent. Chýbajúci údaj je null, nie falošná nula
---
