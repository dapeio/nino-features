# Changelog

All notable changes to the Social links feature are documented in this file.
A release is the tag `social-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-10-01

- **First release.** Links to the profiles a site keeps elsewhere, as elements
  of the type Social Media that the install unit copies with four links to
  start from - Instagram, Facebook, YouTube and Telegram, at the networks'
  front pages - and three shortcodes that draw them: `[social]` as a list,
  with `only=`, `exclude=`, `show=` and `size=`; `[social-link]` as one link
  in running text; `[social-icon]` as the icon alone. 23 icons from Lucide,
  six of them the brand icons of its 0.577.0. An address is checked before it
  becomes a link, every link carries a name, a profile on the web is
  `rel="me"`, and nothing opens a new tab.
- Takes over from the social media block Nino's base unit carried up to
  1.3.1: the four `/company/<network>` keys, the template
  `html-socialmedia.tpl` and the `.nino-socialmedia` rules in `Nino.css`. The
  Design feature's frames include this feature's `templates/social-links.tpl`
  instead.
