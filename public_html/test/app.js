const form = document.getElementById('runner-form');
const runButton = document.getElementById('run-button');
const compileRunButton = document.getElementById('compile-run-button');
const refreshTreeButton = document.getElementById('refresh-tree-button');
const newFileButton = document.getElementById('new-file-button');
const newDirButton = document.getElementById('new-dir-button');
const renameEntryButton = document.getElementById('rename-entry-button');
const deleteEntryButton = document.getElementById('delete-entry-button');
const phpCodeBox = document.getElementById('php-code');
const sandboxTreeBox = document.getElementById('sandbox-tree');
const treeRootPathBox = document.getElementById('tree-root-path');
const selectedFilePathBox = document.getElementById('selected-file-path');
const saveFileButton = document.getElementById('save-file-button');
const debugJsonBox = document.getElementById('debug-json');
const copyDebugButton = document.getElementById('copy-debug-button');
const warningsOutputBox = document.getElementById('warnings-output');
const warningsSection = document.getElementById('warnings-section');
const warningsStatus = document.getElementById('warnings-status');
const cppHeaderCodeBox = document.getElementById('cpp-header-code');
const cppCodeBox = document.getElementById('cpp-code');
const phpOutputBox = document.getElementById('php-output');
const cppOutputBox = document.getElementById('cpp-output');
const timingResourcesBox = document.getElementById('timing-resources');
const generatorStatus = document.getElementById('generator-status');
const phpStatus = document.getElementById('php-status');
const cppStatus = document.getElementById('cpp-status');
const phpPane = document.getElementById('php-pane');
const cppPane = document.getElementById('cpp-pane');
const memTestEnabledBox = document.getElementById('mem-test-enabled');
const cppTabButtons = Array.from(document.querySelectorAll('.cpp-tab'));
const cppTabPanels = Array.from(document.querySelectorAll('.cpp-tab-panel'));

let selectedSandboxPath = '';
let selectedSandboxPathDirty = false;
let selectedSandboxEntryType = '';
let sandboxTreeOpenPaths = new Set();

function buildNoCacheUrl(path, params = {}) {
	const url = new URL(path, window.location.href);
	for (const [key, value] of Object.entries(params)) {
		url.searchParams.set(key, String(value));
	}
	url.searchParams.set('ts', String(Date.now()) + '_' + Math.random().toString(16).slice(2));
	return url.toString();
}

function captureSandboxTreeOpenState() {
	const openPaths = new Set();
	for (const node of sandboxTreeBox.querySelectorAll('.tree-dir')) {
		if (node.open && node.dataset.path) {
			openPaths.add(node.dataset.path);
		}
	}
	sandboxTreeOpenPaths = openPaths;
}

function replaceOpenStatePathPrefix(oldPrefix, newPrefix) {
	if (!oldPrefix || !newPrefix || oldPrefix === newPrefix) {
		return;
	}

	const nextOpenPaths = new Set();
	for (const path of sandboxTreeOpenPaths) {
		if (path === oldPrefix || path.startsWith(`${oldPrefix}/`)) {
			nextOpenPaths.add(newPrefix + path.slice(oldPrefix.length));
			continue;
		}
		nextOpenPaths.add(path);
	}
	sandboxTreeOpenPaths = nextOpenPaths;
}

function removeOpenStatePathPrefix(prefix) {
	if (!prefix) {
		return;
	}

	const nextOpenPaths = new Set();
	for (const path of sandboxTreeOpenPaths) {
		if (path === prefix || path.startsWith(`${prefix}/`)) {
			continue;
		}
		nextOpenPaths.add(path);
	}
	sandboxTreeOpenPaths = nextOpenPaths;
}

function preserveOpenStateForSandboxMutation(action, data, payload) {
	captureSandboxTreeOpenState();

	if (action === 'sandbox_create_dir' && payload.path) {
		sandboxTreeOpenPaths.add(payload.path);
		return;
	}

	if (action === 'sandbox_create_file') {
		if (data.parent_path) {
			sandboxTreeOpenPaths.add(data.parent_path);
		}
		return;
	}

	if (action === 'sandbox_delete_file') {
		if (data.path) {
			const lastSlashIndex = data.path.lastIndexOf('/');
			if (lastSlashIndex > 0) {
				sandboxTreeOpenPaths.add(data.path.slice(0, lastSlashIndex));
			}
		}
		return;
	}

	if (action === 'sandbox_delete_dir' && data.path) {
		const lastSlashIndex = data.path.lastIndexOf('/');
		if (lastSlashIndex > 0) {
			sandboxTreeOpenPaths.add(data.path.slice(0, lastSlashIndex));
		}
		removeOpenStatePathPrefix(data.path);
		return;
	}

	if (action === 'sandbox_rename' && payload.old_path && payload.path) {
		replaceOpenStatePathPrefix(payload.old_path, payload.path);
		const lastSlashIndex = payload.path.lastIndexOf('/');
		if (lastSlashIndex > 0) {
			sandboxTreeOpenPaths.add(payload.path.slice(0, lastSlashIndex));
		}
		if (payload.type === 'dir') {
			sandboxTreeOpenPaths.add(payload.path);
		}
		return;
	}
}

function shouldDirectoryBeOpen(node) {
	if (!node.path) {
		return true;
	}
	if (sandboxTreeOpenPaths.has(node.path)) {
		return true;
	}
	return node.depth <= 1;
}


function setActiveCppTab(target) {
	for (const button of cppTabButtons) {
		const isActive = button.dataset.target === target;
		button.classList.toggle('is-active', isActive);
		button.setAttribute('aria-selected', isActive ? 'true' : 'false');
	}
	for (const panel of cppTabPanels) {
		panel.classList.toggle('is-active', panel.dataset.panel === target);
	}
}

function setStatus(node, state, text) {
	node.classList.remove('state-ok', 'state-error', 'state-busy', 'state-warning');
	if (state === 'ok') {
		node.classList.add('state-ok');
	}
	if (state === 'error') {
		node.classList.add('state-error');
	}
	if (state === 'busy') {
		node.classList.add('state-busy');
	}
	if (state === 'warning') {
		node.classList.add('state-warning');
	}
	node.textContent = text;
}

function normalizeOutput(value) {
	return String(value ?? '');
}

function refreshSelectedFileLabel() {
	if (selectedSandboxPath === '') {
		selectedFilePathBox.textContent = '(manual input)';
		return;
	}

	selectedFilePathBox.textContent = selectedSandboxPathDirty ? `${selectedSandboxPath} *` : selectedSandboxPath;
}

function updateSelectedFileLabel(path, entryType = 'file') {
	selectedSandboxPath = path || '';
	selectedSandboxEntryType = selectedSandboxPath === '' ? '' : entryType;
	selectedSandboxPathDirty = false;
	refreshSelectedFileLabel();
}

function updateMatchState(payload) {
	phpPane.classList.remove('match-ok', 'has-error');
	cppPane.classList.remove('match-ok', 'has-error');

	const phpHasError = normalizeOutput(payload.php_error) !== '';
	const cppHasError = normalizeOutput(payload.cpp_error) !== '';
	const phpOutput = normalizeOutput(payload.php_output);
	const cppOutput = normalizeOutput(payload.cpp_output);

	if (phpHasError) {
		phpPane.classList.add('has-error');
	}
	if (cppHasError) {
		cppPane.classList.add('has-error');
	}

	if (!phpHasError && !cppHasError && phpOutput === cppOutput) {
		phpPane.classList.add('match-ok');
		cppPane.classList.add('match-ok');
	}
}

function formatBytes(bytes) {
	const value = Number(bytes ?? 0);
	const negative = value < 0;
	let abs = Math.abs(value);
	const units = ['B', 'KB', 'MB', 'GB'];
	let index = 0;
	while (abs >= 1024 && index < units.length - 1) {
		abs /= 1024;
		index += 1;
	}
	const formatted = `${abs >= 100 || index === 0 ? abs.toFixed(0) : abs.toFixed(1)} ${units[index]}`;
	return negative ? `-${formatted}` : formatted;
}

function formatKb(kb) {
	return formatBytes(Number(kb ?? 0) * 1024);
}

function formatMs(ms) {
	const value = Number(ms ?? 0);
	if (!Number.isFinite(value)) {
		return 'n/a';
	}
	if (Math.abs(value) >= 1000) {
		return `${(value / 1000).toFixed(3)} s`;
	}
	return `${value.toFixed(3)} ms`;
}

function formatSigned(value, formatter) {
	if (value === null || value === undefined || !Number.isFinite(Number(value))) {
		return 'n/a';
	}
	const numeric = Number(value);
	const prefix = numeric > 0 ? '+' : '';
	return `${prefix}${formatter(numeric)}`;
}

function readStageMemory(stage, previousExternalRssKb) {
	if (stage.max_rss_kb !== undefined && stage.max_rss_kb !== null) {
		return {
			main: `max RSS ${formatKb(stage.max_rss_kb)}`,
			diff: previousExternalRssKb === null ? null : `Δ RSS vs prev ${formatSigned(stage.max_rss_kb - previousExternalRssKb, formatKb)}`,
			nextExternalRssKb: Number(stage.max_rss_kb),
		};
	}

	return {
		main: `mem ${formatSigned(stage.memory_delta_bytes ?? 0, formatBytes)}`,
		diff: `peak ${formatSigned(stage.peak_delta_bytes ?? 0, formatBytes)}`,
		nextExternalRssKb: previousExternalRssKb,
	};
}

function formatStageLine(label, stage, previousExternalRssKb) {
	if (!stage || stage.skipped === true) {
		return {
			text: `- ${label}: skipped${stage && stage.reason ? ` (${stage.reason})` : ''}`,
			nextExternalRssKb: previousExternalRssKb,
		};
	}

	const parts = [];
	parts.push(`wall ${formatMs(stage.wall_ms)}`);
	if (stage.user_ms !== undefined && stage.user_ms !== null) {
		parts.push(`cpu ${formatMs((stage.user_ms || 0) + (stage.sys_ms || 0))}`);
	}
	const memory = readStageMemory(stage, previousExternalRssKb);
	parts.push(memory.main);
	if (memory.diff) {
		parts.push(memory.diff);
	}
	if (stage.exit_code !== undefined && stage.exit_code !== null) {
		parts.push(`exit ${stage.exit_code}`);
	}
	if (stage.timed_out === true) {
		parts.push('timeout');
	}

	return {
		text: `- ${label}: ${parts.join(' | ')}`,
		nextExternalRssKb: memory.nextExternalRssKb,
	};
}

function formatTimingResources(metrics) {
	if (!metrics || typeof metrics !== 'object') {
		return '';
	}

	const groups = [
		{
			title: 'PHP pipeline',
			stages: [
				['Parse AST', metrics.parse_ast],
				['Create C++ code', metrics.create_cpp_code],
				['Execute PHP', metrics.execute_php],
			],
		},
		{
			title: 'C++ pipeline',
			stages: [
				['Compile C++', metrics.compile_cpp],
				['Execute C++', metrics.execute_cpp],
			],
		},
	];

	const lines = [];
	let previousExternalRssKb = null;
	for (const group of groups) {
		lines.push(group.title);
		for (const [label, stage] of group.stages) {
			const formatted = formatStageLine(label, stage, previousExternalRssKb);
			lines.push(formatted.text);
			previousExternalRssKb = formatted.nextExternalRssKb;
		}
		lines.push('');
	}

	const totals = [];
	const allStages = [metrics.parse_ast, metrics.create_cpp_code, metrics.execute_php, metrics.compile_cpp, metrics.execute_cpp].filter(Boolean);
	const totalWallMs = allStages.reduce((sum, stage) => sum + (Number(stage.wall_ms) || 0), 0);
	const maxObservedRssKb = Math.max(0, ...allStages.map((stage) => Number(stage.max_rss_kb) || 0));
	const totalInternalPeakDelta = (Number(metrics.parse_ast?.peak_delta_bytes) || 0) + (Number(metrics.create_cpp_code?.peak_delta_bytes) || 0);
	totals.push(`Total wall: ${formatMs(totalWallMs)}`);
	if (maxObservedRssKb > 0) {
		totals.push(`Max external RSS: ${formatKb(maxObservedRssKb)}`);
	}
	if (totalInternalPeakDelta > 0) {
		totals.push(`Internal peak growth: ${formatBytes(totalInternalPeakDelta)}`);
	}
	lines.push('Summary');
	lines.push(`- ${totals.join(' | ')}`);

	return lines.join('\n').trim();
}

function buildWarningsText(payload) {
	const parts = [];
	const generatorWarnings = normalizeOutput(payload.s2s_generator_output).trim();
	const generatorErrors = normalizeOutput(payload.generator_error).trim();

	if (generatorWarnings !== '') {
		parts.push(`Warnings\n${generatorWarnings}`);
	}
	if (generatorErrors !== '') {
		parts.push(`Generator errors\n${generatorErrors}`);
	}

	return parts.join('\n\n');
}

function renderWarnings(payload) {
	const warningsText = buildWarningsText(payload);
	warningsOutputBox.textContent = warningsText;
	warningsSection.classList.remove('has-warning', 'has-error');

	const hasWarnings = normalizeOutput(payload.s2s_generator_output).trim() !== '';
	const hasErrors = normalizeOutput(payload.generator_error).trim() !== '';

	if (hasWarnings) {
		warningsSection.classList.add('has-warning');
	}
	if (!hasWarnings && hasErrors) {
		warningsSection.classList.add('has-error');
	}

	if (hasWarnings) {
		setStatus(warningsStatus, 'warning', hasErrors ? 'warn+err' : 'warning');
		return;
	}
	if (hasErrors) {
		setStatus(warningsStatus, 'error', 'error');
		return;
	}
	setStatus(warningsStatus, 'ok', 'clear');
}

function renderDebugJson(payload) {
	debugJsonBox.textContent = payload.debug_json || '';
	timingResourcesBox.textContent = formatTimingResources(payload.timing_resources);
	renderWarnings(payload);
}


function escapeHtml(value) {
	return String(value)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');
}

function createTreeActionButton(label, title, className, onClick) {
	const button = document.createElement('button');
	button.type = 'button';
	button.className = `secondary-button tree-action-button ${className}`;
	button.textContent = label;
	button.title = title;
	button.addEventListener('click', (event) => {
		event.preventDefault();
		event.stopPropagation();
		onClick();
	});
	return button;
}

function selectSandboxEntry(path, entryType) {
	updateSelectedFileLabel(path, entryType);
	highlightSelectedFileButton(path);
}

async function promptAndCreateDirectory(parentPath) {
	const directoryName = window.prompt('New directory name:', 'new_dir');
	if (directoryName === null) {
		return;
	}

	await mutateSandbox('sandbox_create_dir', {
		parent_path: parentPath,
		name: directoryName,
	});
}

async function promptAndCreateFile(parentPath) {
	const fileName = window.prompt('New file name:', 'new_file.php');
	if (fileName === null) {
		return;
	}

	const payload = await mutateSandbox('sandbox_create_file', {
		parent_path: parentPath,
		name: fileName,
	});

	if (payload.path) {
		await loadSandboxFile(payload.path);
	}
}

async function promptAndRenameEntry(path, entryType) {
	const currentName = path.split('/').filter(Boolean).pop() || '';
	const nextName = window.prompt(`Rename ${entryType}:`, currentName);
	if (nextName === null) {
		return;
	}

	await mutateSandbox('sandbox_rename', {
		path,
		new_name: nextName,
	});
}

async function confirmAndDeleteEntry(path, entryType) {
	const confirmed = window.confirm(`Delete ${entryType} "${path}"?`);
	if (!confirmed) {
		return;
	}

	await mutateSandbox(entryType === 'dir' ? 'sandbox_delete_dir' : 'sandbox_delete_file', { path });
}

function buildTreeEntryLabel(name, actions) {
	const wrapper = document.createElement('span');
	wrapper.className = 'tree-entry';

	const nameBox = document.createElement('span');
	nameBox.className = 'tree-entry-name';
	nameBox.textContent = name;
	wrapper.appendChild(nameBox);

	const actionsBox = document.createElement('span');
	actionsBox.className = 'tree-entry-actions';
	for (const action of actions) {
		actionsBox.appendChild(action);
	}
	wrapper.appendChild(actionsBox);
	return wrapper;
}

function createTreeDirSummaryContent(name, actions) {
	const inner = document.createElement('span');
	inner.className = 'tree-dir-summary-inner';

	const caret = document.createElement('span');
	caret.className = 'tree-dir-caret';
	caret.setAttribute('aria-hidden', 'true');
	inner.appendChild(caret);

	inner.appendChild(buildTreeEntryLabel(name, actions));
	return inner;
}

function renderTreeNode(node) {
	if (node.type === 'dir') {
		const details = document.createElement('details');
		details.className = 'tree-dir';
		details.dataset.path = node.path || '';
		details.open = shouldDirectoryBeOpen(node);
		details.addEventListener('toggle', () => {
			if (!node.path) {
				return;
			}

			if (details.open) {
				sandboxTreeOpenPaths.add(node.path);
			} else {
				sandboxTreeOpenPaths.delete(node.path);
			}
		});

		const summary = document.createElement('summary');
		summary.appendChild(createTreeDirSummaryContent(node.name, [
			createTreeActionButton('+F', 'Create file', 'tree-action-create-file', () => {
				void promptAndCreateFile(node.path || '');
			}),
			createTreeActionButton('+D', 'Create directory', 'tree-action-create', () => {
				void promptAndCreateDirectory(node.path || '');
			}),
			...(node.path ? [createTreeActionButton('R', 'Rename directory', 'tree-action-rename', () => {
				void promptAndRenameEntry(node.path, 'dir');
			})] : []),
			...(node.path ? [createTreeActionButton('D', 'Delete directory', 'tree-action-delete', () => {
				void confirmAndDeleteEntry(node.path, 'dir');
			})] : []),
		]));
		summary.addEventListener('click', () => {
			selectSandboxEntry(node.path || '', 'dir');
		});
		details.appendChild(summary);

		const list = document.createElement('div');
		list.className = 'tree-children';
		for (const child of node.children || []) {
			list.appendChild(renderTreeNode(child));
		}
		details.appendChild(list);
		return details;
	}

	const row = document.createElement('div');
	row.className = 'tree-file-row';

	const button = document.createElement('button');
	button.type = 'button';
	button.className = 'tree-file';
	button.textContent = node.name;
	button.dataset.path = node.path;
	button.addEventListener('click', () => {
		void loadSandboxFile(node.path, button);
	});
	row.appendChild(button);

	const actions = document.createElement('span');
	actions.className = 'tree-entry-actions';
	actions.appendChild(createTreeActionButton('R', 'Rename file', 'tree-action-rename', () => {
		void promptAndRenameEntry(node.path, 'file');
	}));
	actions.appendChild(createTreeActionButton('D', 'Delete file', 'tree-action-delete', () => {
		void confirmAndDeleteEntry(node.path, 'file');
	}));
	row.appendChild(actions);
	return row;
}

function highlightSelectedFileButton(path) {
	for (const node of sandboxTreeBox.querySelectorAll('.tree-file')) {
		node.classList.toggle('is-selected', node.dataset.path === path);
	}
}

async function loadSandboxTree(options = {}) {
	const preserveCurrentDomState = options.preserveCurrentDomState !== false;
	sandboxTreeBox.innerHTML = '';
	treeRootPathBox.textContent = 'sandbox';

	try {
		if (preserveCurrentDomState) {
			captureSandboxTreeOpenState();
		}
		const response = await fetch(buildNoCacheUrl('run.php', { action: 'sandbox_tree' }));
		const payload = await parseJsonResponseSafe(response);
		if (!response.ok || !payload.ok) {
			throw new Error(payload.error || 'Failed to load sandbox tree.');
		}

		treeRootPathBox.textContent = 'sandbox';
		if (!payload.tree || !Array.isArray(payload.tree.children) || payload.tree.children.length === 0) {
			sandboxTreeBox.innerHTML = '<div class="tree-empty">No folders or files found in sandbox.</div>';
			return;
		}

		for (const child of payload.tree.children) {
			sandboxTreeBox.appendChild(renderTreeNode(child));
		}
		highlightSelectedFileButton(selectedSandboxPath);
	} catch (error) {
		treeRootPathBox.textContent = 'sandbox';
		sandboxTreeBox.innerHTML = `<div class="tree-empty">${error.message}</div>`;
	}
}

async function loadSandboxFile(path, clickedNode = null) {
	updateSelectedFileLabel(path, 'file');
	highlightSelectedFileButton(path);
	if (clickedNode) {
		clickedNode.blur();
	}

	try {
		const response = await fetch(buildNoCacheUrl('run.php', { action: 'sandbox_file', path }));
		const payload = await parseJsonResponseSafe(response);
		if (!response.ok || !payload.ok) {
			throw new Error(payload.error || 'Failed to read sandbox file.');
		}

		phpCodeBox.value = payload.content || '';
	} catch (error) {
		updateSelectedFileLabel('', '');
		highlightSelectedFileButton('');
		debugJsonBox.textContent = JSON.stringify({ sandbox_file_error: error.message, path }, null, '	');
	}
}


async function fetchJson(url, options = {}) {
	const response = await fetch(buildNoCacheUrl(url), options);
	const payload = await parseJsonResponseSafe(response);
	if (!response.ok || !payload.ok) {
		throw new Error(payload.error || 'Request failed.');
	}
	return payload;
}

async function mutateSandbox(action, data) {
	const payload = await fetchJson(`run.php?action=${encodeURIComponent(action)}`, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify(data),
	});

	preserveOpenStateForSandboxMutation(action, data, payload);

	if (action === 'sandbox_create_file' && payload.path) {
		updateSelectedFileLabel(payload.path, 'file');
	}
	if (action === 'sandbox_delete_file' && selectedSandboxPath === data.path) {
		updateSelectedFileLabel('', '');
		phpCodeBox.value = '';
	}
	if (action === 'sandbox_delete_dir' && selectedSandboxPath !== '' && (selectedSandboxPath === data.path || selectedSandboxPath.startsWith(`${data.path}/`))) {
		updateSelectedFileLabel('', '');
		phpCodeBox.value = '';
	}
	if (action === 'sandbox_rename' && selectedSandboxPath !== '' && payload.old_path) {
		if (selectedSandboxPath === payload.old_path || selectedSandboxPath.startsWith(`${payload.old_path}/`)) {
			const replacementPath = selectedSandboxPath === payload.old_path
				? payload.path
				: payload.path + selectedSandboxPath.slice(payload.old_path.length);
				updateSelectedFileLabel(replacementPath, payload.type || selectedSandboxEntryType || 'file');
		}
	}

	await loadSandboxTree({ preserveCurrentDomState: false });

	if (payload.path && payload.type === 'file' && (action === 'sandbox_rename' || action === 'sandbox_save_file')) {
		updateSelectedFileLabel(payload.path, 'file');
		highlightSelectedFileButton(payload.path);
	}
	return payload;
}

async function saveSelectedSandboxFile() {
	if (selectedSandboxPath === '') {
		window.alert('Select a sandbox file first.');
		return;
	}
	if (selectedSandboxEntryType !== 'file') {
		window.alert('Only files can be saved.');
		return;
	}

	saveFileButton.disabled = true;
	const previousText = saveFileButton.textContent;
	try {
		await mutateSandbox('sandbox_save_file', {
			path: selectedSandboxPath,
			content: phpCodeBox.value,
		});
		selectedSandboxPathDirty = false;
		refreshSelectedFileLabel();
		saveFileButton.textContent = 'Saved';
	} catch (error) {
		saveFileButton.textContent = 'Save failed';
		throw error;
	} finally {
		window.setTimeout(() => {
			saveFileButton.textContent = previousText;
			saveFileButton.disabled = false;
		}, 1200);
	}
}

async function runComparison() {
	runButton.disabled = true;
	setStatus(generatorStatus, 'busy', 'running');
	setStatus(phpStatus, 'busy', 'running');
	setStatus(cppStatus, 'busy', 'running');
	debugJsonBox.textContent = '';
	warningsOutputBox.textContent = '';
	warningsSection.classList.remove('has-warning', 'has-error');
	setStatus(warningsStatus, 'busy', 'running');
	cppHeaderCodeBox.value = '';
	cppCodeBox.value = '';
	phpOutputBox.textContent = '';
	cppOutputBox.textContent = '';
	timingResourcesBox.textContent = '';
	phpPane.classList.remove('match-ok', 'has-error');
	cppPane.classList.remove('match-ok', 'has-error');

	try {
		const payload = await fetchJson('run.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				php_code: phpCodeBox.value,
				selected_sandbox_path: selectedSandboxPath,
				mem_test_enabled: memTestEnabledBox.checked,
			}),
		});
		renderDebugJson(payload);

		cppHeaderCodeBox.value = payload.generator_header_display || '';
		cppCodeBox.value = payload.generator_source_display || '';
		phpOutputBox.textContent = payload.php_error || payload.php_output || '';
		cppOutputBox.textContent = payload.cpp_error || payload.cpp_output || '';

		const hasGeneratorWarnings = normalizeOutput(payload.s2s_generator_output).trim() !== '';
		const generatorState = payload.generator_error ? 'error' : (hasGeneratorWarnings ? 'warning' : 'ok');
		const generatorText = payload.generator_error ? 'error' : (hasGeneratorWarnings ? 'warning' : 'ok');
		setStatus(generatorStatus, generatorState, generatorText);
		setStatus(phpStatus, payload.php_error ? 'error' : 'ok', payload.php_error ? 'error' : 'ok');
		setStatus(cppStatus, payload.cpp_error ? 'error' : 'ok', payload.cpp_error ? 'error' : 'ok');
		updateMatchState(payload);
	} catch (error) {
		const message = error instanceof Error ? error.message : String(error);
		cppHeaderCodeBox.value = message;
		cppCodeBox.value = '';
		phpOutputBox.textContent = '';
		cppOutputBox.textContent = '';
		timingResourcesBox.textContent = '';
		if (debugJsonBox.textContent === '') {
			debugJsonBox.textContent = JSON.stringify({ request_error: message }, null, '	');
		}
		setStatus(generatorStatus, 'error', 'request error');
		setStatus(phpStatus, 'error', 'n/a');
		setStatus(cppStatus, 'error', 'n/a');
		setStatus(warningsStatus, 'error', 'request error');
	} finally {
		runButton.disabled = false;
	}
}

async function compileAndRunEditedCpp() {
	compileRunButton.disabled = true;
	setStatus(cppStatus, 'busy', 'compiling');
	setStatus(warningsStatus, 'busy', 'idle');
	warningsOutputBox.textContent = '';
	warningsSection.classList.remove('has-warning', 'has-error');
	cppOutputBox.textContent = '';
	timingResourcesBox.textContent = '';
	cppPane.classList.remove('match-ok', 'has-error');

	try {
		const payload = await fetchJson('run.php?action=compile_edited_cpp', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				cpp_header_code: cppHeaderCodeBox.value,
				cpp_source_code: cppCodeBox.value,
				mem_test_enabled: memTestEnabledBox.checked,
			}),
		});

		cppHeaderCodeBox.value = payload.generator_header_display || cppHeaderCodeBox.value;
		cppCodeBox.value = payload.generator_source_display || cppCodeBox.value;
		cppOutputBox.textContent = payload.cpp_error || payload.cpp_output || '';
		timingResourcesBox.textContent = formatTimingResources(payload.timing_resources);
		debugJsonBox.textContent = payload.debug_json || '';
		renderWarnings(payload);
		setStatus(cppStatus, payload.cpp_error ? 'error' : 'ok', payload.cpp_error ? 'error' : 'ok');
		cppPane.classList.toggle('has-error', normalizeOutput(payload.cpp_error) !== '');
	} catch (error) {
		const message = error instanceof Error ? error.message : String(error);
		cppOutputBox.textContent = message;
		timingResourcesBox.textContent = '';
		cppPane.classList.add('has-error');
		setStatus(cppStatus, 'error', 'request error');
		if (debugJsonBox.textContent === '') {
			debugJsonBox.textContent = JSON.stringify({ request_error: message, mode: 'compile_edited_cpp' }, null, '	');
		}
		warningsOutputBox.textContent = '';
		warningsSection.classList.remove('has-warning');
		warningsSection.classList.add('has-error');
		setStatus(warningsStatus, 'error', 'request error');
	} finally {
		compileRunButton.disabled = false;
	}
}

copyDebugButton.addEventListener('click', async () => {
	const text = debugJsonBox.textContent || '';
	if (text === '') {
		return;
	}

	const previousText = copyDebugButton.textContent;
	try {
		await navigator.clipboard.writeText(text);
		copyDebugButton.textContent = 'Copied';
	} catch (error) {
		copyDebugButton.textContent = 'Copy failed';
	} finally {
		window.setTimeout(() => {
			copyDebugButton.textContent = previousText;
		}, 1200);
	}
});

refreshTreeButton.addEventListener('click', () => {
	void loadSandboxTree();
});

newFileButton.addEventListener('click', () => {
	const parentPath = selectedSandboxEntryType === 'dir' ? selectedSandboxPath : '';
	void promptAndCreateFile(parentPath);
});

newDirButton.addEventListener('click', () => {
	const parentPath = selectedSandboxEntryType === 'dir' ? selectedSandboxPath : '';
	void promptAndCreateDirectory(parentPath);
});

renameEntryButton.addEventListener('click', () => {
	if (selectedSandboxPath === '') {
		window.alert('Select a sandbox file or directory first.');
		return;
	}
	void promptAndRenameEntry(selectedSandboxPath, selectedSandboxEntryType || 'file');
});

deleteEntryButton.addEventListener('click', () => {
	if (selectedSandboxPath === '') {
		window.alert('Select a sandbox file or directory first.');
		return;
	}
	void confirmAndDeleteEntry(selectedSandboxPath, selectedSandboxEntryType || 'file');
});

saveFileButton.addEventListener('click', () => {
	void saveSelectedSandboxFile().catch((error) => {
		window.alert(error instanceof Error ? error.message : String(error));
	});
});

phpCodeBox.addEventListener('input', () => {
	if (selectedSandboxPath === '') {
		return;
	}

	selectedSandboxPathDirty = true;
	refreshSelectedFileLabel();
});

form.addEventListener('submit', (event) => {
	event.preventDefault();
	void runComparison();
});

document.addEventListener('keydown', (event) => {
	if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
		event.preventDefault();
		void saveSelectedSandboxFile().catch((error) => {
			window.alert(error instanceof Error ? error.message : String(error));
		});
	}
});

for (const button of cppTabButtons) {
	button.addEventListener('click', () => {
		setActiveCppTab(button.dataset.target || 'header');
	});
}

compileRunButton.addEventListener('click', () => {
	void compileAndRunEditedCpp();
});

setActiveCppTab('header');

updateSelectedFileLabel('');
void loadSandboxTree();
async function parseJsonResponseSafe(response) {
	const rawText = await response.text();
	if (rawText.trim() === '') {
		return {
			ok: false,
			request_error: `Empty response body (HTTP ${response.status})`,
			raw_response_text: '',
		};
	}

	try {
		return JSON.parse(rawText);
	} catch (error) {
		return {
			ok: false,
			request_error: `Invalid JSON response (HTTP ${response.status}): ${error.message}`,
			raw_response_text: rawText,
		};
	}
}


