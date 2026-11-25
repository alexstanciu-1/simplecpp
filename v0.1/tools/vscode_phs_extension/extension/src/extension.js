"use strict";

const fs = require("fs");
const path = require("path");
const vscode = require("vscode");
const { LanguageClient, TransportKind } = require("vscode-languageclient/node");
const { registerStaticCompletion } = require("./static_completion");
const debugStore = require("./debug/project_debug_store");
const debugRunner = require("./debug/scpp_debug_runner");
const { registerDebugCommands } = require("./debug/debug_commands");
const { registerDebugAdapter } = require("./debug/debug_dap");

/** @type {Map<string, LanguageClient>} */
const clients = new Map();
let devSmokeOpened = false;

async function activate(context) {
	registerStaticCompletion(context);
	registerJssDocumentSymbols(context);

	context.subscriptions.push(
		vscode.commands.registerCommand("simpleCpp.createProject", async () => {
			await createSimpleCppProject(context);
		}),
		vscode.commands.registerCommand("simpleCpp.createJssProject", async () => {
			await createJssProject(context);
		}),
		vscode.commands.registerCommand("simpleCpp.buildProject", async () => {
			await runScppProjectCommand("build", "scpp build");
		}),
		vscode.commands.registerCommand("simpleCpp.runProject", async () => {
			await runScppProjectCommand("run", "scpp run");
		}),
		registerScppCommand("simpleCpp.doctor", "scpp --doctor"),
		registerScppCommand("simpleCpp.docsStrict", "scpp docs strict"),
		registerScppCommand("simpleCpp.docsJss", "scpp docs jss")
	);

	context.subscriptions.push(
		vscode.commands.registerCommand("simpleCpp.stan.restartServer", async () => {
			await restartAllClients(context);
			vscode.window.showInformationMessage("Simple C++ STAN server restarted.");
		})
	);

	context.subscriptions.push(
		vscode.commands.registerCommand("simpleCpp.stan.openSmokeFile", async () => {
			await openSmokeFileIfAvailable(context, true);
		})
	);

	context.subscriptions.push(
		vscode.workspace.onDidChangeWorkspaceFolders(async () => {
			await synchronizeWorkspaceClients(context);
		})
	);

	context.subscriptions.push(
		vscode.workspace.onDidChangeConfiguration(async (event) => {
			if (event.affectsConfiguration("simplecpp.stan")) {
				await restartAllClients(context);
			}
		})
	);

	context.subscriptions.push(
		...registerDebugCommands({
			resolveProjectRoot,
			createTerminal: createScppTerminal,
			runTerminalCommand,
			debugStore,
			debugRunner
		})
	);

	registerDebugAdapter(context, {
		resolveProjectRoot,
		debugRunner
	});

	await synchronizeWorkspaceClients(context);
	await openSmokeFileIfAvailable(context, false);
}

async function synchronizeWorkspaceClients(context) {
	const workspaceFolders = vscode.workspace.workspaceFolders || [];
	const projectFolders = workspaceFolders.filter((folder) => isSimpleCppProjectFolder(folder.uri.fsPath));
	const expectedKeys = new Set(projectFolders.map((folder) => folder.uri.fsPath));

	for (const [key, client] of clients.entries()) {
		if (expectedKeys.has(key)) {
			continue;
		}
		await client.stop();
		clients.delete(key);
	}

	for (const folder of projectFolders) {
		if (clients.has(folder.uri.fsPath)) {
			continue;
		}
		const client = createClient(context, folder);
		clients.set(folder.uri.fsPath, client);
		context.subscriptions.push(client.start());
	}
}

async function restartAllClients(context) {
	for (const client of clients.values()) {
		await client.stop();
	}
	clients.clear();
	await synchronizeWorkspaceClients(context);
}

async function openSmokeFileIfAvailable(context, force) {
	if (!force && devSmokeOpened) {
		return;
	}
	if (!isDevelopmentMode(context)) {
		return;
	}

	const workspaceFolders = vscode.workspace.workspaceFolders || [];
	const smokeFolder = workspaceFolders.find((folder) => path.basename(folder.uri.fsPath) === "stan_smoke_workspace");
	if (!smokeFolder) {
		return;
	}

	const configuredEntrypoint = readProjectEntrypoint(smokeFolder.uri.fsPath);
	const targetPath = configuredEntrypoint !== null
		? path.join(smokeFolder.uri.fsPath, configuredEntrypoint)
		: fs.existsSync(path.join(smokeFolder.uri.fsPath, "main.jss"))
			? path.join(smokeFolder.uri.fsPath, "main.jss")
			: path.join(smokeFolder.uri.fsPath, "main.phs");
	const target = vscode.Uri.file(targetPath);
	try {
		const document = await vscode.workspace.openTextDocument(target);
		await vscode.window.showTextDocument(document, { preview: false });
		devSmokeOpened = true;
	} catch (error) {
		vscode.window.showWarningMessage(`Simple C++ STAN could not open smoke file: ${String(error && error.message ? error.message : error)}`);
	}
}

function isDevelopmentMode(context) {
	const extensionMode = context.extensionMode;
	return extensionMode === vscode.ExtensionMode.Development || extensionMode === vscode.ExtensionMode.Test;
}

function readProjectEntrypoint(projectRoot) {
	const prismPath = path.join(projectRoot, "prism.json");
	try {
		const parsed = JSON.parse(fs.readFileSync(prismPath, "utf8"));
		if (typeof parsed.entrypoint === "string" && parsed.entrypoint.trim() !== "") {
			return parsed.entrypoint;
		}
	} catch (_error) {
		return null;
	}
	return null;
}

function registerJssDocumentSymbols(context) {
	context.subscriptions.push(
		vscode.languages.registerDocumentSymbolProvider(
			{ language: "jss" },
			{
				provideDocumentSymbols(document) {
					return collectJssDocumentSymbols(document);
				}
			}
		)
	);
}

function collectJssDocumentSymbols(document) {
	const symbols = [];
	for (let line = 0; line < document.lineCount; line += 1) {
		const text = document.lineAt(line).text;
		const trimmed = text.trim();
		let match = trimmed.match(/^namespace\s+([A-Za-z_][A-Za-z0-9_.\\]*)/);
		if (match) {
			symbols.push(createDocumentSymbol(document, line, match[1], "JSS namespace", vscode.SymbolKind.Namespace));
			continue;
		}
		match = trimmed.match(/^class\s+([A-Za-z_][A-Za-z0-9_]*)/);
		if (match) {
			symbols.push(createDocumentSymbol(document, line, match[1], "JSS class", vscode.SymbolKind.Class));
			continue;
		}
		match = trimmed.match(/^(?:async\s+)?function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/);
		if (match) {
			symbols.push(createDocumentSymbol(document, line, match[1], "JSS function", vscode.SymbolKind.Function));
			continue;
		}
		match = trimmed.match(/^let\s+([A-Za-z_][A-Za-z0-9_]*)/);
		if (match) {
			symbols.push(createDocumentSymbol(document, line, match[1], "JSS local", vscode.SymbolKind.Variable));
		}
	}
	return symbols;
}

function createDocumentSymbol(document, line, name, detail, kind) {
	const range = document.lineAt(line).range;
	return new vscode.DocumentSymbol(name, detail, kind, range, range);
}

function createClient(context, workspaceFolder) {
	const configuration = vscode.workspace.getConfiguration("simplecpp.stan", workspaceFolder.uri);
	const phpBinary = configuration.get("phpBinary", "php");
	const configuredScript = configuration.get("serverScript", "");
	const serverScript = configuredScript && configuredScript.trim() !== ""
		? configuredScript
		: context.asAbsolutePath(path.join("..", "..", "..", "bin", "stan_lsp_server.php"));
	const serverOptions = createServerOptions(workspaceFolder, phpBinary, serverScript);

	const clientOptions = {
		workspaceFolder,
		documentSelector: [
			{ scheme: "file", language: "phs", pattern: `${workspaceFolder.uri.fsPath.replace(/\\/g, "/")}/**/*` },
			{ scheme: "file", language: "jss", pattern: `${workspaceFolder.uri.fsPath.replace(/\\/g, "/")}/**/*` },
			{ scheme: "file", pattern: `${workspaceFolder.uri.fsPath.replace(/\\/g, "/")}/**/*.phs` },
			{ scheme: "file", pattern: `${workspaceFolder.uri.fsPath.replace(/\\/g, "/")}/**/*.jss` }
		],
		synchronize: {
			fileEvents: vscode.workspace.createFileSystemWatcher(new vscode.RelativePattern(workspaceFolder, "**/*.{phs,php,jss,json}"))
		},
		outputChannel: vscode.window.createOutputChannel(`Simple C++ STAN (${workspaceFolder.name})`),
		traceOutputChannel: vscode.window.createOutputChannel(`Simple C++ STAN Trace (${workspaceFolder.name})`)
	};

	const client = new LanguageClient(
		`simpleCppStan:${workspaceFolder.uri.fsPath}`,
		`Simple C++ STAN (${workspaceFolder.name})`,
		serverOptions,
		clientOptions
	);

	client.setTrace(configuration.get("trace.server", "off"));
	return client;
}

function createServerOptions(workspaceFolder, phpBinary, serverScript) {
	const wslLaunch = resolveWslLaunch(workspaceFolder.uri.fsPath, serverScript, phpBinary);
	if (wslLaunch !== null) {
		return {
			command: "wsl.exe",
			args: wslLaunch,
			transport: TransportKind.stdio
		};
	}

	return {
		command: phpBinary,
		args: [serverScript],
		options: {
			cwd: workspaceFolder.uri.fsPath
		},
		transport: TransportKind.stdio
	};
}

function resolveWslLaunch(workspacePath, serverScript, phpBinary) {
	const workspaceMatch = workspacePath.match(/^\\\\wsl\$\\([^\\]+)\\(.*)$/i);
	const scriptMatch = serverScript.match(/^\\\\wsl\$\\([^\\]+)\\(.*)$/i);
	if (!workspaceMatch || !scriptMatch) {
		return null;
	}

	const workspaceDistro = workspaceMatch[1];
	const scriptDistro = scriptMatch[1];
	if (workspaceDistro.toLowerCase() !== scriptDistro.toLowerCase()) {
		return null;
	}

	const linuxWorkspacePath = `/${workspaceMatch[2].replace(/\\/g, "/")}`;
	const linuxScriptPath = `/${scriptMatch[2].replace(/\\/g, "/")}`;
	return ["-d", workspaceDistro, "--cd", linuxWorkspacePath, phpBinary, linuxScriptPath];
}

async function deactivate() {
	const stops = [];
	for (const client of clients.values()) {
		stops.push(client.stop());
	}
	clients.clear();
	await Promise.all(stops);
}

function registerScppCommand(commandId, shellCommand) {
	return vscode.commands.registerCommand(commandId, async () => {
		const terminal = createScppTerminal("Simple C++");
		runTerminalCommand(terminal, shellCommand);
	});
}

async function createSimpleCppProject(context) {
	const workspaceFolder = await pickTargetWorkspaceFolder();
	if (!workspaceFolder) {
		vscode.window.showWarningMessage("Open a folder in VS Code before creating a Simple C++ project.");
		return;
	}

	const projectRoot = workspaceFolder.uri.fsPath;
	const prismPath = path.join(projectRoot, "prism.json");
	if (fs.existsSync(prismPath)) {
		vscode.window.showInformationMessage(`A Simple C++ project already exists in ${workspaceFolder.name}.`);
		return;
	}

	const profilePick = await vscode.window.showQuickPick(
		[
			{ label: "strict", description: "Recommended strict PHP++ / PHS profile" },
			{ label: "legacy", description: "Compatibility-oriented legacy PHP profile" }
		],
		{
			title: "Select Simple C++ project profile",
			placeHolder: "Choose the profile for scpp init"
		}
	);
	if (!profilePick) {
		return;
	}

	const terminal = createScppTerminal(`Simple C++ (${workspaceFolder.name})`, projectRoot);
	runTerminalCommand(terminal, `scpp init --php-profile=${profilePick.label}`);
	if (!fs.existsSync(path.join(projectRoot, "main.phs"))) {
		runTerminalCommand(terminal, "printf 'echo \"hello\\\\n\";\\n' > main.phs");
	}
	vscode.window.showInformationMessage(`Creating Simple C++ project in ${workspaceFolder.name} with profile ${profilePick.label}.`);

	void synchronizeProjectClientWhenReady(context, projectRoot);
}

async function createJssProject(context) {
	const workspaceFolder = await pickTargetWorkspaceFolder();
	if (!workspaceFolder) {
		vscode.window.showWarningMessage("Open a folder in VS Code before creating a Simple C++ JSS project.");
		return;
	}

	const projectRoot = workspaceFolder.uri.fsPath;
	const prismPath = path.join(projectRoot, "prism.json");
	if (fs.existsSync(prismPath)) {
		vscode.window.showInformationMessage(`A Simple C++ project already exists in ${workspaceFolder.name}.`);
		return;
	}

	const prism = {
		runtime: {
			languages: {
				php: {
					profile: "strict"
				}
			}
		},
		entrypoint: "main.jss"
	};
	const main = [
		"class Box {",
		"\tname: string = \"ready\";",
		"}",
		"",
		"let box: Box = new Box();",
		"print(box.name, \"\\n\");",
		""
	].join("\n");

	try {
		fs.writeFileSync(prismPath, `${JSON.stringify(prism, null, 2)}\n`, "utf8");
		fs.writeFileSync(path.join(projectRoot, "main.jss"), main, "utf8");
		vscode.window.showInformationMessage(`Created Simple C++ JSS project in ${workspaceFolder.name}.`);
		await synchronizeWorkspaceClients(context);
		const document = await vscode.workspace.openTextDocument(vscode.Uri.file(path.join(projectRoot, "main.jss")));
		await vscode.window.showTextDocument(document, { preview: false });
	} catch (error) {
		vscode.window.showErrorMessage(`Could not create Simple C++ JSS project: ${String(error && error.message ? error.message : error)}`);
	}
}

async function runScppProjectCommand(mode, shellCommand) {
	const projectRoot = await resolveProjectRoot();
	if (!projectRoot || !fs.existsSync(path.join(projectRoot, "prism.json"))) {
		vscode.window.showWarningMessage(`No Simple C++ project was found. Run "Simple C++: Create Project" or open a folder containing prism.json before trying to ${mode}.`);
		return;
	}
	const terminal = createScppTerminal("Simple C++", projectRoot);
	runTerminalCommand(terminal, shellCommand);
}

async function resolveProjectRoot() {
	const activeDocument = vscode.window.activeTextEditor ? vscode.window.activeTextEditor.document : null;
	if (activeDocument && activeDocument.uri.scheme === "file") {
		const fromDocument = findProjectRoot(path.dirname(activeDocument.uri.fsPath));
		if (fromDocument) {
			return fromDocument;
		}
	}

	const workspaceFolder = await pickTargetWorkspaceFolder();
	if (!workspaceFolder) {
		return null;
	}
	return findProjectRoot(workspaceFolder.uri.fsPath) || workspaceFolder.uri.fsPath;
}

async function pickTargetWorkspaceFolder() {
	const workspaceFolders = vscode.workspace.workspaceFolders || [];
	if (workspaceFolders.length === 0) {
		return null;
	}
	if (workspaceFolders.length === 1) {
		return workspaceFolders[0];
	}

	const items = workspaceFolders.map((folder) => ({
		label: folder.name,
		description: folder.uri.fsPath,
		folder
	}));
	const picked = await vscode.window.showQuickPick(items, {
		title: "Select workspace folder",
		placeHolder: "Choose the folder to use for the Simple C++ command"
	});
	return picked ? picked.folder : null;
}

function findProjectRoot(startPath) {
	let current = startPath;
	while (true) {
		if (fs.existsSync(path.join(current, "prism.json"))) {
			return current;
		}
		const parent = path.dirname(current);
		if (parent === current) {
			return null;
		}
		current = parent;
	}
}

function isSimpleCppProjectFolder(folderPath) {
	return fs.existsSync(path.join(folderPath, "prism.json"));
}

async function synchronizeProjectClientWhenReady(context, projectRoot) {
	const prismPath = path.join(projectRoot, "prism.json");
	const timeoutAt = Date.now() + 15000;
	while (Date.now() < timeoutAt) {
		if (fs.existsSync(prismPath)) {
			await synchronizeWorkspaceClients(context);
			return;
		}
		await delay(250);
	}
}

function delay(ms) {
	return new Promise((resolve) => setTimeout(resolve, ms));
}

function createScppTerminal(name, cwd) {
	return vscode.window.createTerminal({
		name,
		...(cwd ? { cwd } : {})
	});
}

function runTerminalCommand(terminal, shellCommand) {
	terminal.show(true);
	terminal.sendText(shellCommand, true);
}

module.exports = {
	activate,
	deactivate,
	_debugSupport: {
		debugStore,
		debugRunner,
		registerDebugCommands,
		collectJssDocumentSymbols,
		readProjectEntrypoint
	}
};
