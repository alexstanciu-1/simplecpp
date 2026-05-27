"use strict";

const path = require("path");
const { spawn } = require("child_process");
const vscode = require("vscode");

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
		this.currentStop = null;
		this.terminated = false;
	}

	handleMessage(message) {
		const command = message && typeof message.command === "string" ? message.command : "";
		switch (command) {
			case "initialize":
				this.respond(message, {
					supportsConfigurationDoneRequest: true,
					supportsRestartRequest: true
				});
				this.sendEvent("initialized", {});
				return;
			case "configurationDone":
				this.respond(message, {});
				return;
			case "setBreakpoints":
				this.handleSetBreakpoints(message);
				return;
			case "threads":
				this.respond(message, {
					threads: [{ id: THREAD_ID, name: "Main" }]
				});
				return;
			case "launch":
				void this.handleLaunch(message);
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

	async handleLaunch(message) {
		try {
			const config = message.arguments || {};
			this.lastLaunchConfig = config;
			this.currentStop = null;
			this.terminated = false;
			this.variablesHandles.clear();
			this.nextVariablesReference = 1;

			const projectRoot = await this.resolveProjectRoot(config);
			if (!projectRoot) {
				throw new Error("No Simple C++ project root was found for this debug launch.");
			}

			const actions = buildBreakpointActions(this.breakpointsByFile);
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
			const result = await runDebugCommand(projectRoot, argv, config.env || {});

			this.respond(message, {});

			const aggregate = tryParseJson(result.stdout);
			const events = Array.isArray(aggregate && aggregate.events) ? aggregate.events : [];
			const stop = findPrimaryStopEvent(events);
			if (stop) {
				this.currentStop = {
					event: stop,
					events
				};
				this.sendEvent("stopped", {
					reason: stop.event === "break" ? "breakpoint" : "pause",
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
		this.sendEvent("terminated", {});
	}
}

function buildBreakpointActions(breakpointsByFile) {
	const actions = [];
	for (const [filePath, lines] of breakpointsByFile.entries()) {
		for (const line of lines) {
			actions.push({
				flag: "break",
				spec: `${filePath}:${line}`
			});
		}
	}
	return actions;
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

	const lastDump = Array.isArray(allEvents)
		? [...allEvents].reverse().find((event) => event && event.event === "dump" && event.body && typeof event.body === "object")
		: null;
	if (lastDump && lastDump.body) {
		variables.push({
			name: "last_dump",
			value: renderVariableValue(lastDump.body),
			variablesReference: 0
		});
	}
	return variables;
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
