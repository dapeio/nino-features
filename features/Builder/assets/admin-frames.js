/**
 *	Nino features
 *	Builder						The tab "Header/footer" of the pane of the pages (#builder-frames, see Modules\Builder\FramesTab): the default choice of the site for the
 *										header and the footer, with the way to choose another block for each and to edit it in Themelab. The list is the panel's own - the tab
 *										draws it from the answer of the pages (assets/admin.js, drawFrames()) - and so a choice, an undo or a change in Themelab asks the panel again.
 *										The tab is shown: the panel is asked for its list, which draws it
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */

( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	// The tab, as the workbench asks for it: by its uri
	wn.Nino.admin['builder-frames'] = {
		showCurrent : function() {

			const panel = wn.Nino.admin.builder;

			if( panel.ready === false )
				panel.init( function() {} );
			else
				panel.reload();
		},
	};

} )(window);
