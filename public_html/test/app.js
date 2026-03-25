const form = document.getElementById('runner-form');
const runButton = document.getElementById('run-button');
const phpCodeBox = document.getElementById('php-code');
const debugJsonBox = document.getElementById('debug-json');
const copyDebugButton = document.getElementById('copy-debug-button');
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

function setStatus(node, state, text) {
	node.classList.remove('state-ok', 'state-error', 'state-busy');
	if (state === 'ok') {
		node.classList.add('state-ok');
	}
	if (state === 'error') {
		node.classList.add('state-error');
	}
	if (state === 'busy') {
		node.classList.add('state-busy');
	}
	node.textContent = text;
}

function normalizeOutput(value) {
	return String(value ?? '');
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
		text: `- ${label}: ${parts.join(' | ' )}`,
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
	lines.push(`- ${totals.join(' | ' )}`);

	return lines.join('\n').trim();
}

function renderDebugJson(payload) {
	debugJsonBox.textContent = payload.debug_json || '';
	timingResourcesBox.textContent = formatTimingResources(payload.timing_resources);
}

async function runComparison() {
	runButton.disabled = true;
	setStatus(generatorStatus, 'busy', 'running');
	setStatus(phpStatus, 'busy', 'running');
	setStatus(cppStatus, 'busy', 'running');
	debugJsonBox.textContent = '';
	cppHeaderCodeBox.textContent = '';
	cppCodeBox.textContent = '';
	phpOutputBox.textContent = '';
	cppOutputBox.textContent = '';
	timingResourcesBox.textContent = '';
	phpPane.classList.remove('match-ok', 'has-error');
	cppPane.classList.remove('match-ok', 'has-error');

	try {
		const response = await fetch('run.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({ php_code: phpCodeBox.value }),
		});

		const payload = await response.json();
		renderDebugJson(payload);

		if (!response.ok || !payload.ok) {
			throw new Error(payload.error || 'Request failed.');
		}

		cppHeaderCodeBox.textContent = payload.generator_header_display || '';
		cppCodeBox.textContent = payload.generator_source_display || '';
		phpOutputBox.textContent = payload.php_error || payload.php_output || '';
		cppOutputBox.textContent = payload.cpp_error || payload.cpp_output || '';

		setStatus(
			generatorStatus,
			payload.generator_error ? 'error' : 'ok',
			payload.generator_error ? 'generator error' : 'ok'
		);
		setStatus(
			phpStatus,
			payload.php_error ? 'error' : 'ok',
			payload.php_error ? 'php error' : 'ok'
		);
		setStatus(
			cppStatus,
			payload.cpp_error ? 'error' : 'ok',
			payload.cpp_error ? 'c++ error' : 'ok'
		);

		updateMatchState(payload);
	} catch (error) {
		const message = String(error.message || error);
		cppHeaderCodeBox.textContent = message;
		cppCodeBox.textContent = '';
		phpOutputBox.textContent = '';
		cppOutputBox.textContent = '';
		timingResourcesBox.textContent = '';
		if (debugJsonBox.textContent === '') {
			debugJsonBox.textContent = JSON.stringify({ request_error: message }, null, '\t');
		}
		setStatus(generatorStatus, 'error', 'request error');
		setStatus(phpStatus, 'error', 'n/a');
		setStatus(cppStatus, 'error', 'n/a');
	} finally {
		runButton.disabled = false;
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

form.addEventListener('submit', (event) => {
	event.preventDefault();
	void runComparison();
});
