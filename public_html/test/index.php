<?php

declare(strict_types=1);

// Simple browser UI for side-by-side PHP vs generated C++ testing.
//
// Purpose:
// - lets the user paste PHP code directly in the browser
// - runs the s2s generator on an on-the-fly AST fixture
// - executes the PHP source and the compiled generated C++ separately
// - displays generator / PHP / C++ errors in the requested quadrants

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
			<section class="pane">
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
?></textarea>
			</section>

			<section class="pane">
				<div class="pane-header">
					<h2>Generated C++ / generator error</h2>
					<span class="status-chip" id="generator-status">idle</span>
				</div>
				<pre id="cpp-code" class="code-box"></pre>
			</section>

			<section class="pane result-pane" id="php-pane">
				<div class="pane-header">
					<h2>PHP output / error</h2>
					<span class="status-chip" id="php-status">idle</span>
				</div>
				<pre id="php-output" class="code-box"></pre>
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
