<!-- The Builder's panel, rendered whole into the workbench's pane as a
     workspace (see Admin::template() and Admin::layout()): the shell, the
     rail, the account chrome and the head over the pane - the panel's name -
     are the workbench's, this file owns what is inside. #builder-root is what
     the panel's script works in; its data-dir is the project's directory,
     which the link to the real page starts from.

     Two screens - the list of the page templates and the editor - and, below
     them, everything the script draws with: the one dialog, the one menu, the
     icons and placeholders of the preview as a sprite, and the fragments it
     clones (a <template> each, one element in it). The script fills them with
     text and never writes markup of its own. -->
<div id="builder-root" data-dir="[[/nino/dir]]">

	<section id="builder-list" class="builder-screen">
		<p class="nino-admin-hint">[[/_admin/builder/hint/list]]</p>
		<div id="builder-templates"></div>
	</section>

	<section id="builder-editor" class="builder-screen builder-editor admin-hidden">

		<div class="builder-editor-head">
			<a id="builder-back" class="nino-admin-back-link" href="#builder">[[/_admin/common/label/back]]</a>
			<h2 id="builder-editor-title" class="builder-editor-title"></h2>
			<a id="builder-open-page" class="builder-link" href="#" target="_blank" rel="noopener" hidden>[[/_admin/builder/label/open-page]]</a>
		</div>

		<p class="nino-admin-notice builder-narrow-note" role="note">[[/_admin/builder/state/narrow]]</p>
		<ul id="builder-problems" class="nino-admin-error builder-problems" hidden></ul>

		<div class="builder-workspace">

			<aside id="builder-tree-pane" class="builder-tree-pane" aria-label="[[/_admin/builder/label/tree]]">
				<div class="builder-pane-head">
					<h3 class="builder-pane-title">[[/_admin/builder/label/tree]]</h3>
					<button type="button" id="builder-template-settings" class="builder-icon-btn" title="[[/_admin/builder/menu/template]]" aria-label="[[/_admin/builder/menu/template]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-gear"></use></svg></button>
				</div>
				<div id="builder-tree" class="builder-tree" role="tree"></div>
				<div class="builder-tree-actions">
					<button type="button" id="builder-add-section" class="builder-add-button">+ [[/_admin/builder/tree/section]]</button>
					<button type="button" id="builder-add-html" class="builder-add-button">+ [[/_admin/builder/tree/html-block]]</button>
				</div>
				<div id="builder-menu" class="builder-menu" role="menu" hidden></div>
			</aside>

			<section id="builder-preview-pane" class="builder-preview-pane" aria-label="[[/_admin/builder/label/preview]]">
				<div class="builder-pane-head">
					<h3 class="builder-pane-title">[[/_admin/builder/label/preview]]</h3>
					<div class="builder-viewports" role="group" aria-label="[[/_admin/builder/label/viewports]]">
						<button type="button" id="builder-viewport-s" class="builder-viewport" title="[[/_admin/builder/viewport/s]]">[[/_admin/builder/viewport/s]]</button>
						<button type="button" id="builder-viewport-m" class="builder-viewport" title="[[/_admin/builder/viewport/m]]">[[/_admin/builder/viewport/m]]</button>
						<button type="button" id="builder-viewport-l" class="builder-viewport" title="[[/_admin/builder/viewport/l]]">[[/_admin/builder/viewport/l]]</button>
					</div>
				</div>
				<div id="builder-preview" class="builder-preview" data-viewport="l"></div>
			</section>

		</div>

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
				<h2 id="builder-dialog-title" class="nino-admin-dialog-title"></h2>
				<button type="button" id="builder-dialog-close" class="builder-icon-btn" title="[[/_admin/builder/label/close]]" aria-label="[[/_admin/builder/label/close]]">&times;</button>
			</div>
			<div id="builder-dialog-tabs" class="builder-tabs" role="tablist" hidden></div>
			<div id="builder-dialog-content" class="builder-dialog-content"></div>
			<p id="builder-dialog-problems" class="nino-admin-error" role="alert" hidden></p>
			<div id="builder-dialog-actions" class="nino-admin-dialog-actions"></div>
		</div>
	</dialog>

	<svg class="builder-sprite" aria-hidden="true" focusable="false">
		<symbol id="builder-icon-chevron" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
		<symbol id="builder-icon-grip" viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="9" cy="6" r="1.2"/><circle cx="15" cy="6" r="1.2"/><circle cx="9" cy="12" r="1.2"/><circle cx="15" cy="12" r="1.2"/><circle cx="9" cy="18" r="1.2"/><circle cx="15" cy="18" r="1.2"/></symbol>
		<symbol id="builder-icon-gear" viewBox="0 0 24 24"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></symbol>
		<symbol id="builder-icon-dots" viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/></symbol>
		<symbol id="builder-icon-image" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></symbol>
		<symbol id="builder-icon-hidden" viewBox="0 0 24 24"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></symbol>
		<symbol id="builder-ph-title" viewBox="0 0 48 32"><rect x="4" y="7" width="32" height="7" rx="2" fill="currentColor" stroke="none"/><rect x="4" y="20" width="20" height="3" rx="1.5" fill="currentColor" stroke="none" opacity=".5"/></symbol>
		<symbol id="builder-ph-text" viewBox="0 0 48 32"><rect x="4" y="6" width="40" height="3" rx="1.5" fill="currentColor" stroke="none"/><rect x="4" y="14" width="40" height="3" rx="1.5" fill="currentColor" stroke="none" opacity=".7"/><rect x="4" y="22" width="26" height="3" rx="1.5" fill="currentColor" stroke="none" opacity=".5"/></symbol>
		<symbol id="builder-ph-image" viewBox="0 0 48 32"><rect x="4" y="4" width="40" height="24" rx="3"/><circle cx="15" cy="12" r="3"/><path d="m4 24 11-8 8 6 8-7 13 9"/></symbol>
		<symbol id="builder-ph-button" viewBox="0 0 48 32"><rect x="8" y="9" width="32" height="14" rx="7"/><rect x="16" y="15" width="16" height="2" rx="1" fill="currentColor" stroke="none"/></symbol>
		<symbol id="builder-ph-block" viewBox="0 0 48 32"><rect x="4" y="4" width="40" height="24" rx="3" stroke-dasharray="4 3"/></symbol>
		<symbol id="builder-ph-cells" viewBox="0 0 48 32"><rect x="3" y="4" width="12" height="10" rx="2"/><rect x="18" y="4" width="12" height="10" rx="2"/><rect x="33" y="4" width="12" height="10" rx="2"/><rect x="3" y="18" width="12" height="10" rx="2"/><rect x="18" y="18" width="12" height="10" rx="2"/><rect x="33" y="18" width="12" height="10" rx="2"/></symbol>
	</svg>

	<template id="builder-tpl-row">
		<div class="builder-row" role="treeitem">
			<button type="button" class="builder-row-toggle" aria-label="[[/_admin/builder/menu/fold]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-chevron"></use></svg></button>
			<span class="builder-handle" title="[[/_admin/builder/menu/drag]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-grip"></use></svg></span>
			<button type="button" class="builder-row-main">
				<span class="builder-row-kind"></span>
				<span class="builder-row-label"></span>
				<span class="builder-row-sub"></span>
				<span class="builder-row-badge"></span>
				<span class="builder-row-warning">!</span>
			</button>
			<button type="button" class="builder-icon-btn builder-row-settings" title="[[/_admin/builder/menu/settings]]" aria-label="[[/_admin/builder/menu/settings]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-gear"></use></svg></button>
			<button type="button" class="builder-icon-btn builder-row-menu" title="[[/_admin/builder/menu/more]]" aria-label="[[/_admin/builder/menu/more]]" aria-haspopup="menu"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-dots"></use></svg></button>
		</div>
	</template>

	<template id="builder-tpl-frame">
		<section class="builder-frame">
			<header class="builder-frame-head">
				<span class="builder-frame-name"></span>
				<span class="builder-frame-bg" title="[[/_admin/builder/preview/background]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-image"></use></svg></span>
				<span class="builder-frame-warning">!</span>
				<button type="button" class="builder-icon-btn builder-frame-settings" title="[[/_admin/builder/menu/settings]]" aria-label="[[/_admin/builder/menu/settings]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-gear"></use></svg></button>
			</header>
			<div class="builder-frame-body"></div>
		</section>
	</template>

	<template id="builder-tpl-col">
		<div class="builder-pcol">
			<header class="builder-pcol-head">
				<span class="builder-col-name"></span>
				<span class="builder-col-hidden" title="[[/_admin/builder/preview/hidden]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-hidden"></use></svg></span>
				<button type="button" class="builder-icon-btn builder-col-settings" title="[[/_admin/builder/menu/settings]]" aria-label="[[/_admin/builder/menu/settings]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-gear"></use></svg></button>
			</header>
			<div class="builder-col-body"></div>
		</div>
	</template>

	<template id="builder-tpl-stack">
		<div class="builder-stack">
			<header class="builder-stack-head">
				<svg class="builder-ph-icon" viewBox="0 0 48 32" aria-hidden="true"><use href="#builder-ph-cells"></use></svg>
				<span class="builder-stack-name"></span>
				<code class="builder-stack-source"></code>
				<button type="button" class="builder-icon-btn builder-stack-settings" title="[[/_admin/builder/menu/settings]]" aria-label="[[/_admin/builder/menu/settings]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-gear"></use></svg></button>
			</header>
			<div class="builder-cells"></div>
		</div>
	</template>

	<template id="builder-tpl-ph">
		<div class="builder-ph">
			<svg class="builder-ph-icon" viewBox="0 0 48 32" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><use href="#builder-ph-block"></use></svg>
			<span class="builder-ph-label"></span>
			<button type="button" class="builder-icon-btn builder-ph-settings" title="[[/_admin/builder/menu/settings]]" aria-label="[[/_admin/builder/menu/settings]]"><svg class="builder-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#builder-icon-gear"></use></svg></button>
		</div>
	</template>

	<template id="builder-tpl-source">
		<fieldset class="builder-source">
			<legend class="builder-source-label"></legend>
			<div class="builder-source-body">
				<p class="builder-source-line">[[/_admin/builder/source/current]] <code class="builder-source-current"></code></p>
				<p class="nino-admin-error builder-source-red" hidden></p>
				<div class="builder-source-tabs" role="tablist">
					<button type="button" role="tab" class="builder-tab builder-source-tab" data-tab="text">[[/_admin/builder/source/tab-text]]</button>
					<button type="button" role="tab" class="builder-tab builder-source-tab" data-tab="image">[[/_admin/builder/source/tab-image]]</button>
					<button type="button" role="tab" class="builder-tab builder-source-tab" data-tab="fixed">[[/_admin/builder/source/tab-fixed]]</button>
				</div>
				<div class="builder-source-pane" data-tab="text"></div>
				<div class="builder-source-pane" data-tab="image"></div>
				<div class="builder-source-pane" data-tab="fixed"></div>
			</div>
		</fieldset>
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
