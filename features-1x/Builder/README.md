# Builder

The Builder edits the page templates of a project graphically: sections, columns and
components, their settings, the texts and pictures where they stand. The feature's manual
(`feature.php`) is its overview; this file describes what is not in it.

## Building blocks

A building block is a section of a page, ready made, that the editor offers under
**+ Building block** - in the bar below the preview and at the "+" between two sections. The
dialog lists the blocks by category, finds them by name and description, and shows each as a
sketch drawn from its model (so it looks the same in every theme). One click puts the block
in: the editor pastes it the way it pastes a copy - a free id, keys and image slots of their
own on the page, the words in the languages the site has - and selects the section. Until the
page is saved nothing in the project has changed; after it the section is ordinary page
content that nothing points back from.

The words go into the languages of the site by this rule: a German language (`de`, `de_AT`
...) gets the German text, an English one the English text, and the site's own language, where
it is neither, the English text. Any other language gets none.

The id of the section is the block's own where the page has no section of that id and the
project holds no text key and no image slot under it. A section that was deleted and saved
leaves its keys behind, so a block put in again takes the next free id and none of the old text.

A block that needs a feature that is not switched on stands in the dialog greyed, with what it
needs, and cannot be put in.

### The format

Every block is a directory of `blocks/`, named by its slug (lower case letters, digits and
hyphens):

```
blocks/hero-centered/
	block.php                 what the dialog shows and sorts by
	block-hero-centered.tpl   one section
	text/en_US.php            the words of the keys, English
	text/de_DE.php            the words of the keys, German
```

`block.php` returns exactly these fields, none more, none fewer:

| Field | Meaning |
|---|---|
| `name` | a line in `en_US` and in `de_DE`, at most 60 characters |
| `description` | one sentence in both, at most 200 characters |
| `category` | `intro`, `content`, `offer` or `action` - the order of the dialog |
| `family` | a slug; the blocks of one family stand together, where its first block stands |
| `rank` | a whole number from 0 to 999, the order within a category and a family |
| `features` | the keys of the features the block needs, `[]` for none |

The template is one `<section>` in the grammar of the Builder, written the way the Writer
writes it: the Reader reads it whole and the Writer gives back the same bytes. It names no class
of its own, no colour, no pixel value, no style and no script, and no HTML+ block. The keys of
its components stand under `/template/block-<slug>/<id of the section>/<name>`; the two text
files hold exactly those keys, as `'/template/block-<slug>/<id>/<name>' => 'word'`.

A block that does not fit - a block.php that prints anything, an unknown or missing field, a
field of the wrong kind, a template that is not one readable section, a source outside the
block's keys, keys that differ between the two languages - is left out with a warning in the
log, and the others are offered.

### Adding one

1. Make the directory and its four files as above. The easiest start is a section built in the
   Builder, copied from the template file, and its words moved into the two text files.
2. Run `php features/Builder/tests/blocks-smoke.php`: it reads every directory of `blocks/` and
   checks the form, the round trip through the Reader and the Writer, the keys and the words.
3. Put it in a page, save, and look at it in each theme at the three widths, light and dark.
