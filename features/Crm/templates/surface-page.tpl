<!doctype html>
<html lang="[[lang]]">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="robots" content="noindex, nofollow">
	<meta name="color-scheme" content="light dark">
	<link rel="manifest" href="[[manifest]]">
	<meta name="theme-color" content="[[bar]]" media="(prefers-color-scheme: light)">
	<meta name="theme-color" content="[[bar-dark]]" media="(prefers-color-scheme: dark)">
	<link rel="apple-touch-icon" href="[[touch]]">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="[[app-title]]">
	<title>[[t.page.title]]</title>
	<link rel="icon" href="data:,">
	<link rel="stylesheet" href="[[css]]">
	[[scripts]]
</head>
<body>
	<a class="skip" href="#main">[[t.page.skip]]</a>
	<div class="app">
		<aside class="rail">
			<div class="brand"><b>[[brand]]</b><span>[[tag]]</span></div>
			<nav class="nav" id="nav" aria-label="[[t.nav.label]]">[[entries]]</nav>
			<div class="foot">
				<span class="who">[[user]]</span>
				<button type="button" class="linkish" id="keys-open" aria-keyshortcuts="?">[[t.keys.open]]</button>
				<a href="[[site]]">[[t.rail.site]]</a>
				<a href="[[workbench]]">[[t.rail.workbench]]</a>
				<button type="button" class="linkish" id="signout">[[t.rail.signout]]</button>
			</div>
		</aside>
		<main id="main" tabindex="-1"><noscript><p class="state-note">[[t.page.noscript]]</p></noscript></main>
	</div>
	<div class="toast" id="toast" role="status" aria-live="polite"></div>
	<div id="dialogs"></div>
	<script type="application/json" id="crm-data">[[data]]</script>
</body>
</html>
