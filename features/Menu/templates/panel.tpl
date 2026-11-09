<!-- The Menu's panel, rendered whole into the workbench's pane as a workspace (see Admin::template()
     and Admin::layout()): the shell, the rail, the account chrome and the head over the pane - the
     panel's name - are the workbench's, this file owns what is inside. #menu-root is what the script
     works in. Two screens, the list of the menus and the editor of one, which the script draws into
     the sections and shows one at a time. -->
<div id="menu-root" data-dir="[[/nino/dir]]">
	<section id="menu-list" class="menu-screen"></section>
	<section id="menu-editor" class="menu-screen admin-hidden" tabindex="-1"></section>
</div>
