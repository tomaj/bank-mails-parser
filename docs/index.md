---
layout: home

hero:
  name: "Bank Mails Parser"
  text: "Parse Bank Confirmation Emails"
  tagline: Professional PHP library for extracting transaction data from Slovak and Czech bank notification emails
  image:
    src: /logo.svg
    alt: Bank Mails Parser
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/tomaj/bank-mails-parser

features:
  - icon: 🏦
    title: Multiple Banks Supported
    details: Built-in parsers for TatraBanka, ČSOB (CZ/SK), and VÚB with support for various email formats
  - icon: 🔐
    title: PGP Encrypted Emails
    details: Decrypt and parse PGP-encrypted bank statements from TatraBanka with built-in OpenPGP support
  - icon: 📊
    title: Multi-Transaction Processing
    details: Process emails containing multiple transactions in a single message with parseMulti() method
  - icon: ⚡
    title: Modern PHP 8.4+
    details: Leverages PHP 8.4+ features with property hooks, strict typing, and comprehensive error handling
  - icon: 🧪
    title: Thoroughly Tested
    details: 97% code coverage, 75 tests, 467 assertions, and 90% mutation score indicator (MSI)
  - icon: 🎯
    title: Type-Safe API
    details: Strongly typed MailContent objects with nullable return types for safe data extraction
---
