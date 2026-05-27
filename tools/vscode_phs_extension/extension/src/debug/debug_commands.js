"use strict";

const fs = require("fs");
const vscode = require("vscode");

function registerDebugCommands(options) {
	const {
		resolveProjectRoot,
		createTerminal,
		runTerminalCommand,
		debugStore,
		debugRunner
	} = options;

	return [
		vscode.commands.registerCommand("simpleCpp.debug.resolveProjectRoot", async () => {
			return resolveProjectRoot();
		}),
		vscode.commands.registerCommand("simpleCpp.debug.inspectLatestSession", async () => {
			const projectRoot = await resolveProjectRoot();
			if (!projectRoot) {
				vscode.window.showWarningMessage("No Simple C++ project was found for debug inspection.");
				return null;
			}

			const slot = debugStore.getLatestDebugSlot(projectRoot);
			if (!slot) {
				vscode.window.showInformationMessage("No Simple C++ debug session was found for this project yet.");
				return null;
			}

			const sourceEntry = debugStore.getLatestRewrittenSourceEntry(projectRoot, slot);
			const choice = await vscode.window.showQuickPick(buildLatestSessionInspectorItems(slot, sourceEntry), {
				title: "Inspect Latest Simple C++ Debug Session",
				placeHolder: formatSlotSummary(slot)
			});
			if (!choice) {
				return slot;
			}
			return handleInspectorAction(slot, sourceEntry, choice.action);
		}),
		vscode.commands.registerCommand("simpleCpp.debug.inspectAllSlots", async () => {
			const projectRoot = await resolveProjectRoot();
			if (!projectRoot) {
				vscode.window.showWarningMessage("No Simple C++ project was found for debug slot inspection.");
				return null;
			}

			const slots = debugStore.listDebugSlots(projectRoot)
				.filter((slot) => slot.session && typeof slot.session === "object")
				.sort(compareSlotsByLastUsedDesc);
			if (slots.length === 0) {
				vscode.window.showInformationMessage("No Simple C++ debug slots were found for this project yet.");
				return null;
			}

			const pickedSlotItem = await vscode.window.showQuickPick(
				slots.map((slot) => ({
					label: slot.name,
					description: formatSlotSummary(slot),
					detail: slot.root || "",
					slot
				})),
				{
					title: "Inspect Simple C++ Debug Slots",
					placeHolder: "Choose a recent debug slot"
				}
			);
			if (!pickedSlotItem) {
				return null;
			}

			const slot = pickedSlotItem.slot;
			const sourceEntry = debugStore.getLatestRewrittenSourceEntry(projectRoot, slot);
			const action = await vscode.window.showQuickPick(buildLatestSessionInspectorItems(slot, sourceEntry), {
				title: `Inspect ${slot.name}`,
				placeHolder: formatSlotSummary(slot)
			});
			if (!action) {
				return slot;
			}
			return handleInspectorAction(slot, sourceEntry, action.action);
		}),
		vscode.commands.registerCommand("simpleCpp.debug.readLatestSlot", async () => {
			const projectRoot = await resolveProjectRoot();
			if (!projectRoot) {
				return null;
			}
			return debugStore.getLatestDebugSlot(projectRoot);
		}),
		vscode.commands.registerCommand("simpleCpp.debug.openLatestEvents", async () => {
			const projectRoot = await resolveProjectRoot();
			if (!projectRoot) {
				vscode.window.showWarningMessage("No Simple C++ project was found for debug event inspection.");
				return null;
			}
			const slot = debugStore.getLatestDebugSlot(projectRoot);
			if (!slot || !slot.eventsPath || !fs.existsSync(slot.eventsPath)) {
				vscode.window.showInformationMessage("No saved Simple C++ debug events were found for this project yet.");
				return null;
			}
			await openTextFile(slot.eventsPath);
			return slot;
		}),
		vscode.commands.registerCommand("simpleCpp.debug.openLatestPlan", async () => {
			const projectRoot = await resolveProjectRoot();
			if (!projectRoot) {
				vscode.window.showWarningMessage("No Simple C++ project was found for debug plan inspection.");
				return null;
			}
			const slot = debugStore.getLatestDebugSlot(projectRoot);
			if (!slot || !slot.planPath || !fs.existsSync(slot.planPath)) {
				vscode.window.showInformationMessage("No saved Simple C++ debug plan was found for this project yet.");
				return null;
			}
			await openTextFile(slot.planPath);
			return slot;
		}),
		vscode.commands.registerCommand("simpleCpp.debug.openLatestRewrittenSource", async () => {
			const projectRoot = await resolveProjectRoot();
			if (!projectRoot) {
				vscode.window.showWarningMessage("No Simple C++ project was found for rewritten source inspection.");
				return null;
			}
			const slot = debugStore.getLatestDebugSlot(projectRoot);
			if (!slot) {
				vscode.window.showInformationMessage("No Simple C++ debug slot is available for this project yet.");
				return null;
			}
			const rewritten = debugStore.getLatestRewrittenSourceEntry(projectRoot, slot);
			if (!rewritten || !rewritten.rewrittenSource || !fs.existsSync(rewritten.rewrittenSource)) {
				vscode.window.showInformationMessage("The latest debug run did not save rewritten source artifacts.");
				return slot;
			}
			await openTextFile(rewritten.rewrittenSource);
			return {
				slot,
				rewritten
			};
		}),
		vscode.commands.registerCommand("simpleCpp.debug.runDebugCommand", async (runOptions = {}) => {
			const projectRoot = await resolveProjectRoot();
			if (!projectRoot) {
				vscode.window.showWarningMessage("No Simple C++ project was found for the debug command.");
				return null;
			}

			const terminal = createTerminal("Simple C++ Debug", projectRoot);
			const shellCommand = debugRunner.buildScppDebugShellCommand(runOptions);
			const baselineMtime = getFileMtimeMs(debugStore.getDebugIndexPath(projectRoot));
			runTerminalCommand(terminal, shellCommand);

			const refreshed = await waitForDebugIndexRefresh(projectRoot, debugStore, baselineMtime);
			return {
				projectRoot,
				command: shellCommand,
				index: debugStore.readDebugIndex(projectRoot),
				latestSlot: refreshed
			};
		})
	];
}

async function openTextFile(filePath) {
	const document = await vscode.workspace.openTextDocument(vscode.Uri.file(filePath));
	await vscode.window.showTextDocument(document, { preview: false });
}

async function handleInspectorAction(slot, sourceEntry, action) {
	if (action === "events") {
		if (!slot.eventsPath || !fs.existsSync(slot.eventsPath)) {
			vscode.window.showInformationMessage("The selected debug session does not have saved events.");
			return slot;
		}
		await openTextFile(slot.eventsPath);
		return slot;
	}
	if (action === "plan") {
		if (!slot.planPath || !fs.existsSync(slot.planPath)) {
			vscode.window.showInformationMessage("The selected debug session does not have a saved plan.");
			return slot;
		}
		await openTextFile(slot.planPath);
		return slot;
	}
	if (action === "rewritten_source") {
		if (!sourceEntry || !sourceEntry.rewrittenSource || !fs.existsSync(sourceEntry.rewrittenSource)) {
			vscode.window.showInformationMessage("The selected debug session does not have rewritten source artifacts.");
			return slot;
		}
		await openTextFile(sourceEntry.rewrittenSource);
		return slot;
	}
	if (action === "line_map") {
		if (!sourceEntry || !sourceEntry.lineMap || !fs.existsSync(sourceEntry.lineMap)) {
			vscode.window.showInformationMessage("The selected debug session does not have a saved rewritten-source line map.");
			return slot;
		}
		await openTextFile(sourceEntry.lineMap);
		return slot;
	}
	return slot;
}

function buildLatestSessionInspectorItems(slot, sourceEntry) {
	return [
		{
			label: "Open Events",
			description: slot.eventsPath ? slot.eventsPath : "events.json not available",
			action: "events"
		},
		{
			label: "Open Plan",
			description: slot.planPath ? slot.planPath : "plan.json not available",
			action: "plan"
		},
		{
			label: "Open Rewritten Source",
			description: sourceEntry && sourceEntry.rewrittenSource ? sourceEntry.rewrittenSource : "rewritten source not available",
			action: "rewritten_source"
		},
		{
			label: "Open Rewritten Source Line Map",
			description: sourceEntry && sourceEntry.lineMap ? sourceEntry.lineMap : "line map not available",
			action: "line_map"
		}
	];
}

function formatSlotSummary(slot) {
	const session = slot && slot.session && typeof slot.session === "object" ? slot.session : null;
	const status = session && typeof session.status === "string" && session.status !== "" ? session.status : "unknown";
	const lastUsedAt = session && typeof session.last_used_at === "string" && session.last_used_at !== ""
		? session.last_used_at
		: "unknown time";
	return `${slot.name} • ${status} • ${lastUsedAt}`;
}

function compareSlotsByLastUsedDesc(left, right) {
	const leftTs = parseSlotTimestamp(left) || 0;
	const rightTs = parseSlotTimestamp(right) || 0;
	return rightTs - leftTs;
}

function parseSlotTimestamp(slot) {
	const session = slot && slot.session && typeof slot.session === "object" ? slot.session : null;
	const value = session && typeof session.last_used_at === "string" ? session.last_used_at : "";
	const parsed = Date.parse(value);
	return Number.isFinite(parsed) ? parsed : null;
}

function getFileMtimeMs(filePath) {
	if (!filePath || !fs.existsSync(filePath)) {
		return 0;
	}
	try {
		return fs.statSync(filePath).mtimeMs;
	} catch {
		return 0;
	}
}

async function waitForDebugIndexRefresh(projectRoot, debugStore, baselineMtime) {
	const indexPath = debugStore.getDebugIndexPath(projectRoot);
	const timeoutMs = 12000;
	const pollMs = 300;
	const deadline = Date.now() + timeoutMs;

	while (Date.now() < deadline) {
		await delay(pollMs);
		const currentMtime = getFileMtimeMs(indexPath);
		if (currentMtime > baselineMtime) {
			return debugStore.getLatestDebugSlot(projectRoot);
		}
	}

	return debugStore.getLatestDebugSlot(projectRoot);
}

function delay(ms) {
	return new Promise((resolve) => setTimeout(resolve, ms));
}

module.exports = {
	registerDebugCommands
};
