<?php

declare(strict_types=1);

?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Simple C++ Test UI</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
	<div class="app-shell">
		<form id="runner-form" class="grid-shell" autocomplete="off">
			<section class="pane split-pane split-pane-left">
				<div class="split-pane-section">
					<div class="pane-header">
						<h2>PHP input</h2>
						<div class="pane-actions">
							<button type="submit" id="run-button">Run</button>
						</div>
					</div>
					<textarea id="php-code" spellcheck="false"><?php
						echo htmlentities('<?php'."\n\n".'function add(int $left, int $right): int {
	return $left + $right;
}

echo add(10, 20), "\n";');
?>
					</textarea>
				</div>
				<div class="split-pane-section debug-section">
					<div class="pane-header">
						<h2>Debug JSON</h2>
						<div class="pane-actions">
							<button type="button" id="copy-debug-button" class="secondary-button">Copy</button>
						</div>
					</div>
					<pre id="debug-json" class="code-box"></pre>
				</div>
			</section>

			<section class="pane split-pane">
				<div class="split-pane-section">
					<div class="pane-header">
						<h2>Generated C++ header / generator error</h2>
						<span class="status-chip" id="generator-status">idle</span>
					</div>
					<pre id="cpp-header-code" class="code-box"></pre>
				</div>
				<div class="split-pane-section">
					<div class="pane-header">
						<h2>Generated C++ source</h2>
					</div>
					<pre id="cpp-code" class="code-box"></pre>
				</div>
			</section>

			<section class="pane split-pane-metrics result-pane" id="php-pane">
				<div class="split-pane-section output-section">
					<div class="pane-header">
						<h2>PHP output / error</h2>
						<span class="status-chip" id="php-status">idle</span>
					</div>
					<pre id="php-output" class="code-box"></pre>
				</div>
				<div class="split-pane-section metrics-section">
					<div class="pane-header">
						<h2>Timing / resources</h2>
					</div>
					<pre id="timing-resources" class="code-box"></pre>
				</div>
			</section>

			<section class="pane result-pane" id="cpp-pane">
				<div class="pane-header">
					<h2>C++ output / error</h2>
					<span class="status-chip" id="cpp-status">idle</span>
				</div>
				<pre id="cpp-output" class="code-box"></pre>
			</section>
		</form>
	</div>

	<script src="app.js"></script>
</body>
</html>
