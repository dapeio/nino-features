<!-- The Builder's panel, rendered whole into the workbench's pane as a
     workspace (see Admin::template() and Admin::layout()): the shell, the
     rail, the account chrome and the head over the pane - the panel's name -
     are the workbench's, this file owns what is inside. #builder-root is what
     the panel's script works in; its data-dir is the project's directory,
     which the link to the real page starts from.

     Two screens - the list of the page templates and the editor, which is the
     preview and nothing beside it - and, below them, everything the script
     draws with: the one dialog and the dialog of a source that opens over it,
     the one menu, the icons and placeholders of the preview as a sprite, and
     the fragments it clones (a <template> each, one element in it). The
     script fills them with text and never writes markup of its own. -->
<div id="builder-root" data-dir="[[/nino/dir]]">

	<section id="builder-list" class="builder-screen">
		<div id="builder-templates"></div>
	</section>

	<section id="builder-editor" class="builder-screen builder-editor admin-hidden">

		<div class="builder-editor-head">
			<a id="builder-back" class="nino-admin-back-link" href="#builder">[[/_admin/common/label/back]]</a>
			<h2 id="builder-editor-title" class="builder-editor-title"></h2>
			<button type="button" id="builder-template-settings" class="builder-icon-btn" title="[[/_admin/builder/menu/template]]" aria-label="[[/_admin/builder/menu/template]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-gear"></use></svg></button>
			<a id="builder-open-page" class="builder-link" href="#" target="_blank" rel="noopener" hidden>[[/_admin/builder/label/open-page]]</a>
		</div>

		<p class="nino-admin-notice builder-narrow-note" role="note">[[/_admin/builder/state/narrow]]</p>
		<ul id="builder-problems" class="nino-admin-error builder-problems" hidden></ul>

		<section id="builder-preview-pane" class="builder-preview-pane" aria-label="[[/_admin/builder/label/preview]]">
			<div class="builder-pane-head">
				<h3 class="builder-pane-title">[[/_admin/builder/label/preview]]</h3>
				<div class="builder-viewports" role="group" aria-label="[[/_admin/builder/label/viewports]]">
					<button type="button" id="builder-viewport-s" class="builder-viewport" title="[[/_admin/builder/viewport/s]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-smartphone"></use></svg><span>[[/_admin/builder/viewport/s]]</span></button>
					<button type="button" id="builder-viewport-m" class="builder-viewport" title="[[/_admin/builder/viewport/m]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-tablet"></use></svg><span>[[/_admin/builder/viewport/m]]</span></button>
					<button type="button" id="builder-viewport-l" class="builder-viewport" title="[[/_admin/builder/viewport/l]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-monitor"></use></svg><span>[[/_admin/builder/viewport/l]]</span></button>
					<button type="button" id="builder-viewport-g" class="builder-viewport" title="[[/_admin/builder/viewport/g]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-monitor-smartphone"></use></svg><span>[[/_admin/builder/viewport/g]]</span></button>
				</div>
			</div>
			<div id="builder-preview" class="builder-preview" data-viewport="l"></div>
			<div class="builder-add-bar">
				<button type="button" id="builder-add-section" class="builder-add-button">+ [[/_admin/builder/tree/section]]</button>
				<button type="button" id="builder-add-html" class="builder-add-button">+ [[/_admin/builder/tree/html-block]]</button>
			</div>
			<div id="builder-menu" class="builder-menu" role="menu" hidden></div>
		</section>

		<div id="builder-bar" class="nino-admin-actionbar builder-bar">
			<code id="builder-editor-file" class="builder-bar-file"></code>
			<span id="builder-status"></span>
			<button type="button" id="builder-source" class="nino-admin-btn-secondary">[[/_admin/builder/label/source]]</button>
			<button type="button" id="builder-save" class="nino-admin-btn-primary">[[/_admin/common/label/save]]</button>
		</div>

	</section>

	<dialog id="builder-dialog" class="nino-admin-dialog builder-dialog" aria-labelledby="builder-dialog-title">
		<div class="nino-admin-dialog-body">
			<div class="builder-dialog-head">
				<div id="builder-dialog-heading" class="builder-dialog-heading">
					<h2 id="builder-dialog-title" class="nino-admin-dialog-title"><span id="builder-dialog-kind"></span> <span id="builder-dialog-name" class="builder-title-name" hidden></span></h2>
				</div>
				<button type="button" id="builder-dialog-close" class="builder-icon-btn" title="[[/_admin/builder/label/close]]" aria-label="[[/_admin/builder/label/close]]">&times;</button>
			</div>
			<div id="builder-dialog-tabs" class="builder-tabs" role="tablist" hidden></div>
			<div id="builder-dialog-content" class="builder-dialog-content"></div>
			<p id="builder-dialog-problems" class="nino-admin-error" role="alert" hidden></p>
			<div id="builder-dialog-actions" class="nino-admin-dialog-actions"></div>
		</div>
	</dialog>

	<!-- The dialog of a source: it opens over the one above, to choose a key, a slot or a value -->
	<dialog id="builder-picker" class="nino-admin-dialog builder-dialog builder-picker-dialog" aria-labelledby="builder-picker-title">
		<div class="nino-admin-dialog-body">
			<div class="builder-dialog-head">
				<h2 id="builder-picker-title" class="nino-admin-dialog-title">[[/_admin/builder/source/title]]</h2>
				<button type="button" id="builder-picker-close" class="builder-icon-btn" title="[[/_admin/builder/label/close]]" aria-label="[[/_admin/builder/label/close]]">&times;</button>
			</div>
			<div id="builder-picker-tabs" class="builder-tabs" role="tablist"></div>
			<div id="builder-picker-content" class="builder-dialog-content"></div>
		</div>
	</dialog>

	<svg class="builder-sprite" aria-hidden="true" focusable="false">
		<symbol id="builder-icon-gear" viewBox="0 0 24 24"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></symbol>
		<symbol id="builder-icon-up" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></symbol>
		<symbol id="builder-icon-down" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></symbol>
		<symbol id="builder-icon-arrow-up" viewBox="0 0 24 24"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></symbol>
		<symbol id="builder-icon-arrow-down" viewBox="0 0 24 24"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></symbol>
		<symbol id="builder-icon-copy" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></symbol>
		<symbol id="builder-icon-trash" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></symbol>
		<symbol id="builder-icon-code" viewBox="0 0 24 24"><path d="m16 18 6-6-6-6"/><path d="m8 6-6 6 6 6"/></symbol>
		<symbol id="builder-icon-warning" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></symbol>
		<symbol id="builder-icon-image" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></symbol>
		<symbol id="builder-icon-hidden" viewBox="0 0 24 24"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></symbol>
		<symbol id="builder-icon-smartphone" viewBox="0 0 24 24"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></symbol>
		<symbol id="builder-icon-tablet" viewBox="0 0 24 24"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/></symbol>
		<symbol id="builder-icon-monitor" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></symbol>
		<symbol id="builder-icon-monitor-smartphone" viewBox="0 0 24 24"><path d="M18 8V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h8"/><path d="M10 19v-3.96 3.15"/><path d="M7 19h5"/><rect width="6" height="10" x="16" y="12" rx="2"/></symbol>
		<symbol id="builder-icon-pencil" viewBox="0 0 24 24"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></symbol>
		<symbol id="builder-icon-align-left" viewBox="0 0 24 24"><path d="M21 5H3"/><path d="M15 12H3"/><path d="M17 19H3"/></symbol>
		<symbol id="builder-icon-align-center" viewBox="0 0 24 24"><path d="M21 5H3"/><path d="M17 12H7"/><path d="M19 19H5"/></symbol>
		<symbol id="builder-icon-align-right" viewBox="0 0 24 24"><path d="M21 5H3"/><path d="M21 12H9"/><path d="M21 19H7"/></symbol>
		<symbol id="builder-icon-align-top" viewBox="0 0 24 24"><rect width="6" height="16" x="4" y="6" rx="2"/><rect width="6" height="9" x="14" y="6" rx="2"/><path d="M22 2H2"/></symbol>
		<symbol id="builder-icon-align-middle" viewBox="0 0 24 24"><path d="M2 12h20"/><path d="M10 16v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4"/><path d="M10 8V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v4"/><path d="M20 16v1a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-1"/><path d="M14 8V7c0-1.1.9-2 2-2h2a2 2 0 0 1 2 2v1"/></symbol>
		<symbol id="builder-icon-align-bottom" viewBox="0 0 24 24"><rect width="6" height="16" x="4" y="2" rx="2"/><rect width="6" height="9" x="14" y="9" rx="2"/><path d="M22 22H2"/></symbol>
		<symbol id="builder-icon-stack-start" viewBox="0 0 24 24"><rect width="14" height="6" x="5" y="16" rx="2"/><rect width="10" height="6" x="7" y="6" rx="2"/><path d="M2 2h20"/></symbol>
		<symbol id="builder-icon-stack-center" viewBox="0 0 24 24"><rect width="14" height="6" x="5" y="16" rx="2"/><rect width="10" height="6" x="7" y="2" rx="2"/><path d="M2 12h20"/></symbol>
		<symbol id="builder-icon-stack-end" viewBox="0 0 24 24"><rect width="14" height="6" x="5" y="12" rx="2"/><rect width="10" height="6" x="7" y="2" rx="2"/><path d="M2 22h20"/></symbol>
		<symbol id="builder-icon-loop" viewBox="0 0 24 24"><path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/></symbol>
		<symbol id="builder-icon-source" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></symbol>
		<symbol id="builder-ph-title" viewBox="0 0 48 32"><rect x="4" y="7" width="32" height="7" rx="2" fill="currentColor" stroke="none"/><rect x="4" y="20" width="20" height="3" rx="1.5" fill="currentColor" stroke="none" opacity=".5"/></symbol>
		<symbol id="builder-ph-text" viewBox="0 0 48 32"><rect x="4" y="6" width="40" height="3" rx="1.5" fill="currentColor" stroke="none"/><rect x="4" y="14" width="40" height="3" rx="1.5" fill="currentColor" stroke="none" opacity=".7"/><rect x="4" y="22" width="26" height="3" rx="1.5" fill="currentColor" stroke="none" opacity=".5"/></symbol>
		<symbol id="builder-ph-image" viewBox="0 0 48 32"><rect x="4" y="4" width="40" height="24" rx="3"/><circle cx="15" cy="12" r="3"/><path d="m4 24 11-8 8 6 8-7 13 9"/></symbol>
		<symbol id="builder-ph-button" viewBox="0 0 48 32"><rect x="8" y="9" width="32" height="14" rx="7"/><rect x="16" y="15" width="16" height="2" rx="1" fill="currentColor" stroke="none"/></symbol>
		<symbol id="builder-ph-block" viewBox="0 0 48 32"><rect x="4" y="4" width="40" height="24" rx="3" stroke-dasharray="4 3"/></symbol>
		<symbol id="builder-ph-cells" viewBox="0 0 48 32"><rect x="3" y="4" width="12" height="10" rx="2"/><rect x="18" y="4" width="12" height="10" rx="2"/><rect x="33" y="4" width="12" height="10" rx="2"/><rect x="3" y="18" width="12" height="10" rx="2"/><rect x="18" y="18" width="12" height="10" rx="2"/><rect x="33" y="18" width="12" height="10" rx="2"/></symbol>
	</svg>

	<!-- A name in the list, with the mark of a file the Builder does not read completely -->
	<template id="builder-tpl-name">
		<span class="builder-name">
			<span class="builder-name-text"></span>
			<span class="builder-name-warning" role="img"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-warning"></use></svg></span>
		</span>
	</template>

	<!-- The tools of a frame: seen where the pointer is over it, and on the one that is selected -->
	<template id="builder-tpl-tools">
		<span class="builder-tools" role="toolbar" aria-label="[[/_admin/builder/menu/tools]]">
			<button type="button" class="builder-icon-btn" data-tool="settings" title="[[/_admin/builder/menu/settings]]" aria-label="[[/_admin/builder/menu/settings]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-gear"></use></svg></button>
			<button type="button" class="builder-icon-btn" data-tool="html" title="[[/_admin/builder/menu/edit-html]]" aria-label="[[/_admin/builder/menu/edit-html]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-code"></use></svg></button>
			<button type="button" class="builder-icon-btn" data-tool="up" title="[[/_admin/common/label/moveup]]" aria-label="[[/_admin/common/label/moveup]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-up"></use></svg></button>
			<button type="button" class="builder-icon-btn" data-tool="down" title="[[/_admin/common/label/movedown]]" aria-label="[[/_admin/common/label/movedown]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-down"></use></svg></button>
			<button type="button" class="builder-icon-btn" data-tool="duplicate" title="[[/_admin/builder/menu/duplicate]]" aria-label="[[/_admin/builder/menu/duplicate]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-copy"></use></svg></button>
			<button type="button" class="builder-icon-btn" data-tool="delete" title="[[/_admin/common/label/delete]]" aria-label="[[/_admin/common/label/delete]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-trash"></use></svg></button>
		</span>
	</template>

	<!-- A button that adds to the end of a level: its words are the script's -->
	<template id="builder-tpl-add">
		<button type="button" class="builder-add-button"></button>
	</template>

	<!-- The frame of a section, and the column in it: a head of three parts - the title, what is said of the frame, and the tools,
	     which the script puts in last -->
	<template id="builder-tpl-frame">
		<section class="builder-frame">
			<header class="builder-frame-head">
				<span class="builder-head-title"><span class="builder-frame-name"></span></span>
				<span class="builder-head-status"></span>
			</header>
			<div class="builder-frame-body"></div>
		</section>
	</template>

	<template id="builder-tpl-col">
		<div class="builder-pcol">
			<header class="builder-pcol-head">
				<span class="builder-head-title"><span class="builder-col-name"></span></span>
				<span class="builder-head-status"></span>
			</header>
			<div class="builder-col-body"></div>
		</div>
	</template>

	<template id="builder-tpl-ph">
		<div class="builder-ph">
			<svg class="builder-ph-icon" viewBox="0 0 48 32" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><use href="#builder-ph-block"></use></svg>
			<span class="builder-ph-label"></span>
		</div>
	</template>

	<!-- An icon of the sprite: the script says which -->
	<template id="builder-tpl-icon">
		<svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href=""></use></svg>
	</template>

	<!-- A group of a form under its heading, and one that stays folded away until it is opened -->
	<template id="builder-tpl-group">
		<section class="builder-group">
			<h3 class="builder-group-title"></h3>
			<div class="builder-group-body"></div>
		</section>
	</template>

	<template id="builder-tpl-fold">
		<details class="builder-group builder-fold">
			<summary class="builder-group-title"></summary>
			<div class="builder-group-body"></div>
		</details>
	</template>

	<!-- A table of a form: the script fills its heads and its rows -->
	<template id="builder-tpl-table">
		<table class="nino-admin-table builder-table">
			<thead><tr></tr></thead>
			<tbody></tbody>
		</table>
	</template>

	<!-- The source of a text or a picture in a form: what it is now, and beside it the button of the dialog where it is changed -->
	<template id="builder-tpl-source">
		<div class="builder-source">
			<span class="builder-label builder-source-name" hidden></span>
			<div class="builder-source-line">
				<span class="builder-source-caption"></span>
				<code class="builder-source-current"></code>
			</div>
			<p class="nino-admin-error builder-source-red" hidden></p>
		</div>
	</template>

	<template id="builder-tpl-newkey">
		<div class="builder-new">
			<span class="builder-label builder-new-title"></span>
			<label class="nino-admin-field"><span class="builder-new-name-label"></span><input type="text" class="nino-admin-input builder-new-name" autocomplete="off" spellcheck="false"></label>
			<p class="builder-new-line"><code class="builder-new-uri"></code></p>
			<p class="nino-admin-error builder-new-message"></p>
			<div class="builder-new-size">
				<label class="nino-admin-field"><span class="builder-new-width-label"></span><input type="number" min="1" class="nino-admin-input builder-new-width"></label>
				<label class="nino-admin-field"><span class="builder-new-height-label"></span><input type="number" min="1" class="nino-admin-input builder-new-height"></label>
			</div>
			<button type="button" class="nino-admin-btn-secondary builder-new-use"></button>
		</div>
	</template>

	<template id="builder-tpl-html">
		<div class="builder-html">
			<p class="nino-admin-hint builder-html-note"></p>
			<p class="nino-admin-error builder-html-reason" hidden></p>
			<textarea class="nino-admin-input builder-html-source" rows="18" spellcheck="false" autocomplete="off" wrap="off" aria-label="[[/_admin/builder/html/source]]"></textarea>
		</div>
	</template>

	<!-- What a new block of HTML+ starts as: markup the builder does not read as a
	     section, so it stays a block of its own as it is applied -->
	<template id="builder-tpl-skeleton"><div>
</div></template>

	<template id="builder-tpl-view">
		<div class="builder-view">
			<p class="nino-admin-hint builder-view-note"></p>
			<pre class="builder-view-code" tabindex="0"></pre>
		</div>
	</template>

</div>
