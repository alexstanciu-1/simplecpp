const form = document.getElementById('runner-form');
const runButton = document.getElementById('run-button');
const phpCodeBox = document.getElementById('php-code');
const cppCodeBox = document.getElementById('cpp-code');
const phpOutputBox = document.getElementById('php-output');
const cppOutputBox = document.getElementById('cpp-output');
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

async function runComparison() {
	runButton.disabled = true;
	setStatus(generatorStatus, 'busy', 'running');
	setStatus(phpStatus, 'busy', 'running');
	setStatus(cppStatus, 'busy', 'running');
	cppCodeBox.textContent = '';
	phpOutputBox.textContent = '';
	cppOutputBox.textContent = '';
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

		if (!response.ok || !payload.ok) {
			throw new Error(payload.error || 'Request failed.');
		}

		cppCodeBox.textContent = payload.generator_display || '';
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
		cppCodeBox.textContent = String(error.message || error);
		phpOutputBox.textContent = '';
		cppOutputBox.textContent = '';
		setStatus(generatorStatus, 'error', 'request error');
		setStatus(phpStatus, 'error', 'n/a');
		setStatus(cppStatus, 'error', 'n/a');
	} finally {
		runButton.disabled = false;
	}
}

form.addEventListener('submit', (event) => {
	event.preventDefault();
	void runComparison();
});
