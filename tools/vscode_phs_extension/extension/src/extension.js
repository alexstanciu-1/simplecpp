"use strict";

const vscode = require("vscode");
const { registerStaticCompletion } = require("./static_completion");

function activate(context) {
	registerStaticCompletion(context);

	context.subscriptions.push(
		registerScppCommand("simpleCpp.buildProject", "scpp build"),
		registerScppCommand("simpleCpp.runProject", "scpp run"),
		registerScppCommand("simpleCpp.doctor", "scpp --doctor"),
		registerScppCommand("simpleCpp.docsStrict", "scpp docs strict")
	);
}

function deactivate() {}

function registerScppCommand(commandId, shellCommand) {
	return vscode.commands.registerCommand(commandId, async () => {
		const terminal = vscode.window.createTerminal({
			name: "Simple C++"
		});
		terminal.show(true);
		terminal.sendText(shellCommand, true);
	});
}

module.exports = {
	activate,
	deactivate
};
