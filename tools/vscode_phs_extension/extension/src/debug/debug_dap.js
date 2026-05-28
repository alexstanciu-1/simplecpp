"use strict";

const path = require("path");
const fs = require("fs");
const { spawn } = require("child_process");
const vscode = require("vscode");
const { collectVisibleVariablesBeforeLineText } = require("../static_completion");
const debugStore = require("./project_debug_store");

const THREAD_ID = 1;

function registerDebugAdapter(context, options) {
	const debugType = "simplecpp-debug";
	const provider = new SimpleCppDebugConfigurationProvider(options);
	const factory = new SimpleCppDebugAdapterDescriptorFactory(options);

	context.subscriptions.push(
		vscode.debug.registerDebugConfigurationProvider(debugType, provider),
		vscode.debug.registerDebugAdapterDescriptorFactory(debugType, factory)
	);
}

class SimpleCppDebugConfigurationProvider {
	constructor(options) {
		this.options = options;
	}

	resolveDebugConfiguration(folder, config) {
		if (!config.type) {
			config.type = "simplecpp-debug";
		}
		if (!config.name) {
			config.name = "Simple C++ Debug";
		}
		if (!config.request) {
			config.request = "launch";
		}
		if (!Array.isArray(config.args)) {
			config.args = [];
		}
		if (!config.env || typeof config.env !== "object") {
			config.env = {};
		}
		if (!Array.isArray(config.breakpoints)) {
			config.breakpoints = [];
		}
		return config;
	}
}

class SimpleCppDebugAdapterDescriptorFactory {
	constructor(options) {
		this.options = options;
	}

	createDebugAdapterDescriptor(session) {
		return new vscode.DebugAdapterInlineImplementation(new SimpleCppInlineDebugAdapter(session, this.options));
	}
}

class SimpleCppInlineDebugAdapter {
	constructor(session, options) {
		this.session = session;
		this.options = options;
		this._emitter = new vscode.EventEmitter();
		this.onDidSendMessage = this._emitter.event;
		this.sequence = 1;
		this.breakpointsByFile = new Map();
		this.variablesHandles = new Map();
		this.nextVariablesReference = 1;
		this.lastLaunchConfig = null;
		this.pendingLaunchRequest = null;
		this.configurationDone = false;
		this.currentStop = null;
		this.latestSourceLineMaps = new Map();
		this.terminated = false;
	}

	handleMessage(message) {
		const command = message && typeof message.command === "string" ? message.command : "";
		switch (command) {
			case "initialize":
				this.respond(message, {
					supportsTerminateRequest: true,
					supportsConfigurationDoneRequest: true,
					supportsRestartRequest: true,
					supportsBreakpointLocationsRequest: true,
					supportsConditionalBreakpoints: false,
					supportsHitConditionalBreakpoints: false,
					supportsLogPoints: false,
					supportsFunctionBreakpoints: false,
					supportsInstructionBreakpoints: false,
					supportsStepBack: false,
					supportsStepInTargetsRequest: false,
					supportsCompletionsRequest: false,
					supportsLoadedSourcesRequest: false,
					supportsReadMemoryRequest: false,
					supportsWriteMemoryRequest: false,
					supportsDisassembleRequest: false,
					exceptionBreakpointFilters: []
				});
				this.sendEvent("initialized", {});
				return;
			case "configurationDone":
				this.configurationDone = true;
				this.respond(message, {});
				if (this.pendingLaunchRequest) {
					const pending = this.pendingLaunchRequest;
					this.pendingLaunchRequest = null;
					void this.handleLaunch(pending);
				}
				return;
			case "setBreakpoints":
				this.handleSetBreakpoints(message);
				return;
			case "breakpointLocations":
				this.handleBreakpointLocations(message);
				return;
			case "threads":
				this.respond(message, {
					threads: [{ id: THREAD_ID, name: "Main" }]
				});
				return;
			case "launch":
				this.handleLaunchRequest(message);
				return;
			case "stackTrace":
				this.handleStackTrace(message);
				return;
			case "scopes":
				this.handleScopes(message);
				return;
			case "variables":
				this.handleVariables(message);
				return;
			case "continue":
				this.respond(message, { allThreadsContinued: true });
				this.finishSession();
				return;
			case "restart":
				void this.handleRestart(message);
				return;
			case "disconnect":
			case "terminate":
				this.respond(message, {});
				this.finishSession();
				return;
			default:
				this.respond(message, {});
				return;
		}
	}

	dispose() {
		this._emitter.dispose();
	}

	handleSetBreakpoints(message) {
		const args = message.arguments || {};
		const source = args.source || {};
		const sourcePath = typeof source.path === "string" ? source.path : "";
		const requested = Array.isArray(args.breakpoints) ? args.breakpoints : [];
		const breakpoints = requested.map((breakpoint, index) => ({
			id: index + 1,
			verified: Boolean(sourcePath),
			line: typeof breakpoint.line === "number" ? breakpoint.line : 0
		}));
		if (sourcePath) {
			this.breakpointsByFile.set(sourcePath, breakpoints.filter((bp) => bp.line > 0).map((bp) => bp.line));
		}
		this.respond(message, { breakpoints });
	}

	handleBreakpointLocations(message) {
		const args = message.arguments || {};
		const source = args.source || {};
		const sourcePath = typeof source.path === "string" ? source.path : "";
		if (!sourcePath) {
			this.respond(message, { breakpoints: [] });
			return;
		}

		const startLine = typeof args.line === "number" ? args.line : 1;
		const endLine = typeof args.endLine === "number" ? args.endLine : startLine;
		const breakpoints = [];
		for (let line = startLine; line <= endLine; line += 1) {
			breakpoints.push({ line });
		}
		this.respond(message, { breakpoints });
	}

	handleLaunchRequest(message) {
		this.lastLaunchConfig = message.arguments || {};
		if (!this.configurationDone) {
			this.pendingLaunchRequest = message;
			return;
		}
		void this.handleLaunch(message);
	}

	async handleLaunch(message) {
		try {
			const config = message.arguments || {};
			this.lastLaunchConfig = config;
			this.currentStop = null;
			this.terminated = false;
			this.variablesHandles.clear();
			this.latestSourceLineMaps.clear();
			this.nextVariablesReference = 1;

			const projectRoot = await this.resolveProjectRoot(config);
			if (!projectRoot) {
				throw new Error("No Simple C++ project root was found for this debug launch.");
			}

			const actions = buildBreakpointActions(this.breakpointsByFile, projectRoot);
			const debugOptions = {
				format: "json",
				buildRuntime: Boolean(config.buildRuntime),
				buildDependencies: Boolean(config.buildDependencies),
				argsJson: Array.isArray(config.args) ? JSON.stringify(config.args.map((value) => String(value))) : null,
				callable: typeof config.call === "string" ? config.call : null,
				callArgsJson: typeof config.callArgsJson === "string" ? config.callArgsJson : null,
				callThisJson: typeof config.callThisJson === "string" ? config.callThisJson : null,
				execExpression: typeof config.exec === "string" ? config.exec : null,
				actions
			};
			const argv = this.options.debugRunner.buildScppDebugArgv(debugOptions);
			this.sendEvent("output", {
				category: "console",
				output: renderBreakpointLaunchLog(projectRoot, this.breakpointsByFile, actions, argv)
			});
			const result = await runDebugCommand(projectRoot, argv, config.env || {});
			this.latestSourceLineMaps = loadLatestSourceLineMaps(projectRoot);

			this.respond(message, {});

			const aggregate = tryParseJson(result.stdout);
			const events = Array.isArray(aggregate && aggregate.events) ? aggregate.events : [];
			const stop = findPrimaryStopEvent(events);
			if (stop) {
				const remappedStop = remapDebugEventSource(stop, this.latestSourceLineMaps);
				this.currentStop = {
					event: remappedStop,
					events
				};
				this.sendEvent("stopped", {
					reason: remappedStop.event === "break" ? "breakpoint" : "pause",
					threadId: THREAD_ID,
					allThreadsStopped: true
				});
				return;
			}

			if (result.stdout.trim() !== "") {
				this.sendEvent("output", {
					category: "stdout",
					output: result.stdout
				});
			}
			if (result.stderr.trim() !== "") {
				this.sendEvent("output", {
					category: "stderr",
					output: result.stderr
				});
			}
			this.finishSession();
		} catch (error) {
			this.sendEvent("output", {
				category: "stderr",
				output: String(error && error.message ? error.message : error) + "\n"
			});
			this.respond(message, {}, true, String(error && error.message ? error.message : error));
			this.finishSession();
		}
	}

	async handleRestart(message) {
		if (!this.lastLaunchConfig) {
			this.respond(message, {});
			return;
		}
		this.respond(message, {});
		this.pendingLaunchRequest = null;
		await this.handleLaunch({
			seq: message.seq,
			type: "request",
			command: "launch",
			arguments: this.lastLaunchConfig
		});
	}

	handleStackTrace(message) {
		const frame = buildStackFrame(this.currentStop ? this.currentStop.event : null);
		this.respond(message, {
			stackFrames: frame ? [frame] : [],
			totalFrames: frame ? 1 : 0
		});
	}

	handleScopes(message) {
		const frameId = message.arguments ? message.arguments.frameId : 0;
		if (frameId !== 1 || !this.currentStop) {
			this.respond(message, { scopes: [] });
			return;
		}

		const eventVariables = buildEventVariables(this.currentStop.event, this.currentStop.events);
		const reference = this.allocateVariablesReference(eventVariables);
		this.respond(message, {
			scopes: [
				{
					name: "Event",
					variablesReference: reference,
					expensive: false
				}
			]
		});
	}

	handleVariables(message) {
		const reference = message.arguments ? message.arguments.variablesReference : 0;
		const values = this.variablesHandles.get(reference) || [];
		this.respond(message, { variables: values });
	}

	allocateVariablesReference(values) {
		const reference = this.nextVariablesReference++;
		this.variablesHandles.set(reference, values);
		return reference;
	}

	async resolveProjectRoot(config) {
		if (typeof config.projectRoot === "string" && config.projectRoot.trim() !== "") {
			return config.projectRoot;
		}
		return this.options.resolveProjectRoot();
	}

	sendEvent(event, body) {
		this._emitter.fire({
			seq: this.sequence++,
			type: "event",
			event,
			body
		});
	}

	respond(request, body, success = true, message = undefined) {
		this._emitter.fire({
			seq: this.sequence++,
			type: "response",
			request_seq: request.seq,
			command: request.command,
			success,
			message,
			body
		});
	}

	finishSession() {
		if (this.terminated) {
			return;
		}
		this.terminated = true;
		this.pendingLaunchRequest = null;
		this.sendEvent("terminated", {});
	}
}

function buildBreakpointActions(breakpointsByFile, projectRoot) {
	const actions = [];
	for (const [filePath, lines] of breakpointsByFile.entries()) {
		const normalizedFileSpec = toDebugPathSpec(filePath, projectRoot);
		const visibleVariablesByLine = collectBreakpointVisibleVariables(filePath, lines);
		for (const line of lines) {
			const visibleVariables = visibleVariablesByLine.get(line) || [];
			for (const variableName of visibleVariables) {
				actions.push({
					flag: "dump-before",
					spec: `${normalizedFileSpec}:${line}:${variableName}`
				});
			}
			actions.push({
				flag: "break",
				spec: `${normalizedFileSpec}:${line}`
			});
		}
	}
	return actions;
}

function collectBreakpointVisibleVariables(filePath, lines) {
	const byLine = new Map();
	if (typeof filePath !== "string" || filePath.trim() === "" || !Array.isArray(lines) || lines.length === 0) {
		return byLine;
	}

	let text = "";
	try {
		text = fs.readFileSync(filePath, "utf8");
	} catch {
		return byLine;
	}

	for (const line of lines) {
		if (typeof line !== "number" || line <= 0) {
			continue;
		}
		byLine.set(line, collectVisibleVariablesBeforeLineText(text, line));
	}
	return byLine;
}

function toDebugPathSpec(filePath, projectRoot) {
	if (typeof filePath !== "string" || filePath.trim() === "") {
		return filePath;
	}
	if (typeof projectRoot !== "string" || projectRoot.trim() === "") {
		return filePath;
	}

	const relative = path.relative(projectRoot, filePath);
	if (!relative || relative.startsWith("..") || path.isAbsolute(relative)) {
		return filePath;
	}
	return relative.split(path.sep).join("/");
}

function renderBreakpointLaunchLog(projectRoot, breakpointsByFile, actions, argv) {
	const lines = [
		"[simplecpp-debug] launch configuration",
		`projectRoot: ${projectRoot}`,
		`argv: ${JSON.stringify(argv)}`,
		"[simplecpp-debug] breakpoint path mapping"
	];

	if (breakpointsByFile.size === 0) {
		lines.push("(no breakpoints)");
	} else {
		for (const [filePath, sourceLines] of breakpointsByFile.entries()) {
			const normalizedFileSpec = toDebugPathSpec(filePath, projectRoot);
			lines.push(`raw: ${filePath}`);
			lines.push(`spec-file: ${normalizedFileSpec}`);
			lines.push(`lines: ${JSON.stringify(sourceLines)}`);
		}
	}

	lines.push("[simplecpp-debug] emitted actions");
	if (actions.length === 0) {
		lines.push("(no actions)");
	} else {
		for (const action of actions) {
			lines.push(`--${action.flag}=${action.spec}`);
		}
	}

	return lines.join("\n") + "\n";
}

function loadLatestSourceLineMaps(projectRoot) {
	const maps = new Map();
	const slot = debugStore.getLatestDebugSlot(projectRoot);
	if (!slot) {
		return maps;
	}
	const manifest = debugStore.readDebugSourceManifest(projectRoot, slot);
	if (!manifest || !Array.isArray(manifest.files)) {
		return maps;
	}
	for (const entry of manifest.files) {
		if (!entry || typeof entry !== "object" || !entry.logicalSource || !entry.lineMap) {
			continue;
		}
		const lineMap = readLineMapFile(entry.lineMap);
		if (lineMap.size > 0) {
			maps.set(entry.logicalSource, lineMap);
		}
	}
	return maps;
}

function readLineMapFile(lineMapPath) {
	const map = new Map();
	if (typeof lineMapPath !== "string" || lineMapPath.trim() === "" || !fs.existsSync(lineMapPath)) {
		return map;
	}
	let text = "";
	try {
		text = fs.readFileSync(lineMapPath, "utf8");
	} catch {
		return map;
	}
	const lines = text.replace(/\r\n/g, "\n").replace(/\r/g, "\n").split("\n");
	for (const line of lines.slice(1)) {
		if (!line.trim()) {
			continue;
		}
		const [debugLineText, originalLineText] = line.split("\t", 3);
		const debugLine = Number.parseInt(debugLineText, 10);
		const originalLine = Number.parseInt(originalLineText, 10);
		if (Number.isInteger(debugLine) && debugLine > 0 && Number.isInteger(originalLine) && originalLine > 0) {
			map.set(debugLine, originalLine);
		}
	}
	return map;
}

function remapDebugEventSource(event, sourceLineMaps) {
	if (!event || typeof event !== "object" || !(sourceLineMaps instanceof Map)) {
		return event;
	}
	const source = event.source && typeof event.source === "object" ? event.source : null;
	if (!source || typeof source.file !== "string" || typeof source.line !== "number") {
		return event;
	}
	const lineMap = sourceLineMaps.get(source.file);
	if (!(lineMap instanceof Map) || !lineMap.has(source.line)) {
		return event;
	}
	return {
		...event,
		source: {
			...source,
			line: lineMap.get(source.line)
		}
	};
}

function runDebugCommand(projectRoot, argv, extraEnv) {
	return new Promise((resolve, reject) => {
		const child = spawn(argv[0], argv.slice(1), {
			cwd: projectRoot,
			env: { ...process.env, ...extraEnv },
			shell: false
		});

		let stdout = "";
		let stderr = "";
		child.stdout.on("data", (chunk) => {
			stdout += String(chunk);
		});
		child.stderr.on("data", (chunk) => {
			stderr += String(chunk);
		});
		child.on("error", reject);
		child.on("close", (code) => {
			resolve({
				exitCode: typeof code === "number" ? code : 1,
				stdout,
				stderr
			});
		});
	});
}

function tryParseJson(text) {
	try {
		return JSON.parse(text);
	} catch {
		return null;
	}
}

function findPrimaryStopEvent(events) {
	for (const event of events) {
		if (!event || typeof event !== "object") {
			continue;
		}
		if (event.event === "break" || event.event === "hit") {
			return event;
		}
	}
	return null;
}

function buildStackFrame(event) {
	if (!event || typeof event !== "object") {
		return null;
	}
	const source = event.source && typeof event.source === "object" ? event.source : null;
	if (!source || typeof source.file !== "string") {
		return {
			id: 1,
			name: event.event || "Simple C++ Debug",
			line: 1,
			column: 1
		};
	}
	return {
		id: 1,
		name: event.event || "Simple C++ Debug",
		source: {
			name: path.basename(source.file),
			path: source.file
		},
		line: typeof source.line === "number" ? source.line : 1,
		column: 1
	};
}

function buildEventVariables(stopEvent, allEvents) {
	const variables = [];
	const body = stopEvent && stopEvent.body && typeof stopEvent.body === "object" ? stopEvent.body : {};
	for (const [name, value] of Object.entries(body)) {
		variables.push({
			name,
			value: renderVariableValue(value),
			variablesReference: 0
		});
	}

	const relatedDumps = findRelatedDumpEvents(stopEvent, allEvents);
	for (const dumpEvent of relatedDumps) {
		const subject = dumpEvent.body && typeof dumpEvent.body === "object" && dumpEvent.body.subject && typeof dumpEvent.body.subject === "object"
			? dumpEvent.body.subject
			: {};
		const dumpValue = dumpEvent.body && typeof dumpEvent.body === "object" && dumpEvent.body.value && typeof dumpEvent.body.value === "object"
			? dumpEvent.body.value
			: {};
		variables.push({
			name: typeof subject.text === "string" ? subject.text : "dump",
			value: renderDumpValue(dumpValue),
			variablesReference: 0
		});
	}
	return variables;
}

function findRelatedDumpEvents(stopEvent, allEvents) {
	if (!stopEvent || !Array.isArray(allEvents)) {
		return [];
	}

	const stopSeq = typeof stopEvent.seq === "number" ? stopEvent.seq : Number.MAX_SAFE_INTEGER;
	const priorEvents = allEvents
		.filter((event) => event && typeof event === "object" && typeof event.seq === "number" && event.seq < stopSeq)
		.sort((left, right) => left.seq - right.seq);
	const related = [];
	for (let index = priorEvents.length - 1; index >= 0; index -= 1) {
		const event = priorEvents[index];
		if (event.event === "dump" && event.body && typeof event.body === "object") {
			related.unshift(event);
			continue;
		}
		if (related.length > 0) {
			break;
		}
	}
	return related;
}

function renderDumpValue(dumpValue) {
	if (!dumpValue || typeof dumpValue !== "object") {
		return renderVariableValue(dumpValue);
	}
	const preview = typeof dumpValue.preview === "string" ? dumpValue.preview : "";
	if (preview !== "" && preview !== "<not inspectable>") {
		return preview;
	}
	const typeLabel = simplifyCppTypeName(typeof dumpValue.type === "string" ? dumpValue.type : "");
	if (preview === "<not inspectable>" && typeLabel !== "") {
		return `<${typeLabel}>`;
	}
	if (preview !== "") {
		return preview;
	}
	if (typeLabel !== "") {
		return `<${typeLabel}>`;
	}
	return renderVariableValue(dumpValue);
}

function simplifyCppTypeName(typeName) {
	if (typeof typeName !== "string" || typeName.trim() === "") {
		return "";
	}
	const directShared = typeName.match(/shared_p(?:<|INS_?)(?:[^A-Za-z0-9_]*)([A-Z][A-Za-z0-9_]*)/);
	if (directShared && directShared[1]) {
		return `shared<${directShared[1]}>`;
	}
	const scopedShared = typeName.match(/shared_p.*?([A-Z][A-Za-z0-9_]*)(?:EEE|EE|E|>|$)/);
	if (scopedShared && scopedShared[1]) {
		return `shared<${scopedShared[1]}>`;
	}
	const plainObject = typeName.match(/([A-Z][A-Za-z0-9_]*)(?:EEE|EE|E|>|$)/);
	if (plainObject && plainObject[1]) {
		return plainObject[1];
	}
	const readable = typeName.match(/[A-Za-z_][A-Za-z0-9_]*/g);
	if (!readable || readable.length === 0) {
		return typeName;
	}
	return readable[readable.length - 1];
}

function renderVariableValue(value) {
	if (value === null || value === undefined) {
		return "null";
	}
	if (typeof value === "string") {
		return value;
	}
	if (typeof value === "number" || typeof value === "boolean") {
		return String(value);
	}
	try {
		return JSON.stringify(value);
	} catch {
		return String(value);
	}
}

module.exports = {
	registerDebugAdapter
};
