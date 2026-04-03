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
				<div class="split-pane-section php-input-section">
					<div class="pane-header">
						<h2>PHP input</h2>
						<div class="pane-actions">
							<label class="toggle-option" for="mem-test-enabled">
								<input type="checkbox" id="mem-test-enabled">
								<span>Mem test (ASan)</span>
							</label>
							<button type="submit" id="run-button">Run</button>
						</div>
					</div>
					<div class="php-input-layout">
						<aside class="file-tree-panel">
							<div class="tree-root-path">
								<span id="tree-root-path" class="tree-root-label">sandbox</span>
								<div class="pane-actions tree-root-actions">
									<button type="button" id="new-file-button" class="secondary-button tree-toolbar-icon" title="New file" aria-label="New file">📄+</button>
									<button type="button" id="new-dir-button" class="secondary-button tree-toolbar-icon" title="New dir" aria-label="New dir">📁+</button>
									<button type="button" id="rename-entry-button" class="secondary-button tree-toolbar-icon" title="Rename selected entry" aria-label="Rename selected entry">✎</button>
									<button type="button" id="delete-entry-button" class="secondary-button tree-toolbar-icon" title="Delete selected entry" aria-label="Delete selected entry">🗑</button>
									<button type="button" id="refresh-tree-button" class="secondary-button tree-toolbar-icon" title="Refresh tree" aria-label="Refresh tree">↻</button>
								</div>
							</div>
							<div id="sandbox-tree" class="file-tree" aria-label="Sandbox files"></div>
						</aside>
						<div class="editor-panel">
							<div class="editor-toolbar">
								<div class="editor-toolbar-left">
									<span class="current-file-label">Selected file</span>
									<code id="selected-file-path">(manual input)</code>
								</div>
								<div class="editor-toolbar-right">
									<button type="button" id="save-file-button" class="secondary-button">Save</button>
								</div>
							</div>
							<textarea id="php-code" spellcheck="false"><?php
								echo htmlentities('<?php'."

".'function add(int $left, int $right): int {
	return $left + $right;
}

echo add(10, 20), "\n";');
?>
							</textarea>
						</div>
					</div>
				</div>
				<div class="split-pane-section debug-section">
					<div class="split-pane split-pane-debug">
						<div class="split-pane-section debug-json-section">
							<div class="pane-header">
								<h2>Debug JSON</h2>
								<div class="pane-actions">
									<button type="button" id="copy-debug-button" class="secondary-button">Copy</button>
								</div>
							</div>
							<pre id="debug-json" class="code-box"></pre>
						</div>
						<div class="split-pane-section warnings-section" id="warnings-section">
							<div class="pane-header">
								<h2>Warnings / s2s generator</h2>
								<span class="status-chip" id="warnings-status">clear</span>
							</div>
							<pre id="warnings-output" class="code-box"></pre>
						</div>
					</div>
				</div>
			</section>

			<section class="pane cpp-editor-pane">
				<div class="pane-header">
					<h2>Generated C++ / editable</h2>
					<div class="pane-actions">
						<button type="button" id="compile-run-button">Compile &amp; run edited C++</button>
						<span class="status-chip" id="generator-status">idle</span>
					</div>
				</div>
				<div class="cpp-tabs-toolbar">
					<div class="cpp-tab-list" role="tablist" aria-label="Generated C++ tabs">
						<button type="button" id="cpp-tab-header" class="cpp-tab is-active" data-target="header" role="tab" aria-selected="true">Generated C++ header</button>
						<button type="button" id="cpp-tab-source" class="cpp-tab" data-target="source" role="tab" aria-selected="false">Generated C++ source</button>
					</div>
				</div>
				<div class="cpp-tab-panels">
					<div class="cpp-tab-panel is-active" data-panel="header">
						<textarea id="cpp-header-code" class="code-editor" spellcheck="false"></textarea>
					</div>
					<div class="cpp-tab-panel" data-panel="source">
						<textarea id="cpp-code" class="code-editor" spellcheck="false"></textarea>
					</div>
				</div>
			</section>

			<section class="pane result-pane" id="php-pane">
				<div class="pane-header">
					<h2>PHP output / error</h2>
					<span class="status-chip" id="php-status">idle</span>
				</div>
				<pre id="php-output" class="code-box"></pre>
			</section>

			<section class="pane split-pane-metrics result-pane" id="cpp-pane">
				<div class="split-pane-section output-section">
					<div class="pane-header">
						<h2>C++ output / error</h2>
						<span class="status-chip" id="cpp-status">idle</span>
					</div>
					<pre id="cpp-output" class="code-box"></pre>
				</div>
				<div class="split-pane-section metrics-section">
					<div class="pane-header">
						<h2>Timing / resources</h2>
					</div>
					<pre id="timing-resources" class="code-box"></pre>
				</div>
			</section>
		</form>
	</div>

	<script src="app.js"></script>
</body>
</html>
