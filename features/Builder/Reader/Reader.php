<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Builder\Reader	see features/Builder/Builder.php for the feature's own
 *												docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Builder {

	/**
	 *	Nino								A compact filesystembased php framework
	 *	Reader							A page template in, the model out: the head, the blocks in
	 *											their order, the foot. A pure function of the source and
	 *											the registry of components - no $appData, no file, no
	 *											state - so the tests drive it directly and the panel can
	 *											rely on one answer for one file.
	 *
	 *											The grammar it reads is the one the Writer writes: a block
	 *											is a section (a <section>, an optional background, exactly
	 *											one row with an optional background of its own, columns, and
	 *											in each column an optional background of its own and either
	 *											component calls or one stack call) or a foreign block. Where the
	 *											registry has a stack whose cell is a whole section, its call
	 *											[sections ...] stands for the tag of a section and holds what a
	 *											section holds between its tags. A component whose schema says it holds
	 *											components ('holds', the place of the ones it holds: the
	 *											kernel's card) is a container: its call has the calls of its
	 *											children between its tags, one level deep, in a column and
	 *											inside a stack. A section is
	 *											read whole or not at all - whatever it holds that the model
	 *											cannot keep (a second row, a column without a width, a
	 *											shortcode that is no component, markup next to a stack or
	 *											next to the calls of a container, a loop or a container
	 *											inside a container, a background that is not the first thing
	 *											in its column, a nested call, an attribute the schema does
	 *											not know) makes
	 *											the whole section a foreign block, with the line and the
	 *											reason it failed at. A foreign block runs from where the
	 *											reading failed to the next section that is read, and is
	 *											written back byte for byte; one wrapped in the markers
	 *											<!-- nino:html --> ... <!-- /nino:html --> is recognised
	 *											without being analysed, and carries no reason.
	 *
	 *											All the blocks stand in a wrap, a <div class="nino-wrap ...">
	 *											between the head and the foot. It is known by its place
	 *											alone: the first line after the head (the name and the
	 *											frame above) opens it, a line of nothing but that tag with
	 *											the class attribute alone, and the last line before the
	 *											foot (the frame below) closes it, a line of nothing but
	 *											</div>. A file that lacks either of the two has no wrap and
	 *											is read as it was before there was one - the one line that
	 *											is there is a foreign block - and the Writer gives it its
	 *											wrap with the first change. The classes of the wrap are the
	 *											kind of animation of the template: nino-wrap--vpa says the
	 *											sections are animated, an effect with its strength
	 *											(nino-vpa--zoom-soft) and a speed (nino-vpa--speed-medium)
	 *											say how, through the custom properties of Nino.css that
	 *											every .nino-vpa below it inherits; every other class is kept
	 *											as it is. The kernel reads none of it but the classes of the
	 *											elements that are animated: the rows of the sections, and the columns.
	 *
	 *											The shortcode calls are read the way \Nino\Html reads them:
	 *											the same expression for the call and its content, the same
	 *											one for the arguments - a first positional argument, named
	 *											arguments in double quotes. A call whose arguments that
	 *											expression would read differently than they were written (a
	 *											quote of the other kind, two spaces, a stray '=') is not
	 *											kept, because writing it back would change it.
	 *
	 *											THE MODEL, as it runs between the panel and the server
	 *											(every key is always present in what read() answers; the
	 *											Writer takes a missing one as its default):
	 *
	 *											document
	 *											  file      string  page-home; read() leaves it '', Document sets it
	 *											  name      string  the nino:template-name comment, '' where none
	 *											  animate   bool    nino-wrap--vpa on the wrap: the sections are animated,
	 *											                    and a new one is given a bare nino-vpa
	 *											  vpa       string  the effect of the wrap with its strength as one word
	 *											                    (nino-vpa--zoom-soft is zoom-soft), '' for none: then
	 *											                    what the frame sets, else Nino.css, holds
	 *											  vpaSpeed  string  ''|fast|medium|slow   nino-vpa--speed-<x> on the wrap
	 *											  wrapClass string  every other class of the wrap, as they are written
	 *											  header    string  html-header, or any html-header* template; '' for none
	 *											  footer    string  html-footer, ditto
	 *											  blocks    list    section and html, in the order of the file
	 *
	 *											html (a foreign block)
	 *											  kind      'html'
	 *											  source    string  byte for byte, without the blank lines around it
	 *											  reason    null    wrapped in the markers - written back with them -
	 *											            array   read as a section and failed: line (int, the first
	 *											                    line of the attempt), code (see REASONS), detail
	 *											                    (a name where the text has one, else ''; for a
	 *											                    sentence with two places the two, a line feed between),
	 *											                    text (the sentence, in English) - written back as it was
	 *											  edited    bool    only ever sent by the panel, never read: the source
	 *											                    was changed, so a save reads it as a section again
	 *
	 *											section
	 *											  kind      'section'
	 *											  id        string  slug, unique in the page; the third segment of the
	 *											                    text keys of its components
	 *											  renamedFrom string only ever sent by the panel: the id it had, so a
	 *											                    save moves the keys of the section
	 *											  settings  map     of the section and its row, defaults in sectionDefaults()
	 *											    row         ''|narrow|wide|full   nino-grid-row--<x>: the maximum width of the row, the grid's own for ''
	 *											    rowPlace    ''|left|right         nino-grid-row--<x>: where a row narrower than the page stands, the middle for ''
	 *											    rowAlign    ''|center|middle|bottom   nino-grid-<x>
	 *											    reverse     map                   s, m, l: bool each, the columns of the row in the opposite order from that viewport on. The
	 *											                                      Writer says the fewest classes: nino-grid-row--reverse for s, then for m and l a
	 *											                                      nino-grid-row--<m|l>-reverse or --<m|l>-forward where the value differs from the viewport
	 *											                                      before. Read: --reverse is s, --m-* and --l-* the others, the first
	 *											                                      class of a viewport is the setting; --s-*, --xl-* and a bare --forward are none of the three and stay custom classes
	 *											    rowGap      ''|0..6               nino-grid-gap-<n>: the gap between the columns of the row, a step of the space scale; '' writes none. The first
	 *											                                      class is the setting, a second one is a class of the row's own
	 *											    rowPl rowPr rowPt rowPb  ''|0..6  nino-pl-<n> nino-pr-<n> nino-pt-<n> nino-pb-<n>: the padding of the row on each side, a step
	 *											                                      of the space scale; '' writes none - the gutter at the sides, no padding above and below,
	 *											                                      or what the background inside gives the row
	 *											    rowRadius   ''|standard|alternative   nino-radius, nino-radius-alt on the row: the radius of its ground and of its picture, the page's own
	 *											                                      for ''. Read where the background stands inside (inner); on a row that paints nothing the two classes are idle and stay custom ones
	 *											    rowCustom   string                classes of the row that are none of these
	 *											    rowBgPl rowBgPr  ''|0..3        nino-bg-pl-<n> nino-bg-pr-<n>: how far in from the left and from the right the picture inside the row starts, a step
	 *											                                      of the space scale; '' is the edge. Read where the picture stands inside (inner and a background); on a row without one the two
	 *											                                      classes are idle and stay custom ones
	 *											    color       ''|alt|tint|dark|black|primary|brand-alt
	 *											    border      ''|1|2|3              nino-section--border-<n>
	 *											    borderPrimary bool                nino-section--border-primary, with or without a strength
	 *											    cover       int|null              nino-cover with data-cover-height: the section is a cover this
	 *											                                      high, in percent of the window's height; null: it is none
	 *											    parallax    bool                  nino-parallex, on its own or on a cover as well
	 *											    dim         bool                  the scrim over the picture: nino-cover--dim where the section
	 *											                                      is a cover, else nino-parallex--dim where it is a parallax,
	 *											                                      else nino-section--dim where the section has a picture of its
	 *											                                      own - or nino-grid-row--dim where the picture is the row's
	 *											                                      (inner); a scrim over no picture is a custom class
	 *											    padded      bool                  nino-cover--padded: the cover or the parallax keeps the room of
	 *											                                      the page around it; on a section that is neither the class
	 *											                                      is idle and stays a custom one
	 *											    radius      ''|standard|alternative   nino-radius, nino-radius-alt: the radius of the picture of a padded cover, the page's own
	 *											                                      for ''; on a section that is not a padded cover the two classes are idle and stay custom ones
	 *											    contentPos  ''|top|bottom         nino-cover-<x>: where the row stands in a cover or a parallax,
	 *											                                      the middle for ''; on a section that is neither the class
	 *											                                      is idle and stays a custom one
	 *											    inner       bool                  the colour, the picture and the scrim are the row's and not the
	 *											                                      section's: nino-grid-row--<color>, div.nino-grid-row-bg as the
	 *											                                      first thing in the row, nino-grid-row--dim. Read where the row has
	 *											                                      a ground or a picture and the section is no cover, no parallax and
	 *											                                      has no colour and no picture of its own; the colour and the picture
	 *											                                      are the section's keys all the same. With neither of the two there
	 *											                                      is nothing on the row to read it from, and it reads as off
	 *											    tiles       bool                  nino-tiles: the section is a section of tiles, the second kind - full width, no padding of its own, its
	 *											                                      columns tiles with a height, a padding and a radius of their own. The cover, the parallax, the scrim, the room of
	 *											                                      a padded cover, the place of the content and the radius of a padded cover are the content kind's: on a tiles
	 *											                                      section they are custom classes, and so are the width, the place, the ground and the scrim of its row; its inner
	 *											                                      is never read. A section background or a row background in it is a failure (tiles-background)
	 *											    ratio       ''|RATIO              nino-tiles--<x>: the height of every tile, the words of the ratio of the image component; '' is a tile as high as
	 *											                                      its words. Read while the section is tiles, else the class is a custom one; a second class is a class of its own
	 *											    whitespace  ''|least|less|more|most   nino-whitespace--<x>: how much room the section keeps, on
	 *											                                      the scale of the page's whitespace (the Design's)
	 *											    mt mb pt pb pl pr ''|0..6         nino-mt-<n> nino-mb-<n> nino-pt-<n> nino-pb-<n> nino-pl-<n> nino-pr-<n>: the six sides of the spacing of the section - the margin
	 *											                                      above and below it, the padding at its top, at its bottom, on its left and on its right -, each a step of the space scale, in the
	 *											                                      order of SPACING. The first class of a side is the setting, a second class of the side is a class of its own. The kernel's nino-p-<n>
	 *											                                      is the four inside sides at once, the base that a class of one side stands over, whatever the order of the two in the attribute, and a
	 *											                                      second nino-p-<n> is a class of its own; the Writer says the four inside sides as nino-p-<n> where they are set and the same
	 *											    text        ''|left|center|right  nino-text-<x>
	 *											    arrow       bool                  [scroll-hint], the line after the row: the arrow at the foot of the section, which leads to what follows it
	 *											    vpa         null|string           the animation of the section stands on its row - its classes among those of the row, its delay and its duration as
	 *											                                      attributes of its tag - and on the section itself it is a class like any other. null: none; '': nino-vpa; else the
	 *											                                      effect, nino-vpa--<x>
	 *											    vpaSpeed    ''|fast|medium|slow   nino-vpa--speed-<x>
	 *											    vpaMode     ''|repeat|visible|visible-once
	 *											    vpaDelay    string                data-vpa-delay of the row, a css time
	 *											    vpaDuration string                data-vpa-duration of the row
	 *											    custom      string                every other class of the section
	 *											  background null|map  slot (string, a text key grammar uri), focus
	 *											            (int 1..9|null); an image call with alt=""; create (map,
	 *											            only ever sent by the panel) as that of a component. It stands
	 *											            before the row, in div.nino-section-bg - or, with inner, in the row
	 *											  stack     null    the section is a static one: a <section> tag
	 *											            map     name, source, attributes - the registered stack whose cell is a whole
	 *											                    section (its schema says cell: 'section', which the Document puts there):
	 *											                    the section is written as the call [sections /type ...] with its row for
	 *											                    the content, and the id of the section and its classes after nino-section
	 *											                    are the call's id and class - the attributes of the map hold none of the
	 *											                    two, they are '' - so it can carry none of the attributes of a tag: a cover
	 *											                    is as high as Nino.ui.js makes it. The row stands inside the call, and the animation of the section is the row's, with its delay and its duration
	 *											  cols      list    the columns of the one row
	 *
	 *											column
	 *											  width     map     s, m, l: 25 33 50 66 75 100 (ints), what each
	 *											                    viewport ends up with, mobile first: nino-grid-100
	 *											                    alone is all three, nino-grid-m-50 holds from m up.
	 *											                    A column that is not 100 everywhere is written
	 *											                    nino-grid-100 nino-grid-s-<s> nino-grid-m-<m>
	 *											                    nino-grid-l-<l> - full width until its first tier
	 *											                    applies - and the tiers win over the base class;
	 *											                    a column with tiers and without the base class
	 *											                    reads the same
	 *											  hidden    map     s, m, l => true, only the hidden ones (none
	 *											                    hidden is [] in php, and in the json)
	 *											  color     ''|counter|alt|tint|dark|black|primary|brand-alt
	 *											                    nino-grid--<x>; counter is the ground that sets the
	 *											                    column off from the section it is in
	 *											  dim       bool    nino-grid--dim, written only while the column has a
	 *											                    background: there is no picture to darken without one
	 *											  linked    bool    nino-grid--linked, read on a tile - a column of a section of tiles - only: the title's
	 *											                    link covers the whole tile. On a column of a content section it is a custom class
	 *											  radius    ''|standard|alternative   nino-radius, nino-radius-alt: the radius of the ground and of the picture of the column - of a tile, of the kind tile -,
	 *											                    the page's own for ''; on a column that paints neither the two classes are idle and stay custom ones
	 *											  background null|map  as that of a section: slot, focus, create; the
	 *											            div.nino-grid-bg, the first thing in the column - before its
	 *											            stack or its components
	 *											  text      ''|left|center|right
	 *											  stackAlign ''|start|center|end   nino-stack-<x>
	 *											  stackGap  ''|0..6                nino-stack-gap-<n>
	 *											  mt mb pt pb pl pr ''|0..6        the six sides of the spacing of the column, read and written as those of a section: nino-mt-<n> to nino-pr-<n>,
	 *											                    and the kernel's nino-p-<n> for the four inside sides at once
	 *											  bgPl, bgPr ''|0..3               nino-bg-pl-<n>, nino-bg-pr-<n>: how far in from the left and from the right the picture of the column starts, a step of the
	 *											                    space scale; read where the column has a picture (background), else the two classes are idle and stay custom ones
	 *											  vpa, vpaSpeed, vpaMode   as the animation in settings, with the classes of the column
	 *											  vpaDelay, vpaDuration   always ''; a column is written with its classes
	 *											                    alone, and has no data-vpa-delay or data-vpa-duration
	 *											  custom    string
	 *											  stack     null    the column is a static stack of components
	 *											            map     name, source (the type's uri), attributes - the
	 *											                    registered stack that loops its components
	 *											  components list  of component, in order; inside the stack's content
	 *											                    where the column has one
	 *
	 *											component
	 *											  name      string  a registered component
	 *											  source    string  the first argument: a text key, an image slot, a
	 *											                    field of the type inside a stack, '' for none
	 *											  text      null|string   the fixed value, text="..."
	 *											  attributes map    every attribute of the schema, a string each - the
	 *											                    default where the call does not set it
	 *											  radius    ''|standard|alternative   only for a component that is a surface (see surface()): the class nino-radius or nino-radius-alt of its call,
	 *											                    the page's own for ''. The Reader takes the first of the two out of the class attribute, the Writer puts it back as the first class
	 *											                    of the call
	 *											  hover     ''|on   only for a component whose source is an image, the picture of a card among them, which is no surface: the class nino-hover of its call,
	 *											                    the first of them taken out of the class attribute as the radius is and put back by the Writer after the radius, before the offsets of
	 *											                    the ground of a card and the sides of the spacing; what it does is the Design's to say
	 *											  bgPl, bgPr ''|0..3   only for the card, the surface that paints a ground: the classes nino-bg-pl-<n> and nino-bg-pr-<n> of its call,
	 *											                    taken out of the class attribute as the radius is and put back after it by the Writer, whatever the call says of a picture
	 *											  mt mb pt pb pl pr ''|0..6   the six sides of the spacing of any component: the classes nino-mt-<n> to nino-pr-<n> of its call, taken out of the class attribute as
	 *											                    the radius is - the first of a side is the setting, a second is a class of the editor's - and put back by the Writer after the radius and the
	 *											                    offsets of the ground, in the order of SPACING. The kernel's nino-p-<n> is no setting of a call: a card's padding attribute says the same
	 *											                    for its four sides, and the Builder keeps the two apart
	 *											  content   string  only for a component whose source is 'content' and whose
	 *											                    schema does not say 'holds'
	 *											  components list  only for a component whose schema says 'holds' (a container):
	 *											                    the component nodes between its tags, in order, one level
	 *											                    deep - none of them has 'components' - and never a
	 *											                    'content'
	 *											  create    map     only ever sent by the panel: what the key or the slot
	 *											                    this source names is made with at save - for a key
	 *											                    format (that of its component's format attribute,
	 *											                    plain where it has none) and nothing else: the text it
	 *											                    is made with is what was typed for it (values), in the
	 *											                    language it was typed in, and none in the others; for a
	 *											                    slot label, width, height and alt (a text by language,
	 *											                    written to the slot once made)
	 *											  values    map     only ever sent by the panel: the texts typed for the
	 *											                    key this source names, by language (language =>
	 *											                    text), only those that were changed - for a key the
	 *											                    save makes, those that were typed. They are the
	 *											                    key's and not the file's, so the file never has them:
	 *											                    a save writes them to the key, in their language
	 *											  slots     map     only ever sent by the panel, as create is, for the image
	 *											                    attributes of the schema (the kernel's card has one, its
	 *											                    picture): attribute => what the slot the attribute names
	 *											                    is made with at save, label, width, height and alt (a text
	 *											                    by language), as the create of a source for a slot. The
	 *											                    file never has it; the save reads it for the attributes
	 *											                    that name a slot and are not empty
	 *
	 *	@package						Dape/Nino
	 *	@author							David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Reader {

		// The vocabulary of Nino.css the model keeps in settings of their own,
		// the order the Writer writes them in - everything else is 'custom'.
		// The maximum width of the row, the grid's own being none, and where a row narrower than the page stands, the middle being none
		public const array ROW = [ 'narrow', 'wide', 'full' ];
		public const array ROW_PLACE = [ 'left', 'right' ];
		public const array ROW_ALIGN = [ 'center', 'middle', 'bottom' ];
		// The padding of the row, one step of the space scale a side: the word of the class and the setting that holds it
		public const array ROW_SPACING = [ 'pl' => 'rowPl', 'pr' => 'rowPr', 'pt' => 'rowPt', 'pb' => 'rowPb' ];
		public const array COLOR = [ 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ];
		// A column paints the same grounds, and the one that sets it off from its section
		public const array COL_COLOR = [ 'counter', ...self::COLOR ];
		public const array BORDER = [ '1', '2', '3' ];
		// Where the row stands in a cover or a parallax, the middle being none: nino-cover-center aligns the text of a
		// .nino-cover-content, which the Builder does not write, so it is a custom class here
		public const array CONTENT_POS = [ 'top', 'bottom' ];
		// The spacing of a section, a column and a component: six sides, each one of the steps of the space scale, in the order the Writer writes their classes and the
		// settings stand in - the margin above and below, then the padding at the top, at the bottom, on the left and on the right
		public const array SPACING = [ 'mt', 'mb', 'pt', 'pb', 'pl', 'pr' ];
		// The four of them that are inside: the kernel's nino-p-<n> says the same for all four at once
		public const array INSIDE = [ 'pt', 'pb', 'pl', 'pr' ];
		public const array STEPS = [ '0', '1', '2', '3', '4', '5', '6' ];
		// How far in from either side the picture behind a row, a column or a card starts: the word of the class and the setting that holds it, one step of the space scale from 0 to 3
		public const array BG_STEPS = [ '0', '1', '2', '3' ];
		public const array ROW_BG_SPACING = [ 'pl' => 'rowBgPl', 'pr' => 'rowBgPr' ];
		public const array BG_SPACING = [ 'pl' => 'bgPl', 'pr' => 'bgPr' ];
		// How much room a section keeps, the four rungs of the page's whitespace around the middle: nino-whitespace--<x>
		public const array WHITESPACE = [ 'least', 'less', 'more', 'most' ];
		/*	The radius of a surface: the two strengths the page has - the Design writes both, Nino.css has them as well -, the class that puts one
			element on either, and the six kinds of surface the page has a default strength for (see surface() for the components)	*/
		public const array RADIUS = [ 'standard', 'alternative' ];
		public const array RADIUS_CLASS = [ 'standard' => 'nino-radius', 'alternative' => 'nino-radius-alt' ];
		public const array SURFACES = [ 'cover', 'row', 'column', 'tile', 'card', 'image' ];
		// The height of a tile, the ratio the section names for all of its tiles: the words of the ratio of the kernel's image component, which the smoke test holds equal
		public const array RATIO = [ '1-1', '4-3', '3-2', '16-9', '21-9', '4-1', '5-1' ];
		// The hover of a picture - a component whose schema says its source is an image -: the one class that puts the hover effect of the page on its frame, and the word of the
		// setting while it does
		public const string HOVER = 'on';
		public const string HOVER_CLASS = 'nino-hover';
		// Where a component that is a link opens: the target that every component with 'link' has, the second of its two implicit attributes
		public const array TARGETS = [ '', '_blank' ];
		public const array TEXT = [ 'left', 'center', 'right' ];
		public const array EFFECTS = [
			'blur-soft', 'blur-medium', 'blur-hard', 'flip-soft', 'flip-medium', 'flip-hard',
			'slide-down-soft', 'slide-down-medium', 'slide-down-hard',
			'slide-left-soft', 'slide-left-medium', 'slide-left-hard', 'slide-right-soft', 'slide-right-medium', 'slide-right-hard',
			'slide-up-soft', 'slide-up-medium', 'slide-up-hard',
			'zoom-soft', 'zoom-medium', 'zoom-hard', 'zoom-out-soft', 'zoom-out-medium', 'zoom-out-hard',
		];
		public const array SPEEDS = [ 'fast', 'medium', 'slow' ];
		public const array MODES = [ 'repeat', 'visible', 'visible-once' ];
		public const array WIDTHS = [ '25', '33', '50', '66', '75', '100' ];
		public const array VIEWPORTS = [ 's', 'm', 'l' ];
		public const array STACK_ALIGN = [ 'start', 'center', 'end' ];

		// The class of the wrap that makes it one, and the one that says its sections are animated
		// The word of a variant, which the renderer writes as the end of a class: lower case letters and digits, hyphens between them (the kernel's own rule)
		public const string WORD = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
		public const string WRAP = 'nino-wrap';
		public const string WRAP_VPA = 'nino-wrap--vpa';

		// The call of the arrow at the foot of a section: the one thing that may stand after its row
		public const string ARROW = 'scroll-hint';

		// What follows the name of a call up to its closing bracket: a fill, [[...]], is one thing in it - the kernel puts the fills in before it reads a call, so that
		// the brackets of a fill that is there are not the end of the call - and any other character is itself. See address() for the only fills a call may hold
		private const string RAW = '(?:\[\[[^\[\]]*+\]\]|[^\]])*+';

		// An address that leads to a page of the site and goes on from it: the directory the site is served from, [[/nino/dir]] - the key that holds the address of the page is
		// written without it, as the kernel puts it in front of a bare key - then the fill of that key, [[/_nino/webpage/<uri>/uri]], then the sub-path: characters that are none
		// of a space, a quote, a backslash or a bracket, and the fills of the element of a loop, [[.uri]] or [[title]]
		private const string ADDRESS = '~^\[\[/nino/dir\]\]\[\[(/_nino/webpage/[^\[\]\s"\'\\\\]+/uri)\]\]((?:[^\s"\'\\\\\[\]]|\[\[\.?[A-Za-z0-9_-]+\]\])*)$~D';

		// Why a section is not read: the code of a failure, and the sentence
		// that says it. %s is the detail, where the failure has one
		public const array REASONS = [
			'not-a-section'				=> 'a block that is no section',
			'marker-unclosed'			=> 'a <!-- nino:html --> marker that is never closed',
			'attribute-syntax'		=> 'a tag whose attributes are not written as name="value"',
			'section-attribute'		=> 'the section has the attribute %s, which the builder does not keep',
			'tiles-background'		=> 'the section of tiles has the background block %s, which the builder does not keep: its tiles hold the pictures',
			'section-id'					=> 'the section has no id that is a slug',
			'duplicate-id'				=> 'the id %s is the id of an earlier section',
			'section-class'				=> 'the section has no class nino-section',
			'class-token'					=> 'the class %s has characters the builder does not keep',
			'attribute-value'			=> 'the attribute %s has a value the builder does not keep',
			'background-class'		=> 'the background block has the class %s, which the builder does not keep',
			'background-image'		=> 'the background block is not one image call with an empty alt',
			'grid-background'		=> 'the column has a background block that is not the first thing in it, or a second one',
			'row-background'		=> 'the row has a background block that is not the first thing in it, or a second one, or the section has a colour, a picture, a cover or a parallax of its own as well',
			'no-row'							=> 'the section has no row',
			'second-row'					=> 'the section has more than one row',
			'section-content'			=> 'the section holds markup next to its row',
			'row-attribute'				=> 'the row has the attribute %s, which the builder does not keep',
			'row-content'					=> 'the row holds something that is no column',
			'no-cols'							=> 'the row has no column',
			'nested-row'					=> 'a row inside a row',
			'col-attribute'				=> 'the column has the attribute %s, which the builder does not keep',
			'col-width'						=> 'the column has no width class for every viewport',
			'col-markup'					=> 'the column holds markup next to its component calls: %s',
			'unknown-shortcode'		=> 'the shortcode %s is no registered component or loop',
			'call-arguments'			=> 'a call whose arguments are not written the way the kernel reads them: %s',
			'unknown-attribute'		=> 'the attribute %s is none of the component\'s',
			'component-content'		=> 'the component %s takes no content',
			'nested-call'					=> 'a call inside the content of %s',
			'stack-source'				=> 'a loop without the type it loops',
			'stack-neighbour'			=> 'a loop with other components in its column',
			'stack-in-stack'			=> 'a loop inside a loop',
			'stack-content'				=> 'the loop holds markup next to its component calls: %s',
			'holder-stack'				=> 'a loop inside a component that holds components',
			'holder-in-holder'		=> 'a component that holds components inside another',
			'holder-content'			=> 'the component %s holds markup next to its component calls: %s',
			'sections-no-row'			=> 'the loop of sections has no row',
			'sections-second-row'	=> 'the loop of sections has more than one row',
			'sections-content'		=> 'the loop of sections holds markup next to its row',
			'sections-nested'			=> 'a loop of sections inside a section',
			'sections-attribute'	=> 'the loop of sections has the attribute %s, which the builder does not keep: the sections it makes have an id and classes, no other attribute',
		];

		// The marker the builder wraps a foreign block in, and its end
		public const string MARKER = '<!-- nino:html -->';
		public const string MARKER_END = '<!-- /nino:html -->';

		/**
		 *	The settings of a section and its row, as a section without a
		 *	single modifier has them
		 *
		 *	@return 	array
		 */
		public static function sectionDefaults(): array {
			return [
				'row' => '', 'rowPlace' => '', 'rowAlign' => '', 'reverse' => [ 's' => false, 'm' => false, 'l' => false ], 'rowGap' => '', 'rowPl' => '', 'rowPr' => '', 'rowPt' => '', 'rowPb' => '', 'rowRadius' => '', 'rowCustom' => '', 'rowBgPl' => '', 'rowBgPr' => '', 'color' => '', 'border' => '', 'borderPrimary' => false,
				'cover' => null, 'parallax' => false, 'dim' => false, 'padded' => false, 'radius' => '', 'contentPos' => '', 'inner' => false, 'tiles' => false, 'ratio' => '', 'whitespace' => '',
				...array_fill_keys( self::SPACING, '' ), 'text' => '', 'arrow' => false,
			] + self::animationDefaults() + [ 'custom' => '' ];
		}

		/**
		 *	A column's own settings, as a column without a modifier has them -
		 *	its width and hidden, which have no default, left out
		 *
		 *	@return 	array
		 */
		public static function colDefaults(): array {
			return [ 'color' => '', 'dim' => false, 'linked' => false, 'radius' => '', 'background' => null, 'text' => '', 'stackAlign' => '', 'stackGap' => '', ...array_fill_keys( self::SPACING, '' ), 'bgPl' => '', 'bgPr' => '' ] + self::animationDefaults() + [ 'custom' => '' ];
		}

		/**
		 *	@return 	array										The animation settings of a section or a column, off
		 */
		public static function animationDefaults(): array {
			return [ 'vpa' => null, 'vpaSpeed' => '', 'vpaMode' => '', 'vpaDelay' => '', 'vpaDuration' => '' ];
		}

		/**
		 *	@return 	array										The animation of a template as its wrap has it, off: the switch, the effect with its
		 *																	strength, the speed, and the other classes of the wrap
		 */
		public static function wrapDefaults(): array {
			return [ 'animate' => false, 'vpa' => '', 'vpaSpeed' => '', 'wrapClass' => '' ];
		}

		/**
		 *	The attributes of a component or a stack and what each is worth
		 *	where a call does not set it: the 'defaults' the Document puts in
		 *	the registry (the kernel's Components::defaults(), so a stack has
		 *	the loop's and the grid's too), else those the schema declares
		 *
		 *	@param		array			$schema				One entry of the registry
		 *
		 *	@return 	array										attribute => default, strings
		 */
		public static function defaults( array $schema ): array {

			if( is_array( $schema['defaults'] ?? null ) === true )
				return $schema['defaults'];

			$defaults = [];

			foreach( (array) ( $schema['attributes'] ?? [] ) as $attribute => $declared ) {
				$default = is_array( $declared ) === true ? ( $declared['default'] ?? '' ) : '';
				$defaults[$attribute] = is_bool( $default ) === true ? ( $default === true ? '1' : '0' ) : (string) $default;
			}

			return $defaults + [ 'class' => '' ];
		}

		/**
		 *	The two parts of an address that leads to a page of the site and goes on from it - the key that holds the address of the page, and the sub-path after it, which is
		 *	empty where the address is the page alone - written [[/nino/dir]][[<key>]]<sub-path>. The kernel puts the fills in before it reads the page: the directory the site is
		 *	served from, which the fill of the key has not (a bare key is given it by the kernel), and the address of the page, so the link leads to the page in the language the
		 *	visitor reads, and the sub-path with it; a fill of the element of a loop in the sub-path, [[.uri]], is the loop's. It is the one place a call may hold the brackets of
		 *	a fill: the value of a link (the address of a component, or an attribute of the type href), and only in this form
		 *
		 *	@param		string		$value
		 *
		 *	@return 	array|null								[ 'key' => ..., 'postfix' => ... ], null where the value is no such address
		 */
		public static function address( string $value ): ?array {

			if( preg_match( self::ADDRESS, $value, $match ) !== 1 )
				return null;

			return [ 'key' => $match[1], 'postfix' => $match[2] ];
		}

		/**
		 *	The attributes of a component or a stack that are the address of a link: the ones its schema declares of the type href, and - for a component the registry marks
		 *	as a link, which has the address and the window it opens in without declaring them - href. The Builder knows no component by name: this is what makes an
		 *	attribute an address, and the one place a call may hold the brackets of a fill (see address())
		 *
		 *	@param		array			$schema				One entry of the registry
		 *
		 *	@return 	array										The names
		 */
		public static function addresses( array $schema ): array {

			$names = [];

			foreach( (array) ( $schema['attributes'] ?? [] ) as $attribute => $declared )
				if( is_array( $declared ) === true && ( $declared['type'] ?? '' ) === 'href' )
					$names[] = (string) $attribute;

			if( ( $schema['link'] ?? false ) === true && in_array( 'href', $names, true ) === false )
				$names[] = 'href';

			return $names;
		}

		/**
		 *	Whether a component holds components: its schema says 'holds', the place of the ones it
		 *	holds. The Builder knows no component by name, and this is what makes one a container
		 *
		 *	@param		array			$schema				One entry of the registry
		 *
		 *	@return 	bool
		 */
		public static function holds( array $schema ): bool {
			return is_string( $schema['holds'] ?? null ) === true && $schema['holds'] !== '';
		}

		/**
		 *	Whether a component that holds components paints a surface of its own, by what its schema declares and not by its name: a ground - a select over the
		 *	grounds a column may paint, COL_COLOR with the empty word - or a picture behind it, an attribute of the type image: any such attribute counts, by its type and wherever it stands, as the panel's _pictures() and _look() find the picture of a holder. The card paints both. A holder that paints
		 *	neither lays out what it holds and nothing else, and has no corners to round and no ground whose picture could start further in
		 *
		 *	@param		array			$schema				One entry of the registry
		 *
		 *	@return 	bool
		 */
		public static function paints( array $schema ): bool {

			$grounds = [ '', ...self::COL_COLOR ];

			foreach( (array) ( $schema['attributes'] ?? [] ) as $declared ) {

				if( is_array( $declared ) === false )
					continue;

				if( ( $declared['type'] ?? '' ) === 'image' )
					return true;

				if( ( $declared['type'] ?? '' ) === 'select' && is_array( $declared['options'] ?? null ) === true && count( $declared['options'] ) === count( $grounds ) && array_diff( $grounds, $declared['options'] ) === [] )
					return true;
			}

			return false;
		}

		/**
		 *	The kind of surface a component is, by what the registry says of it and not by its name. A component that holds components and paints (see paints()) is a
		 *	card - the container of a page is the box that has corners; one that paints nothing is none. A component whose source is an image and which no card claims
		 *	(its 'in' names no place a card holds) is the frame of a picture. Any other, the picture of a card among them, is none: it
		 *	follows the surface it stands in. A component that is a surface has the setting radius, and the panel offers it the toggle
		 *	against the page's default for its kind
		 *
		 *	@param		array			$schema				One entry of the registry
		 *	@param		array			$components		All the components of the registry, name => schema
		 *
		 *	@return 	string										A value of SURFACES - 'card' or 'image' - or '' for none
		 */
		public static function surface( array $schema, array $components ): string {

			if( isset( $schema['source'] ) === false )
				return '';

			if( self::holds( $schema ) === true )
				return self::paints( $schema ) === true ? 'card' : '';

			if( $schema['source'] !== 'image' )
				return '';

			foreach( $components as $other )
				if( is_array( $other ) === true && self::holds( $other ) === true && self::paints( $other ) === true && in_array( $other['holds'], (array) ( $schema['in'] ?? [] ), true ) === true )
					return '';

			return 'image';
		}

		/**
		 *	Whether a source says the hover of a picture behind a class that the Writer puts after it. The Writer writes nino-hover as the first class of the call of a
		 *	picture, or behind the radius of one that has a radius; a call that says it after any other class, an editor's own among them, reads as the same setting and so
		 *	as the same model as the call the Writer writes, and a save has to write the file anew even where the model says that nothing changed (see Document::save()). It
		 *	looks at the text and not at the model, at the calls of the components whose schema says that their source is an image, and a call that is none of a picture's of
		 *	the page may make it say yes: the file is then written as the Writer writes it, as it is by any other save
		 *
		 *	@param		string		$source				The file
		 *	@param		array			$registry			[ 'components' => name => schema, 'stacks' => name => schema ]
		 *
		 *	@return 	bool
		 */
		public static function hoverBehind( string $source, array $registry ): bool {

			$components	= (array) ( $registry['components'] ?? [] );
			$pictures		= [];

			foreach( $components as $name => $schema )
				if( is_array( $schema ) === true && ( $schema['source'] ?? '' ) === 'image' )
					$pictures[(string) $name] = self::surface( $schema, $components ) !== '';

			if( $pictures === [] || preg_match_all( '~\[('. implode( '|', array_map( static fn( string $name ): string => preg_quote( $name, '~' ), array_keys( $pictures ) ) ). ')(?: ([^\]]*))?\]~', $source, $found, PREG_SET_ORDER ) === 0 )
				return false;

			foreach( $found as $call ) {

				try {
					$named = self::_arguments( $call[2] ?? '', $call[1], 0 )[1];
				}
				catch( \RuntimeException ) {
					continue;
				}

				$tokens	= preg_split( '/\s+/', trim( $named['class'] ?? '' ), -1, PREG_SPLIT_NO_EMPTY ) ?: [];
				$at			= array_search( self::HOVER_CLASS, $tokens, true );

				// It stands first, or behind the radius class of a picture that has a radius - the class of its kind that stands first is the one the Reader takes
				if( $at !== false && $at !== 0 && ( $at !== 1 || $pictures[$call[1]] === false || in_array( $tokens[0], self::RADIUS_CLASS, true ) === false ) )
					return true;
			}

			return false;
		}

		/**
		 *	The radius out of the class attribute of a call: the first of the two classes of the page, and the rest of the classes as they were. A
		 *	second class of the kind is a class of its own and stays. A call with none of them keeps its class attribute byte for byte
		 *
		 *	@param		string		$class				The class attribute of the call
		 *
		 *	@return 	array											[ '' or a value of RADIUS, the classes that are left ]
		 */
		private static function _radius( string $class ): array {

			$word		= '';
			$rest		= [];

			foreach( preg_split( '/\s+/', trim( $class ), -1, PREG_SPLIT_NO_EMPTY ) ?: [] as $token ) {

				$read = array_search( $token, self::RADIUS_CLASS, true );

				if( $read !== false && $word === '' )
					$word = (string) $read;
				else
					$rest[] = $token;
			}

			return $word === '' ? [ '', $class ] : [ $word, implode( ' ', $rest ) ];
		}

		/**
		 *	The hover out of the class attribute of a call: the first nino-hover, and the rest of the classes as they were. A second one is a class of its own and
		 *	stays. A call without it keeps its class attribute byte for byte
		 *
		 *	@param		string		$class				The class attribute of the call
		 *
		 *	@return 	array											[ '' or HOVER, the classes that are left ]
		 */
		private static function _hover( string $class ): array {

			$on		= false;
			$rest	= [];

			foreach( preg_split( '/\s+/', trim( $class ), -1, PREG_SPLIT_NO_EMPTY ) ?: [] as $token ) {

				if( $token === self::HOVER_CLASS && $on === false )
					$on = true;
				else
					$rest[] = $token;
			}

			return $on === false ? [ '', $class ] : [ self::HOVER, implode( ' ', $rest ) ];
		}

		/**
		 *	How far in from either side the picture behind an element starts, out of the class attribute of a call: the first class of each side, nino-bg-pl-<n> and
		 *	nino-bg-pr-<n>, and the rest of the classes as they were. A second class of a side is a class of its own and stays. A call with none of them keeps its
		 *	class attribute byte for byte
		 *
		 *	@param		string		$class				The class attribute of the call
		 *
		 *	@return 	array											[ side => step for each side that has a class, the classes that are left ]
		 */
		private static function _offsets( string $class ): array {

			$steps	= [];
			$rest		= [];

			foreach( preg_split( '/\s+/', trim( $class ), -1, PREG_SPLIT_NO_EMPTY ) ?: [] as $token ) {

				if( preg_match( '/^nino-bg-('. implode( '|', array_keys( self::BG_SPACING ) ). ')-('. implode( '|', self::BG_STEPS ). ')$/D', $token, $m ) === 1 && isset( $steps[$m[1]] ) === false )
					$steps[$m[1]] = $m[2];
				else
					$rest[] = $token;
			}

			return $steps === [] ? [ [], $class ] : [ $steps, implode( ' ', $rest ) ];
		}

		/**
		 *	The six sides of the spacing out of the classes of an element: the first class of a side is its setting, a second class of the side is a class of its
		 *	own and stays. Where $shorthand is set - on a tag - the kernel's nino-p-<n> is the four inside sides at once, the base that a class of one side stands
		 *	over, whatever the order of the two in the attribute (Nino.css writes the rules of one side after that of all four); a second nino-p-<n> stays as well
		 *
		 *	@param		array			$tokens				The classes
		 *	@param		bool			$shorthand		Whether nino-p-<n> says the four inside sides: on a tag it does, on a call it is a class of the editor's
		 *
		 *	@return 	array											[ side => step for each side that is set, the classes that are left ]
		 */
		private static function _sides( array $tokens, bool $shorthand ): array {

			$words	= implode( '|', $shorthand === true ? [ 'p', ...self::SPACING ] : self::SPACING );
			$sides	= [];
			$base		= null;
			$rest		= [];

			foreach( $tokens as $token ) {

				if( preg_match( '/^nino-('. $words. ')-([0-6])$/D', $token, $m ) === 1 ) {

					if( $m[1] === 'p' && $base === null ) {
						$base = $m[2];
						continue;
					}

					if( $m[1] !== 'p' && isset( $sides[$m[1]] ) === false ) {
						$sides[$m[1]] = $m[2];
						continue;
					}
				}

				$rest[] = $token;
			}

			if( $base !== null )
				foreach( self::INSIDE as $side )
					$sides[$side] ??= $base;

			return [ $sides, $rest ];
		}

		/**
		 *	The six sides of the spacing out of the class attribute of a call, as the radius is taken out of it: the first class of each side, and the rest of the
		 *	classes as they were. A call with none of them keeps its class attribute byte for byte
		 *
		 *	@param		string		$class				The class attribute of the call
		 *
		 *	@return 	array											[ side => step for each side that has a class, the classes that are left ]
		 */
		private static function _callSides( string $class ): array {

			[ $sides, $rest ] = self::_sides( preg_split( '/\s+/', trim( $class ), -1, PREG_SPLIT_NO_EMPTY ) ?: [], false );

			return $sides === [] ? [ [], $class ] : [ $sides, implode( ' ', $rest ) ];
		}

		/**
		 *	The stack whose cell is a whole section - the loop of sections. The schema says so ('cell' =>
		 *	'section', which the Document puts there, being the one place that knows the kernel's name for
		 *	it), and the Builder asks the schema and not the name
		 *
		 *	@param		array			$registry			[ 'components' => name => schema, 'stacks' => name => schema ]
		 *
		 *	@return 	string										Its name, '' where the registry has none
		 */
		public static function loopsSections( array $registry ): string {

			foreach( (array) ( $registry['stacks'] ?? [] ) as $name => $schema )
				if( is_array( $schema ) === true && ( $schema['cell'] ?? '' ) === 'section' )
					return (string) $name;

			return '';
		}

		/**
		 *	A page template read into its model
		 *
		 *	@param		string		$source				The file
		 *	@param		array			$registry			[ 'components' => name => schema, 'stacks' => name => schema ]
		 *
		 *	@return 	array										The model, see above
		 */
		public static function read( string $source, array $registry ): array {

			$model	= [ 'file' => '', 'name' => '' ] + self::wrapDefaults() + [ 'header' => '', 'footer' => '', 'blocks' => [] ];
			$pos		= 0;

			if( preg_match( '~\G\s*<!--[\t ]*nino:template-name[\t ]+([^\r\n<>]+?)[\t ]*-->[\t ]*(?:\r?\n|$)~', $source, $match, 0, $pos ) === 1 ) {
				$model['name'] = $match[1];
				$pos += strlen( $match[0] );
			}

			if( preg_match( '~\G\s*\[template /templates/(html-header(?:-[a-z0-9]+)*)\][\t ]*(?:\r?\n|$)~', $source, $match, 0, $pos ) === 1 ) {
				$model['header'] = $match[1];
				$pos += strlen( $match[0] );
			}

			$end = strlen( $source );

			if( preg_match( '~(?<![^\n])[\t ]*\[template /templates/(html-footer(?:-[a-z0-9]+)*)\]\s*\z~', $source, $match, PREG_OFFSET_CAPTURE, $pos ) === 1 ) {
				$model['footer'] = $match[1][0];
				$end = $match[0][1];
			}

			// The wrap, where the file has one: its classes are the animation of the template, and the blocks are what stands inside it
			$wrap = self::_wrap( $source, $pos, $end );

			if( $wrap !== null ) {
				$model	= array_replace( $model, $wrap['settings'] );
				$pos		= $wrap['from'];
				$end		= $wrap['to'];
			}

			$body		= substr( $source, 0, $end );
			$ids		= [];
			$pending = null;

			while( true ) {

				$start = self::_lineStart( $body, $pos );

				if( $start >= strlen( $body ) )
					break;

				$pos = $start;

				try {

					// A block the builder wrapped itself: recognised, not analysed
					if( preg_match( '~\G[\t ]*<!--[\t ]*nino:html[\t ]*-->~', $body, $open, 0, $pos ) === 1 ) {

						if( preg_match( '~<!--[\t ]*/nino:html[\t ]*-->~', $body, $closing, PREG_OFFSET_CAPTURE, $pos + strlen( $open[0] ) ) !== 1 )
							throw self::_fail( 'marker-unclosed', '', $pos );

						self::_flush( $model, $pending, $body, $pos );

						$inner = substr( $body, $pos + strlen( $open[0] ), $closing[0][1] - $pos - strlen( $open[0] ) );
						$inner = (string) preg_replace( '~\A\r?\n~', '', $inner );
						$inner = (string) preg_replace( '~\r?\n[\t ]*\z~', '', $inner );

						$model['blocks'][] = [ 'kind' => 'html', 'source' => $inner, 'reason' => null ];
						$pos = $closing[0][1] + strlen( $closing[0][0] );

						continue;
					}

					[ $section, $after ] = self::_section( $body, $pos, $registry, $ids );

					self::_flush( $model, $pending, $body, $pos );

					$ids[$section['id']] = true;
					$model['blocks'][] = $section;
					$pos = $after;

					continue;
				}
				catch( \RuntimeException $e ) {

					if( $pending === null )
						$pending = [ 'start' => $pos, 'reason' => self::_reason( $body, $e ) ];
				}

				$candidate = self::_candidate( $body, $pos, self::loopsSections( $registry ) );

				if( $candidate === null ) {
					self::_flush( $model, $pending, $body, strlen( $body ) );
					break;
				}

				$pos = $candidate;
			}

			self::_flush( $model, $pending, $body, strlen( $body ) );

			return $model;
		}

		/**
		 *	One block as a section, or why it is none - the way the Document
		 *	reads a block again after it was edited
		 *
		 *	@param		string		$source				The block
		 *	@param		array			$registry			See read()
		 *	@param		array			$taken				Ids the page has already: id => true
		 *
		 *	@return 	array										[ the section, null ] or [ null, the reason ]
		 */
		public static function readSection( string $source, array $registry, array $taken = [] ): array {

			try {
				[ $section, $end ] = self::_section( $source, self::_lineStart( $source, 0 ), $registry, $taken );
			}
			catch( \RuntimeException $e ) {
				return [ null, self::_reason( $source, $e ) ];
			}

			if( trim( substr( $source, $end ) ) !== '' )
				return [ null, self::_reason( $source, new \RuntimeException( 'section-content', $end ) ) ];

			return [ $section, null ];
		}

		/**
		 *	Close the foreign block that is being collected, if there is one:
		 *	from where the reading failed to where it is now
		 *
		 *	@param		array 		&$model				The model so far
		 *	@param		array|null &$pending			[ start, reason ]
		 *	@param		string		$body					The source
		 *	@param		int				$until				Where the next block starts
		 *
		 *	@param-out		null							$pending
		 *
		 *	@return 	void
		 */
		private static function _flush( array &$model, ?array &$pending, string $body, int $until ): void {

			if( $pending === null )
				return;

			$source = rtrim( substr( $body, $pending['start'], $until - $pending['start'] ), " \t\r\n" );

			if( $source !== '' )
				$model['blocks'][] = [ 'kind' => 'html', 'source' => $source, 'reason' => $pending['reason'] ];

			$pending = null;
		}

		/**
		 *	The start of the line that holds the first character that is no
		 *	whitespace at or after an offset - so a block keeps the
		 *	indentation of its first line
		 *
		 *	@param		string		$text
		 *	@param		int				$pos
		 *
		 *	@return 	int											strlen() where only whitespace is left
		 */
		private static function _lineStart( string $text, int $pos ): int {

			if( preg_match( '~\S~', $text, $match, PREG_OFFSET_CAPTURE, $pos ) !== 1 )
				return strlen( $text );

			$newline = strrpos( substr( $text, 0, $match[0][1] ), "\n" );

			return max( $pos, $newline === false ? 0 : $newline + 1 );
		}

		/**
		 *	Where the next block may start after a failed one: a line that
		 *	opens a <section>, a loop of sections or a marker, and that is not
		 *	inside the section that failed - the sections and the loops opened
		 *	since and not yet closed are counted
		 *
		 *	@param		string		$body
		 *	@param		int				$from					The start of the failed block
		 *	@param		string		$loop					The name of the loop of sections, '' where the registry has none
		 *
		 *	@return 	int|null								The start of the line, null where there is none
		 */
		private static function _candidate( string $body, int $from, string $loop = '' ): ?int {

			$newline = strpos( $body, "\n", $from );

			if( $newline === false )
				return null;

			$search	= $newline + 1;
			$open		= $loop === '' ? '' : '\['. preg_quote( $loop, '~' ). '(?=[ \]])';
			$close	= $loop === '' ? '' : '\[/'. preg_quote( $loop, '~' ). '\]';

			while( preg_match( '~(?<![^\n])[\t ]*(?:<(?:section\b|!--[\t ]*nino:html[\t ]*-->)'. ( $open === '' ? '' : '|'. $open ). ')~', $body, $match, PREG_OFFSET_CAPTURE, $search ) === 1 ) {

				$at			= (int) $match[0][1];
				$passed	= substr( $body, $from, $at - $from );
				$depth	= preg_match_all( '~<section\b~', $passed ) - preg_match_all( '~</section\s*>~', $passed );

				if( $loop !== '' )
					$depth += preg_match_all( '~'. $open. '~', $passed ) - preg_match_all( '~'. $close. '~', $passed );

				if( $depth <= 0 )
					return $at;

				$search = $at + strlen( $match[0][0] );
			}

			return null;
		}

		/**
		 *	The reason of a failure, as the model carries it
		 *
		 *	@param		string		$text					What was read
		 *	@param		\RuntimeException	$e		The failure: its message is code and detail, its code the offset
		 *
		 *	@return 	array
		 */
		private static function _reason( string $text, \RuntimeException $e ): array {

			[ $code, $detail ] = array_pad( explode( ':', $e->getMessage(), 2 ), 2, '' );

			$offset		= min( strlen( $text ), $e->getCode() );
			$sentence	= self::REASONS[$code] ?? '%s';
			$places		= substr_count( $sentence, '%s' );

			return [
				'line'		=> substr_count( $text, "\n", 0, $offset ) + 1,
				'code'		=> $code,
				'detail'	=> $detail,
				// A sentence with two places is told two things, which the detail holds with a line feed between
				'text'		=> vsprintf( $sentence, $places < 2 ? [ $detail ] : array_pad( explode( "\n", $detail, $places ), $places, '' ) ),
			];
		}

		/**
		 *	@param		string		$code
		 *	@param		string		$detail
		 *	@param		int				$offset
		 *
		 *	@return 	\RuntimeException
		 */
		private static function _fail( string $code, string $detail, int $offset ): \RuntimeException {
			return new \RuntimeException( $detail === '' ? $code : $code. ':'. $detail, $offset );
		}

		/**
		 *	One section, from the line it starts at: a <section>, or a loop of sections
		 *
		 *	@param		string		$body					The source
		 *	@param		int				$pos					Where the block starts
		 *	@param		array			$registry
		 *	@param		array			$ids					The ids of the sections before it: id => true
		 *
		 *	@return 	array										[ the section, where it ends ]
		 *
		 *	@throws 	\RuntimeException					The failure, see _fail()
		 */
		private static function _section( string $body, int $pos, array $registry, array $ids ): array {

			$pos		= self::_skip( $body, $pos );
			$loop		= self::loopsSections( $registry );

			// A loop of sections has no tag of its own: its call is the tag
			if( $loop !== '' && preg_match( '~\G\['. preg_quote( $loop, '~' ). '(?=[ \]])~', $body, $call, 0, $pos ) === 1 )
				return self::_loop( $body, $pos, $registry, $ids );

			[ $attributes, $pos ] = self::_open( $body, $pos, 'section', 'not-a-section' );

			foreach( array_keys( $attributes ) as $name )
				if( in_array( $name, [ 'id', 'class', 'data-cover-height' ], true ) === false )
					throw self::_fail( 'section-attribute', $name, $pos );

			$id = $attributes['id'] ?? '';

			if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $id ) !== 1 )
				throw self::_fail( 'section-id', '', $pos );

			if( isset( $ids[$id] ) === true )
				throw self::_fail( 'duplicate-id', $id, $pos );

			$tokens = self::_tokens( $attributes['class'] ?? '', $pos );

			if( in_array( 'nino-section', $tokens, true ) === false )
				throw self::_fail( 'section-class', '', $pos );

			// A scrim darkens the picture of the section itself, the block that stands before its row
			$settings = self::_sectionSettings( $tokens, $attributes, $pos, self::_isDiv( $body, self::_skip( $body, $pos ), 'nino-section-bg' ) );
			$section	= [ 'kind' => 'section', 'id' => $id, 'settings' => $settings, 'background' => null, 'stack' => null, 'cols' => [] ];
			$pos			= self::_skip( $body, self::_inside( $body, $pos, $registry, $section, false ) );

			if( preg_match( '~\G</section\s*>~', $body, $close, 0, $pos ) !== 1 ) {

				[ $tag, $tagAttributes ] = self::_peek( $body, $pos );

				throw self::_fail( $tag === 'div' && in_array( 'nino-grid-row', self::_tokens( $tagAttributes['class'] ?? '', $pos ), true ) === true ? 'second-row' : 'section-content', '', $pos );
			}

			return [ $section, $pos + strlen( $close[0] ) ];
		}

		/**
		 *	One loop of sections: the call stands where the tag of a section does, and what it holds
		 *	is what a section holds - the picture behind it, the row, its columns. The id and the
		 *	classes of the call are the section's (the kernel makes the ids of the sections of the id
		 *	and the number of the element), and it has no other attribute but those of the loop
		 *
		 *	@param		string		$body					The source
		 *	@param		int				$pos					Where the call starts
		 *	@param		array			$registry
		 *	@param		array			$ids					The ids of the sections before it: id => true
		 *
		 *	@return 	array										[ the section, where its closing tag ends ]
		 *
		 *	@throws 	\RuntimeException					The failure, see _fail()
		 */
		private static function _loop( string $body, int $pos, array $registry, array $ids ): array {

			[ $call, $end ] = self::_call( $body, $pos, 0, $registry, 'not-a-section', '', true );

			$node		= $call['node'];
			$id			= (string) $node['attributes']['id'];
			$class	= (string) $node['attributes']['class'];

			if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $id ) !== 1 )
				throw self::_fail( 'section-id', '', $pos );

			if( isset( $ids[$id] ) === true )
				throw self::_fail( 'duplicate-id', $id, $pos );

			// The id and the classes are the section's: the call keeps neither of them
			$node['attributes']['id']			= '';
			$node['attributes']['class']	= '';

			$content	= $call['content'];
			// A call that is never closed has no content, and a failure in it is where its tag ends
			$base			= $call['contentAt'] > 0 ? $call['contentAt'] : $end;
			$tokens		= self::_tokens( 'nino-section '. $class, $pos );
			$settings	= self::_sectionSettings( $tokens, [], $pos, self::_isDiv( $content, self::_skip( $content, 0 ), 'nino-section-bg' ) );
			$section	= [ 'kind' => 'section', 'id' => $id, 'settings' => $settings, 'background' => null, 'stack' => $node, 'cols' => [] ];

			try {

				$at = self::_skip( $content, self::_inside( $content, 0, $registry, $section, true ) );

				if( $at < strlen( $content ) ) {

					[ $tag, $tagAttributes ] = self::_peek( $content, $at );

					throw self::_fail( $tag === 'div' && in_array( 'nino-grid-row', self::_tokens( $tagAttributes['class'] ?? '', $at ), true ) === true ? 'sections-second-row' : 'sections-content', '', $at );
				}
			}
			catch( \RuntimeException $e ) {

				// Where it is in the source, and not in the content of the call alone
				throw new \RuntimeException( $e->getMessage(), $base + $e->getCode() );
			}

			return [ $section, $end ];
		}

		/**
		 *	What a section holds, wherever its tag is - the one of a <section> and the call of a loop of
		 *	sections hold the same: the picture behind it, then its row, with the columns in it, then
		 *	its arrow, the one call that may stand after the row
		 *
		 *	@param		string		$body					The source, or the content of the call of a loop of sections
		 *	@param		int				$pos					Where the tag of the section ends
		 *	@param		array			$registry
		 *	@param		array 		&$section			The section as far as it is read: its background, its columns, its arrow and the settings of its row are set in it
		 *	@param		bool			$loop					Whether the section is a loop of sections
		 *
		 *	@return 	int											Where the row ends - or the arrow after it, where there is one
		 *
		 *	@throws 	\RuntimeException					The failure, see _fail()
		 */
		private static function _inside( string $body, int $pos, array $registry, array &$section, bool $loop ): int {

			$pos = self::_skip( $body, $pos );

			[ $tag, $tagAttributes, $after ] = self::_peek( $body, $pos );

			if( $tag === 'div' && in_array( 'nino-section-bg', self::_tokens( $tagAttributes['class'] ?? '', $pos ), true ) === true ) {

				if( $section['settings']['tiles'] === true )
					throw self::_fail( 'tiles-background', 'nino-section-bg', $pos );

				[ $section['background'], $pos ] = self::_background( $body, $pos, $registry, 'nino-section-bg' );
				$pos = self::_skip( $body, $pos );
				[ $tag, $tagAttributes, $after ] = self::_peek( $body, $pos );
			}

			if( $tag !== 'div' || in_array( 'nino-grid-row', self::_tokens( $tagAttributes['class'] ?? '', $pos ), true ) === false )
				throw self::_fail( $loop === true ? 'sections-no-row' : 'no-row', '', $pos );

			// The row has the animation of the section: its classes among the others, its delay and its duration as the attributes of its tag
			foreach( array_keys( $tagAttributes ) as $name )
				if( in_array( $name, [ 'class', 'data-vpa-delay', 'data-vpa-duration' ], true ) === false )
					throw self::_fail( 'row-attribute', $name, $pos );

			$rowTokens	= self::_tokens( $tagAttributes['class'], $pos );
			$rowAt			= $pos;
			$pos				= self::_skip( $body, $after );
			$picture		= null;

			// The picture behind the row is the first thing in it, as the one behind the section is the first before its row
			if( self::_isDiv( $body, $pos, 'nino-grid-row-bg' ) === true && $section['settings']['tiles'] === true )
				throw self::_fail( 'tiles-background', 'nino-grid-row-bg', $pos );

			if( self::_isDiv( $body, $pos, 'nino-grid-row-bg' ) === true )
				[ $picture, $pos ] = self::_background( $body, $pos, $registry, 'nino-grid-row-bg' );

			self::_rowSettings( $rowTokens, $tagAttributes, $section, $picture, $rowAt );

			while( true ) {

				$pos = self::_skip( $body, $pos );

				if( preg_match( '~\G</div\s*>~', $body, $close, 0, $pos ) === 1 ) {
					$pos += strlen( $close[0] );
					break;
				}

				if( self::_isDiv( $body, $pos, 'nino-grid-row-bg' ) === true )
					throw self::_fail( 'row-background', '', $pos );

				[ $col, $pos ] = self::_col( $body, $pos, $registry, $loop, $section['settings']['tiles'] );
				$section['cols'][] = $col;
			}

			if( $section['cols'] === [] )
				throw self::_fail( 'no-cols', '', $pos );

			// The arrow of the section is the call after its row, whitespace between: exactly the call, one with an attribute is content the builder does not keep
			$pos = self::_skip( $body, $pos );

			if( preg_match( '~\G\['. preg_quote( self::ARROW, '~' ). '\]~', $body, $arrow, 0, $pos ) === 1 ) {
				$section['settings']['arrow'] = true;
				$pos += strlen( $arrow[0] );
			}

			return $pos;
		}

		/**
		 *	@param		string		$body
		 *	@param		int				$pos
		 *
		 *	@return 	int											The first position at or after $pos that is no whitespace
		 */
		private static function _skip( string $body, int $pos ): int {
			return $pos + strspn( $body, " \t\r\n", $pos );
		}

		/**
		 *	The opening tag at a position: its name and attributes - without
		 *	consuming it
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *
		 *	@return 	array										[ tag name or '', attributes, position after the tag ]
		 */
		private static function _peek( string $body, int $pos ): array {

			try {
				if( preg_match( '~\G<([a-zA-Z][a-zA-Z0-9]*)~', $body, $match, 0, $pos ) !== 1 )
					return [ '', [], $pos ];

				[ $attributes, $after ] = self::_open( $body, $pos, strtolower( $match[1] ), 'not-a-section' );

				return [ strtolower( $match[1] ), $attributes, $after ];
			}
			catch( \RuntimeException ) {
				return [ '', [], $pos ];
			}
		}

		/**
		 *	Whether the tag at a position is a div with a class, by the words of its
		 *	class attribute - the others it has are not looked at, so that a class the
		 *	builder cannot keep does not change the reason a column fails with
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *	@param		string		$class
		 *
		 *	@return 	bool
		 */
		private static function _isDiv( string $body, int $pos, string $class ): bool {

			[ $tag, $attributes ] = self::_peek( $body, $pos );

			return $tag === 'div' && in_array( $class, preg_split( '/\s+/', trim( $attributes['class'] ?? '' ), -1, PREG_SPLIT_NO_EMPTY ) ?: [], true );
		}

		/**
		 *	An opening tag, with attributes written as name="value"
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *	@param		string		$tag					The tag it has to be
		 *	@param		string		$code					What fails where it is another one
		 *
		 *	@return 	array										[ attributes, the position after the tag ]
		 */
		private static function _open( string $body, int $pos, string $tag, string $code ): array {

			if( preg_match( '~\G<'. $tag. '((?:\s+[a-zA-Z][-a-zA-Z0-9_:.]*(?:="[^"]*")?)*)\s*>~', $body, $match, 0, $pos ) !== 1 ) {

				if( preg_match( '~\G<'. $tag. '\b~', $body, $loose, 0, $pos ) === 1 )
					throw self::_fail( 'attribute-syntax', '', $pos );

				throw self::_fail( $code, '', $pos );
			}

			$attributes = [];

			if( preg_match_all( '~\s+([a-zA-Z][-a-zA-Z0-9_:.]*)(?:="([^"]*)")?~', $match[1], $found, PREG_SET_ORDER ) > 0 )
				foreach( $found as $attribute ) {

					if( isset( $attribute[2] ) === false )
						throw self::_fail( 'attribute-syntax', '', $pos );

					$attributes[strtolower( $attribute[1] )] = $attribute[2];
				}

			return [ $attributes, $pos + strlen( $match[0] ) ];
		}

		/**
		 *	The classes of a class attribute, each one a token the builder can
		 *	write back
		 *
		 *	@param		string		$class
		 *	@param		int				$pos
		 *
		 *	@return 	array
		 */
		private static function _tokens( string $class, int $pos ): array {

			$tokens = preg_split( '/\s+/', trim( $class ), -1, PREG_SPLIT_NO_EMPTY ) ?: [];

			foreach( $tokens as $token )
				if( preg_match( '/^[A-Za-z0-9_:.\/-]+$/D', $token ) !== 1 )
					throw self::_fail( 'class-token', $token, $pos );

			return array_values( array_unique( $tokens ) );
		}

		/**
		 *	The settings of a section: its classes and the height of its cover. The
		 *	animation is not among them: it stands on the row (see _rowSettings()), and
		 *	a class of it on the section is a custom class like any other
		 *
		 *	@param		array			$tokens				The classes
		 *	@param		array			$attributes		The attributes of the tag
		 *	@param		int				$pos
		 *	@param		bool			$pictured			Whether the section has a block of its own for a picture: only then is there something for nino-section--dim to darken
		 *
		 *	@return 	array
		 */
		private static function _sectionSettings( array $tokens, array $attributes, int $pos, bool $pictured = false ): array {

			$s				= self::sectionDefaults();
			$custom		= [];
			$dim			= [];
			$padded		= [];
			$radii		= [];
			$places	= [];
			$default	= $s;

			// The spacing is read apart from the other classes: the shorthand of the four inside sides is the base of them, wherever it stands
			[ $sides, $tokens ] = self::_sides( $tokens, true );
			$s = array_replace( $s, $sides );

			// A section of tiles is the second kind: what only the content kind has - a cover, a parallax, a scrim, a padded cover, the place of its content - is a custom class
			// on it, and a picture block of its own is none (the section fails where it stands, see _inside()). The ratio is read after the loop: it stands where the section says tiles
			$tiles		= in_array( 'nino-tiles', $tokens, true ) === true;
			$ratios	= [];

			// A second modifier of a kind that has one place is a class of its
			// own: kept, not lost
			$set = static function( string $key, mixed $value, string $token ) use ( &$s, &$custom, $default ): void {

				if( $s[$key] !== $default[$key] )
					$custom[] = $token;
				else
					$s[$key] = $value;
			};

			foreach( $tokens as $token ) {

				if( $token === 'nino-section' )
					continue;

				if( preg_match( '/^nino-section--(alt|tint|dark|black|primary|brand-alt)$/D', $token, $m ) === 1 )
					$set( 'color', $m[1], $token );
				elseif( preg_match( '/^nino-section--border-(1|2|3)$/D', $token, $m ) === 1 )
					$set( 'border', $m[1], $token );
				elseif( $token === 'nino-section--border-primary' )
					$set( 'borderPrimary', true, $token );
				elseif( $token === 'nino-tiles' )
					$set( 'tiles', true, $token );
				elseif( preg_match( '/^nino-tiles--('. implode( '|', self::RATIO ). ')$/D', $token, $m ) === 1 )
					$ratios[] = $token;
				elseif( $tiles === true && ( in_array( $token, [ 'nino-cover', 'nino-parallex', 'nino-cover--dim', 'nino-parallex--dim', 'nino-section--dim', 'nino-cover--padded' ], true ) === true
					|| preg_match( '/^nino-cover-('. implode( '|', self::CONTENT_POS ). ')$/D', $token ) === 1 ) )
					$custom[] = $token;
				// A cover that names no height is as high as Nino.ui.js makes it: 90 percent of the window
				elseif( $token === 'nino-cover' )
					$set( 'cover', 90, $token );
				elseif( $token === 'nino-parallex' )
					$set( 'parallax', true, $token );
				elseif( $token === 'nino-cover--dim' || $token === 'nino-parallex--dim' || $token === 'nino-section--dim' )
					$dim[] = $token;
				elseif( $token === 'nino-cover--padded' )
					$padded[] = $token;
				// The radius is decided below, with the padded cover it belongs to: it stands among the custom classes until then
				elseif( in_array( $token, self::RADIUS_CLASS, true ) === true ) {
					$radii[] = $token;
					$custom[] = $token;
				}
				elseif( preg_match( '/^nino-whitespace--('. implode( '|', self::WHITESPACE ). ')$/D', $token, $m ) === 1 )
					$set( 'whitespace', $m[1], $token );
				elseif( preg_match( '/^nino-cover-('. implode( '|', self::CONTENT_POS ). ')$/D', $token, $m ) === 1 )
					$places[] = [ $m[1], $token ];
				elseif( preg_match( '/^nino-text-('. implode( '|', self::TEXT ). ')$/D', $token, $m ) === 1 )
					$set( 'text', $m[1], $token );
				else
					$custom[] = $token;
			}

			// The height of the tiles: the first ratio class is the setting while the section is tiles, a second one is a class of its own, and on a section that is no
			// tiles section the class is idle and stays a custom one
			foreach( $ratios as $token )
				if( $s['tiles'] === true && $s['ratio'] === '' )
					$s['ratio'] = substr( $token, strlen( 'nino-tiles--' ) );
				else
					$custom[] = $token;

			// The place of the content is the row's in a cover or a parallax: on a section that is neither the Builder writes none,
			// and one that is there is kept as a custom class (the kernel reads it on a section with a background picture too)
			foreach( $places as [ $word, $token ] )
				if( $s['cover'] !== null || $s['parallax'] === true )
					$set( 'contentPos', $word, $token );
				else
					$custom[] = $token;

			// Only a cover and a parallax keep the room of the page around them: on a section that is neither the class is idle, a custom one
			foreach( $padded as $token )
				if( $s['cover'] !== null || $s['parallax'] === true )
					$set( 'padded', true, $token );
				else
					$custom[] = $token;

			// The radius is the padded cover's: the first of the two classes is the setting, a second is a class of its own as in any kind, and on a section
			// that is no padded cover both are idle and stay where they stood among the custom classes
			if( $s['padded'] === true && $radii !== [] ) {
				$s['radius']	= (string) array_search( $radii[0], self::RADIUS_CLASS, true );
				$custom				= array_values( array_diff( $custom, [ $radii[0] ] ) );
			}

			// The scrim is the cover's where the section is one, else the parallax's, else - where a picture of its own stands behind it - the section's:
			// any other dim class is a custom one
			foreach( $dim as $token )
				if( ( $token === 'nino-cover--dim' && $s['cover'] !== null )
					|| ( $token === 'nino-parallex--dim' && $s['parallax'] === true && $s['cover'] === null )
					|| ( $token === 'nino-section--dim' && $s['cover'] === null && $s['parallax'] === false && $pictured === true ) )
					$s['dim'] = true;
				else
					$custom[] = $token;

			if( isset( $attributes['data-cover-height'] ) === true ) {

				// The height is the cover's: the Writer would leave it out of a section that is none, so the Reader does not take it
				if( $s['cover'] === null )
					throw self::_fail( 'section-attribute', 'data-cover-height', $pos );

				if( preg_match( '/^[1-9][0-9]{0,3}$/D', $attributes['data-cover-height'] ) !== 1 )
					throw self::_fail( 'attribute-value', 'data-cover-height', $pos );

				$s['cover'] = (int) $attributes['data-cover-height'];
			}

			$s['custom'] = implode( ' ', $custom );

			return $s;
		}

		/**
		 *	The classes and the attributes of the row: its width and its place, the alignment of its columns, its padding, its ground, the
		 *	scrim over its picture, how far in from either side that picture starts, the animation of the section, and the rest. The
		 *	ground and the picture are the row's where nothing of the kind is the section's - no colour, no picture, no cover, no
		 *	parallax: then the colour, the picture and the scrim are the section's keys all the same, and inner says where they stand.
		 *	A ground or a scrim that cannot be kept so is a custom class of the row, a picture is a failure
		 *
		 *	@param		array			$tokens
		 *	@param		array			$attributes		The attributes of the tag: the delay and the duration of the animation, the class is $tokens
		 *	@param		array 		&$section			The section as far as it is read: its settings, and its background where it has the row's
		 *	@param		array|null	$picture		The block behind the row, null where it has none
		 *	@param		int				$at						Where the row starts, for a failure
		 *
		 *	@return 	void
		 *
		 *	@throws 	\RuntimeException					A picture behind the row of a section that has a colour, a picture, a cover or a parallax of its own, a delay or a duration that is no css time
		 */
		private static function _rowSettings( array $tokens, array $attributes, array &$section, ?array $picture, int $at ): void {

			$s				= &$section['settings'];
			$custom		= [];
			$radii		= [];
			$turned		= [];
			$tiles		= $s['tiles'] === true;
			// A row that paints a ground of its own is the content kind's: on a section of tiles no ground, no scrim, no width and no place of the row is read - they are the
			// row's custom classes - and its inner is never set
			$free			= $s['cover'] === null && $s['parallax'] === false && $s['color'] === '' && $section['background'] === null && $tiles === false;

			if( $picture !== null ) {

				if( $free === false )
					throw self::_fail( 'row-background', '', $at );

				$section['background']	= $picture;
				$s['inner']							= true;
			}

			foreach( $tokens as $token ) {

				if( $token === 'nino-grid-row' )
					continue;

				if( preg_match( '/^nino-grid-row--('. implode( '|', self::ROW ). ')$/D', $token, $m ) === 1 && $s['row'] === '' && $tiles === false )
					$s['row'] = $m[1];
				elseif( preg_match( '/^nino-grid-row--('. implode( '|', self::ROW_PLACE ). ')$/D', $token, $m ) === 1 && $s['rowPlace'] === '' && $tiles === false )
					$s['rowPlace'] = $m[1];
				elseif( preg_match( '/^nino-grid-('. implode( '|', self::ROW_ALIGN ). ')$/D', $token, $m ) === 1 && $s['rowAlign'] === '' )
					$s['rowAlign'] = $m[1];
				// The order of the columns, for each viewport by itself, in the builder's own vocabulary: the base class says the narrowest viewport, a class of the m and of the l tier the
				// other two, and the first class of a viewport is the setting, a second one is a class of the row's own. The classes of the s and of the xl tier are no setting of the
				// builder (the narrowest viewport has the base class, xl follows l) and neither is a bare --forward: they stay classes of the row's own, kept as they were
				elseif( preg_match( '/^nino-grid-row--(?:(m|l)-)?(reverse|forward)$/D', $token, $m ) === 1 && ( $m[1] !== '' || $m[2] === 'reverse' ) ) {
					$viewport = $m[1] === '' ? 's' : $m[1];

					if( isset( $turned[$viewport] ) === false )
						$turned[$viewport] = $m[2] === 'reverse';
					else
						$custom[] = $token;
				}
				// The gap between the columns is the first class of its kind, as the gap of a stack is a column's: a second is a class of the row's own
				elseif( preg_match( '/^nino-grid-gap-([0-6])$/D', $token, $m ) === 1 && $s['rowGap'] === '' )
					$s['rowGap'] = $m[1];
				// The padding of a side is the first class of its kind, a second is a class of its own as on a section
				elseif( preg_match( '/^nino-('. implode( '|', array_keys( self::ROW_SPACING ) ). ')-([0-6])$/D', $token, $m ) === 1 && $s[self::ROW_SPACING[$m[1]]] === '' )
					$s[self::ROW_SPACING[$m[1]]] = $m[2];
				// The same for how far in from a side the picture starts: it has one only where the picture is inside, else the class is idle and stays a custom one
				elseif( $picture !== null && preg_match( '/^nino-bg-('. implode( '|', array_keys( self::ROW_BG_SPACING ) ). ')-('. implode( '|', self::BG_STEPS ). ')$/D', $token, $m ) === 1 && $s[self::ROW_BG_SPACING[$m[1]]] === '' )
					$s[self::ROW_BG_SPACING[$m[1]]] = $m[2];
				elseif( preg_match( '/^nino-grid-row--('. implode( '|', self::COLOR ). ')$/D', $token, $m ) === 1 && $free === true && $s['color'] === '' ) {
					$s['color']	= $m[1];
					$s['inner']	= true;
				}
				elseif( $token === 'nino-grid-row--dim' && $picture !== null )
					$s['dim'] = true;
				elseif( in_array( $token, self::RADIUS_CLASS, true ) === true ) {
					$radii[] = $token;
					$custom[] = $token;
				}
				elseif( self::_animation( $token, $s ) === false )
					$custom[] = $token;
			}

			// A viewport that names nothing has the value of the one before it, the narrowest has none
			$before = false;

			foreach( self::VIEWPORTS as $viewport )
				$before = $s['reverse'][$viewport] = $turned[$viewport] ?? $before;

			// The radius is the radius of what the row paints, which the background inside gives it: the first of the two classes is the setting then, and on a row that
			// paints nothing both are idle and stay custom classes
			if( $s['inner'] === true && $radii !== [] ) {
				$s['rowRadius']	= (string) array_search( $radii[0], self::RADIUS_CLASS, true );
				$custom					= array_values( array_diff( $custom, [ $radii[0] ] ) );
			}

			$s['rowCustom'] = implode( ' ', $custom );

			self::_animationAttributes( $attributes, $s, $at );
		}

		/**
		 *	One class that belongs to the animation, set into the settings of
		 *	a section or a column
		 *
		 *	@param		string		$token
		 *	@param		array 		&$s
		 *
		 *	@return 	bool										Whether it was one
		 */
		private static function _animation( string $token, array &$s ): bool {

			if( $token === 'nino-vpa' ) {
				$s['vpa'] ??= '';
				return true;
			}

			if( preg_match( '/^nino-vpa--(.+)$/D', $token, $m ) !== 1 )
				return false;

			if( in_array( $m[1], self::EFFECTS, true ) === true && ( $s['vpa'] ?? '' ) === '' ) {
				$s['vpa'] = $m[1];
				return true;
			}

			if( preg_match( '/^speed-(fast|medium|slow)$/D', $m[1], $speed ) === 1 && $s['vpaSpeed'] === '' ) {
				$s['vpa'] ??= '';
				$s['vpaSpeed'] = $speed[1];
				return true;
			}

			if( in_array( $m[1], self::MODES, true ) === true && $s['vpaMode'] === '' ) {
				$s['vpa'] ??= '';
				$s['vpaMode'] = $m[1];
				return true;
			}

			return false;
		}

		/**
		 *	The two attributes of the animation
		 *
		 *	@param		array			$attributes
		 *	@param		array 		&$s
		 *	@param		int				$pos
		 *
		 *	@return 	void
		 */
		private static function _animationAttributes( array $attributes, array &$s, int $pos ): void {

			foreach( [ 'data-vpa-delay' => 'vpaDelay', 'data-vpa-duration' => 'vpaDuration' ] as $attribute => $key ) {

				if( isset( $attributes[$attribute] ) === false )
					continue;

				if( preg_match( '/^[0-9]+(?:\.[0-9]+)?(?:ms|s)$/D', $attributes[$attribute] ) !== 1 )
					throw self::_fail( 'attribute-value', $attribute, $pos );

				$s[$key] = $attributes[$attribute];
				$s['vpa'] ??= '';
			}
		}

		/**
		 *	The wrap of a page, where it has one: its first line is the line after
		 *	the head, its last the line before the foot, each of nothing but its
		 *	tag. Where either is missing, or the opening one has an attribute
		 *	beside its class or a class that cannot be kept, the file has no wrap
		 *
		 *	@param		string		$source				The file
		 *	@param		int				$pos					Where the head ends
		 *	@param		int				$end					Where the foot starts, the end of the file where it has none
		 *
		 *	@return 	array|null								[ 'settings' => animate, vpa, vpaSpeed and wrapClass of its classes, 'from' => where
		 *																	the blocks start, 'to' => where they end ], null where there is no wrap
		 */
		private static function _wrap( string $source, int $pos, int $end ): ?array {

			$body	= rtrim( substr( $source, 0, $end ), " \t\r\n" );
			$at		= self::_skip( $body, $pos );

			// A file that ends with its head has nothing to wrap
			if( $at >= strlen( $body ) )
				return null;

			try {
				[ $attributes, $after ] = self::_open( $body, $at, 'div', 'not-a-section' );
				$tokens = self::_tokens( $attributes['class'] ?? '', $at );
			}
			catch( \RuntimeException ) {
				return null;
			}

			if( array_keys( $attributes ) !== [ 'class' ] || in_array( self::WRAP, $tokens, true ) === false )
				return null;

			if( preg_match( '~\G[\t ]*(?:\r?\n|\z)~', $body, $line, 0, $after ) !== 1 )
				return null;

			$from = $after + strlen( $line[0] );

			if( preg_match( '~(?<![^\n])[\t ]*</div[\t ]*>\z~', $body, $close, PREG_OFFSET_CAPTURE, $from ) !== 1 )
				return null;

			return [ 'settings' => self::_wrapSettings( $tokens ), 'from' => $from, 'to' => (int) $close[0][1] ];
		}

		/**
		 *	What the classes of a wrap say: whether its sections are animated, the
		 *	effect with its strength, the speed - and, as they are written, the rest
		 *
		 *	@param		array			$tokens				The classes
		 *
		 *	@return 	array										animate, vpa, vpaSpeed, wrapClass
		 */
		private static function _wrapSettings( array $tokens ): array {

			$s			= self::wrapDefaults();
			$custom	= [];

			foreach( $tokens as $token ) {

				if( $token === self::WRAP )
					continue;

				if( $token === self::WRAP_VPA && $s['animate'] === false )
					$s['animate'] = true;
				elseif( preg_match( '/^nino-vpa--(.+)$/D', $token, $m ) === 1 && in_array( $m[1], self::EFFECTS, true ) === true && $s['vpa'] === '' )
					$s['vpa'] = $m[1];
				elseif( preg_match( '/^nino-vpa--speed-(fast|medium|slow)$/D', $token, $m ) === 1 && $s['vpaSpeed'] === '' )
					$s['vpaSpeed'] = $m[1];
				else
					$custom[] = $token;
			}

			$s['wrapClass'] = implode( ' ', $custom );

			return $s;
		}

		/**
		 *	The background block of a section or a column
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *	@param		array			$registry
		 *	@param		string		$class				nino-section-bg or nino-grid-bg: the class that makes the block one
		 *
		 *	@return 	array										[ [ slot, focus ], the position after it ]
		 */
		private static function _background( string $body, int $pos, array $registry, string $class ): array {

			[ $attributes, $pos ] = self::_open( $body, $pos, 'div', 'background-image' );

			$focus = null;

			foreach( array_keys( $attributes ) as $name )
				if( $name !== 'class' )
					throw self::_fail( 'background-class', $name, $pos );

			foreach( self::_tokens( $attributes['class'] ?? '', $pos ) as $token )
				if( $token === $class )
					continue;
				elseif( preg_match( '/^nino-img-focus--([1-9])$/D', $token, $m ) === 1 && $focus === null )
					$focus = (int) $m[1];
				else
					throw self::_fail( 'background-class', $token, $pos );

			$pos = self::_skip( $body, $pos );

			[ $call, $pos ] = self::_call( $body, $pos, 0, $registry );

			if( $call['stack'] === true || $call['node']['name'] !== 'image' || $call['node']['source'] === '' || $call['node']['source'][0] !== '/'
				|| $call['node']['text'] !== null || ( $call['node']['attributes']['alt'] ?? '' ) !== '' || ( $call['node']['radius'] ?? '' ) !== '' || ( $call['node']['hover'] ?? '' ) !== '' || implode( '', array_intersect_key( $call['node'], array_flip( self::SPACING ) ) ) !== '' )
				throw self::_fail( 'background-image', '', $pos );

			// Only the slot and the empty alt: any other attribute set would be lost in writing
			foreach( $call['node']['attributes'] as $attribute => $value )
				if( $attribute !== 'alt' && $value !== ( Reader::defaults( $registry['components']['image'] ?? [] )[$attribute] ?? '' ) )
					throw self::_fail( 'background-image', '', $pos );

			$pos = self::_skip( $body, $pos );

			if( preg_match( '~\G</div\s*>~', $body, $close, 0, $pos ) !== 1 )
				throw self::_fail( 'background-image', '', $pos );

			return [ [ 'slot' => $call['node']['source'], 'focus' => $focus ], $pos + strlen( $close[0] ) ];
		}

		/**
		 *	One column and what is in it
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *	@param		array			$registry
		 *	@param		bool			$loop					Whether the column is one of a loop of sections, whose cell it is: a loop of its own would be one inside a loop
		 *	@param		bool			$tiles				Whether the column is a tile, one of a section of tiles: nino-grid--linked is a setting of it
		 *
		 *	@return 	array										[ the column, the position after its closing tag ]
		 */
		private static function _col( string $body, int $pos, array $registry, bool $loop = false, bool $tiles = false ): array {

			[ $attributes, $after ] = self::_open( $body, $pos, 'div', 'row-content' );

			foreach( array_keys( $attributes ) as $name )
				if( $name !== 'class' )
					throw self::_fail( 'col-attribute', $name, $pos );

			$tokens = self::_tokens( $attributes['class'] ?? '', $pos );

			if( in_array( 'nino-grid-row', $tokens, true ) === true )
				throw self::_fail( 'nested-row', '', $pos );

			// The spacing is read apart from the other classes, as a section's is
			[ $sides, $tokens ] = self::_sides( $tokens, true );

			$col = array_replace( [ 'width' => [], 'hidden' => [] ] + self::colDefaults() + [ 'stack' => null, 'components' => [] ], $sides );
			$custom = [];
			$dim = false;
			$radii = [];
			$offsets = [];

			// Nino.css is mobile-first: nino-grid-100 holds in every viewport, a
			// prefixed width from its viewport up until a larger one takes over.
			// The model keeps the width each viewport ends up with. A width that
			// cannot be said that way - another unprefixed one than 100 - fails
			// the column, a second one for a viewport is kept as a class of its own
			$base		= null;
			$widths	= [];

			foreach( $tokens as $token ) {

				if( preg_match( '/^nino-grid-(?:(s|m|l)-)?(25|33|50|66|75|100)$/D', $token, $m ) === 1 ) {

					if( $m[1] === '' && $m[2] !== '100' )
						throw self::_fail( 'col-width', '', $pos );

					if( $m[1] === '' )
						$base = 100;
					elseif( isset( $widths[$m[1]] ) === false )
						$widths[$m[1]] = (int) $m[2];
					else
						$custom[] = $token;
				}
				elseif( preg_match( '/^nino-hide-(s|m|l)$/D', $token, $m ) === 1 )
					$col['hidden'][$m[1]] = true;
				elseif( preg_match( '/^nino-grid--(counter|alt|tint|dark|black|primary|brand-alt)$/D', $token, $m ) === 1 && $col['color'] === '' )
					$col['color'] = $m[1];
				elseif( $token === 'nino-grid--dim' ) {

					$dim = true;
					$custom[] = $token;
				}
				// The link over the whole of a tile is the tile's setting: on a column of a content section the class is a custom one
				elseif( $token === 'nino-grid--linked' && $tiles === true )
					$col['linked'] = true;
				elseif( in_array( $token, self::RADIUS_CLASS, true ) === true ) {

					$radii[] = $token;
					$custom[] = $token;
				}
				elseif( preg_match( '/^nino-bg-('. implode( '|', array_keys( self::BG_SPACING ) ). ')-('. implode( '|', self::BG_STEPS ). ')$/D', $token, $m ) === 1 ) {

					$offsets[$m[1]][] = $m[2];
					$custom[] = $token;
				}
				elseif( preg_match( '/^nino-stack-('. implode( '|', self::STACK_ALIGN ). ')$/D', $token, $m ) === 1 && $col['stackAlign'] === '' )
					$col['stackAlign'] = $m[1];
				elseif( preg_match( '/^nino-stack-gap-([0-6])$/D', $token, $m ) === 1 && $col['stackGap'] === '' )
					$col['stackGap'] = $m[1];
				elseif( preg_match( '/^nino-text-('. implode( '|', self::TEXT ). ')$/D', $token, $m ) === 1 && $col['text'] === '' )
					$col['text'] = $m[1];
				elseif( self::_animation( $token, $col ) === false )
					$custom[] = $token;
			}

			$width = [ 's' => $widths['s'] ?? $base ];
			$width['m'] = $widths['m'] ?? $width['s'];
			$width['l'] = $widths['l'] ?? $width['m'];

			if( $width['s'] === null )
				throw self::_fail( 'col-width', '', $pos );

			$col['width'] = $width;

			self::_animationAttributes( $attributes, $col, $pos );

			// The picture behind the column is the first thing in it, in a block of its own as a section has one before its row
			$pos = self::_skip( $body, $after );

			if( self::_isDiv( $body, $pos, 'nino-grid-bg' ) === true )
				[ $col['background'], $pos ] = self::_background( $body, $pos, $registry, 'nino-grid-bg' );

			// There is nothing to darken without a picture: the class of a column that has none is a class of its own, and stays where it stood among the others
			if( $dim === true && $col['background'] !== null ) {

				$col['dim'] = true;
				$custom = array_values( array_diff( $custom, [ 'nino-grid--dim' ] ) );
			}

			// The radius is the radius of what the column paints - a ground or a picture: the first of the two classes is the setting then, and on a column that
			// paints neither both are idle and stay custom classes
			if( $radii !== [] && ( $col['color'] !== '' || $col['background'] !== null ) ) {
				$col['radius']	= (string) array_search( $radii[0], self::RADIUS_CLASS, true );
				$custom					= array_values( array_diff( $custom, [ $radii[0] ] ) );
			}

			// How far in from a side the picture starts is the picture's: the first class of a side is the setting where the column has one, a second is a class of its
			// own, and without a picture - or on a tile, which has no offsets of its picture - both sides stay custom classes
			if( $col['background'] !== null && $tiles === false )
				foreach( $offsets as $side => $steps ) {
					$col[self::BG_SPACING[$side]]	= $steps[0];
					$custom												= array_values( array_diff( $custom, [ 'nino-bg-'. $side. '-'. $steps[0] ] ) );
				}

			$col['custom'] = implode( ' ', $custom );
			$calls = [];

			while( true ) {

				$pos = self::_skip( $body, $pos );

				if( preg_match( '~\G</div\s*>~', $body, $close, 0, $pos ) === 1 ) {
					$pos += strlen( $close[0] );
					break;
				}

				if( self::_isDiv( $body, $pos, 'nino-grid-bg' ) === true )
					throw self::_fail( 'grid-background', '', $pos );

				$at = $pos;
				[ $call, $pos ] = self::_call( $body, $pos, 0, $registry, 'col-markup' );
				$call['at'] = $at;

				if( $call['stack'] === true )
					$call['components'] = self::_sequence( $call['content'], $call['contentAt'], $registry );

				$calls[] = $call;
			}

			$stacks = array_filter( $calls, static fn( array $call ): bool => $call['stack'] === true );

			if( $stacks !== [] ) {

				if( $loop === true )
					throw self::_fail( 'stack-in-stack', '', $calls[array_key_first( $stacks )]['at'] );

				if( count( $calls ) > 1 )
					throw self::_fail( 'stack-neighbour', '', $calls[1]['at'] );

				$stack = $calls[0];
				$col['stack'] = $stack['node'];
				$col['components'] = $stack['components'];

				return [ $col, $pos ];
			}

			foreach( $calls as $call )
				$col['components'][] = $call['node'];

			return [ $col, $pos ];
		}

		/**
		 *	The calls of a stack's content, or of the content of a component that holds components,
		 *	and nothing else. A loop and a second container in the content of a container are no calls
		 *	that it holds (see _call())
		 *
		 *	@param		string		$content			The text between [stack] and [/stack], or between the tags of the container
		 *	@param		int				$base					Where it starts in the source, for the line of a failure
		 *	@param		array			$registry
		 *	@param		string		$holder				The name of the container whose content it is, '' for a stack's
		 *
		 *	@return 	array										The components
		 */
		private static function _sequence( string $content, int $base, array $registry, string $holder = '' ): array {

			$components	= [];
			$pos				= 0;

			while( true ) {

				$pos = self::_skip( $content, $pos );

				if( $pos >= strlen( $content ) )
					return $components;

				[ $call, $pos ] = self::_call( $content, $pos, $base, $registry, $holder === '' ? 'stack-content' : 'holder-content', $holder );

				if( $call['stack'] === true )
					throw self::_fail( 'stack-in-stack', '', $base + $pos );

				$components[] = $call['node'];
			}
		}

		/**
		 *	One shortcode call, read the way the kernel reads it
		 *
		 *	@param		string		$text					The text the call is in
		 *	@param		int				$pos					Where it starts
		 *	@param		int				$base					What to add to a position to get one of the source
		 *	@param		array			$registry
		 *	@param		string		$other				What fails where the text at $pos is no call
		 *	@param		string		$holder				The name of the container whose content this is, '' elsewhere: a loop and a container are no calls it holds
		 *	@param		bool			$whole				Whether the call is a loop of sections standing for the tag of a section, which is the only place for one: its attributes are the loop's and the section's alone
		 *
		 *	@return 	array										[ [ stack, node, content, contentAt ], the position after the call ]
		 */
		private static function _call( string $text, int $pos, int $base, array $registry, string $other = 'background-image', string $holder = '', bool $whole = false ): array {

			if( preg_match( '~\G\[([a-z][a-z0-9-]*)(?: ('. self::RAW. '))?\]~', $text, $head, 0, $pos ) !== 1 )
				throw self::_fail( $other, $holder === '' ? trim( substr( $text, $pos, 24 ) ) : $holder. "\n". (string) preg_replace( '/\s+/', ' ', trim( substr( $text, $pos, 24 ) ) ), $base + $pos );

			$name			= $head[1];
			$isStack	= isset( $registry['stacks'][$name] ) === true;

			if( $isStack === false && isset( $registry['components'][$name] ) === false )
				throw self::_fail( 'unknown-shortcode', $name, $base + $pos );

			$schema = $isStack === true ? $registry['stacks'][$name] : $registry['components'][$name];

			// A loop of sections is a section, and a section is not inside one
			if( $isStack === true && $whole === false && ( $schema['cell'] ?? '' ) === 'section' )
				throw self::_fail( 'sections-nested', '', $base + $pos );

			// A container holds the calls of components that hold none: not a loop, not a second container
			if( $holder !== '' && ( $isStack === true || self::holds( $schema ) === true ) )
				throw self::_fail( $isStack === true ? 'holder-stack' : 'holder-in-holder', '', $base + $pos );

			// The expression the kernel finds a call and its content with - but for the fills in the address of a link (see RAW), which the kernel has put in by then
			preg_match( '~\G\[('. preg_quote( $name, '~' ). ')(?: ('. self::RAW. '))?\](?:([^\[]*+(?:\[(?!\/\1\])[^\[]*+)*+)(?:\[\/\1\]))?~', $text, $match, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL, $pos );

			$raw				= $match[2][0] ?? '';
			$content		= $match[3][0] ?? null;
			$contentAt	= $match[3][1] ?? 0;
			$end				= $pos + strlen( $match[0][0] );

			[ $positional, $named ] = self::_arguments( $raw, $name, $base + $pos );

			$kind = $isStack === true ? 'stack' : (string) ( $schema['source'] ?? 'text' );

			if( count( $positional ) > ( $kind === 'none' || $kind === 'content' ? 0 : 1 ) )
				throw self::_fail( 'call-arguments', $name. ' takes no more than its source', $base + $pos );

			if( $isStack === true && ( $positional[0] ?? '' ) === '' )
				throw self::_fail( 'stack-source', '', $base + $pos );

			// A fill is part of a call only in the address of a link (see address()): in any other value the closing bracket of the fill would have ended the call where the
			// kernel, which had put the fills in, does not see one
			if( str_contains( (string) ( $positional[0] ?? '' ), ']' ) === true && ( $kind !== 'href' || self::address( $positional[0] ) === null ) )
				throw self::_fail( 'call-arguments', $name. ' '. $raw, $base + $pos );

			foreach( $named as $key => $value )
				if( str_contains( $value, ']' ) === true && ( in_array( (string) $key, self::addresses( $schema ), true ) === false || self::address( $value ) === null ) )
					throw self::_fail( 'call-arguments', $name. ' '. $raw, $base + $pos );

			$fixed = null;

			if( array_key_exists( 'text', $named ) === true ) {

				if( in_array( $kind, [ 'text', 'href', 'image' ], true ) === false )
					throw self::_fail( 'unknown-attribute', 'text', $base + $pos );

				$fixed = $named['text'];
				unset( $named['text'] );
			}

			// A component that is a surface has its radius apart from the rest of its classes
			$components = (array) ( $registry['components'] ?? [] );
			$surface = $isStack === true ? '' : self::surface( $schema, $components );

			$node = [
				'name'				=> $name,
				'source'			=> $positional[0] ?? '',
				'text'				=> $fixed,
				'attributes'	=> self::_attributes( $named, $schema, $base + $pos, $whole ),
			];

			if( $surface !== '' )
				[ $node['radius'], $node['attributes']['class'] ] = self::_radius( (string) ( $node['attributes']['class'] ?? '' ) );

			// A picture - any component whose source is an image, the picture of a card too, which is no surface - has the hover of the page apart from its classes
			if( $kind === 'image' )
				[ $node['hover'], $node['attributes']['class'] ] = self::_hover( (string) ( $node['attributes']['class'] ?? '' ) );

			// A card is the one surface that paints a ground: how far in from either side its picture starts is split off the classes as well, whatever the call says of a picture
			if( $surface === 'card' ) {

				[ $steps, $node['attributes']['class'] ] = self::_offsets( (string) ( $node['attributes']['class'] ?? '' ) );

				foreach( self::BG_SPACING as $side => $key )
					$node[$key] = $steps[$side] ?? '';
			}

			// Any component carries the six sides of the spacing in its class, after the radius and the offsets of the ground; a loop is no component and keeps its class attribute
			if( $isStack === false ) {

				[ $sides, $node['attributes']['class'] ] = self::_callSides( (string) ( $node['attributes']['class'] ?? '' ) );

				foreach( self::SPACING as $side )
					$node[$side] = $sides[$side] ?? '';
			}

			if( $isStack === true ) {

				unset( $node['text'] );

				return [ [ 'stack' => true, 'node' => $node, 'content' => (string) $content, 'contentAt' => $base + $contentAt ], $end ];
			}

			// What stands between the tags of a container is the calls of its children
			if( $kind === 'content' && self::holds( $schema ) === true )
				$node['components'] = self::_sequence( (string) $content, $base + $contentAt, $registry, $name );
			elseif( $kind === 'content' ) {

				$names = array_merge( array_keys( $components ), array_keys( (array) ( $registry['stacks'] ?? [] ) ) );

				if( $names !== [] && preg_match( '~\[(?:'. implode( '|', array_map( static fn( string $n ): string => preg_quote( $n, '~' ), $names ) ). ')(?: [^\]]*)?\]~', (string) $content ) === 1 )
					throw self::_fail( 'nested-call', $name, $base + $contentAt );

				$node['content'] = (string) $content;
			}
			elseif( $content !== null )
				throw self::_fail( 'component-content', $name, $base + $pos );

			return [ [ 'stack' => false, 'node' => $node, 'content' => '', 'contentAt' => 0 ], $end ];
		}

		/**
		 *	The arguments of a call as \Nino\Html splits them - and only where
		 *	reading what that makes back gives the text that was written
		 *
		 *	@param		string		$raw
		 *	@param		string		$name
		 *	@param		int				$offset
		 *
		 *	@return 	array										[ the positional arguments, the named ones ]
		 */
		private static function _arguments( string $raw, string $name, int $offset ): array {

			$positional	= [];
			$named			= [];
			$again			= [];

			if( $raw === '' )
				return [ $positional, $named ];

			preg_match_all( '/\ ([^\ \=]*)(\=[\"]([^\"]*)[\"])?/i', ' '. $raw. ' ', $found );

			// The expression ends on an empty match for the space it was given
			array_pop( $found[1] );

			foreach( $found[1] as $id => $key ) {

				if( $key === '' )
					throw self::_fail( 'call-arguments', $name. ' has an empty argument', $offset );

				if( $found[2][$id] !== '' ) {
					$named[$key] = $found[3][$id];
					$again[] = $key. '="'. $found[3][$id]. '"';
					continue;
				}

				$positional[] = $key;
				$again[] = $key;
			}

			if( implode( ' ', $again ) !== $raw )
				throw self::_fail( 'call-arguments', $name. ' '. $raw, $offset );

			return [ $positional, $named ];
		}

		/**
		 *	The attributes of a call against its schema: every one of the
		 *	schema, a string each, with the default where the call is silent
		 *
		 *	@param		array			$named
		 *	@param		array			$schema
		 *	@param		int				$offset
		 *	@param		bool			$whole				Whether the call is a loop of sections: an attribute it does not know is told as that, see _call()
		 *
		 *	@return 	array
		 */
		private static function _attributes( array $named, array $schema, int $offset, bool $whole = false ): array {

			$attributes = self::defaults( $schema );

			foreach( $named as $key => $value ) {

				if( array_key_exists( $key, $attributes ) === false )
					throw self::_fail( $whole === true ? 'sections-attribute' : 'unknown-attribute', (string) $key, $offset );

				$declared = $schema['attributes'][$key] ?? [];

				// Where it opens is the second implicit attribute of a component that is a link (the schema does not declare it)
				if( $declared === [] && $key === 'target' )
					$declared = [ 'type' => 'select', 'options' => self::TARGETS ];

				$type = (string) ( $declared['type'] ?? 'string' );

				$valid = match( $type ) {
					// A choice whose options a method of the feature answered (the schema carries their words, optionLabels) is the project's data: what a
					// call holds outside the list - the form that was deleted since - stays as it is, and the dialog says it is not among the options. The
					// word of a variant is written as the end of a class, so it stays only where it is a word (lower case letters and digits, hyphens between)
					'select', 'variant'	=> in_array( $value, (array) ( $declared['options'] ?? [] ), true )
						|| ( is_array( $declared['optionLabels'] ?? null ) === true && ( $type === 'select' || preg_match( self::WORD, $value ) === 1 ) ),
					'int'			=> preg_match( '/^-?\d{1,9}$/D', $value ) === 1 && (int) $value >= ( $declared['min'] ?? PHP_INT_MIN ) && (int) $value <= ( $declared['max'] ?? PHP_INT_MAX ),
					'bool'		=> $value === '0' || $value === '1',
					default		=> true,
				};

				if( $valid === false )
					throw self::_fail( 'attribute-value', (string) $key, $offset );

				$attributes[$key] = $value;
			}

			return $attributes;
		}
	}

}
