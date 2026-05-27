"use strict";

function buildScppDebugShellCommand(options) {
	return buildScppDebugArgv(options).map(quoteIfNeeded).join(" ");
}

function buildScppDebugArgv(options) {
	const args = ["scpp", "debug"];
	if (options && typeof options === "object") {
		if (options.format) {
			args.push(`--format=${options.format}`);
		}
		if (options.argsJson) {
			args.push(`--args=${options.argsJson}`);
		}
		if (options.planOnly) {
			args.push("--plan-only");
		}
		if (options.buildRuntime) {
			args.push("--build-runtime");
		}
		if (options.buildDependencies) {
			args.push("--build-dependencies");
		}
		if (options.callable) {
			args.push(`--call=${options.callable}`);
		}
		if (options.callArgsJson) {
			args.push(`--call-args=${options.callArgsJson}`);
		}
		if (options.callThisJson) {
			args.push(`--call-this=${options.callThisJson}`);
		}
		if (options.execExpression) {
			args.push(`--exec=${options.execExpression}`);
		}
		if (Array.isArray(options.actions)) {
			for (const action of options.actions) {
				if (!action || typeof action !== "object" || typeof action.flag !== "string" || typeof action.spec !== "string") {
					continue;
				}
				args.push(`--${action.flag}=${action.spec}`);
			}
		}
	}
	return args;
}

function quoteIfNeeded(value) {
	return /[\s"'{}[\]()\\:$]/.test(value) ? quoteShellLiteral(value) : value;
}

function quoteShellLiteral(value) {
	return `'${String(value).replace(/'/g, `'\"'\"'`)}'`;
}

module.exports = {
	buildScppDebugShellCommand,
	buildScppDebugArgv
};
