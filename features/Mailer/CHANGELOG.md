# Changelog

All notable changes to the Mailer feature are documented in this file.
A release is the tag `mailer-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-08

- First release: a pure-php SMTP transport registered under
  `\Nino\Mail::TRANSPORT`, so every mail `\Nino\Mail::send()` hands out -
  the contact form, the Newsletter feature, a project's own code - goes out
  over SMTP instead of the server's `mail()`.
- Settings for host, port, encryption (STARTTLS, TLS from the start, or
  none), username, password, the From address and name, a timeout and
  certificate verification.
- A **Mailer** panel in the workbench's System group: a status line and a
  **Send test mail** button, going through the same transport and the same
  per-ip cap as every other mail on the site.
