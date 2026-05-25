'use strict';

const path = require('path');
const vscode = require('vscode');
const { LanguageClient, TransportKind } = require('vscode-languageclient/node');

/** @type {Map<string, LanguageClient>} */
const clients = new Map();
let devSmokeOpened = false;

async function activate(context) {
  context.subscriptions.push(
    vscode.commands.registerCommand('simplecpp.stan.restartServer', async () => {
      await restartAllClients(context);
      vscode.window.showInformationMessage('Simple C++ STAN server restarted.');
    })
  );

  context.subscriptions.push(
    vscode.commands.registerCommand('simplecpp.stan.openSmokeFile', async () => {
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
      if (event.affectsConfiguration('simplecpp.stan')) {
        await restartAllClients(context);
      }
    })
  );

  await synchronizeWorkspaceClients(context);
  await openSmokeFileIfAvailable(context, false);
}

async function synchronizeWorkspaceClients(context) {
  const workspaceFolders = vscode.workspace.workspaceFolders || [];
  const expectedKeys = new Set(workspaceFolders.map((folder) => folder.uri.fsPath));

  for (const [key, client] of clients.entries()) {
    if (expectedKeys.has(key)) {
      continue;
    }
    await client.stop();
    clients.delete(key);
  }

  for (const folder of workspaceFolders) {
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
  const smokeFolder = workspaceFolders.find((folder) => path.basename(folder.uri.fsPath) === 'smoke-workspace');
  if (!smokeFolder) {
    return;
  }

  const target = vscode.Uri.file(path.join(smokeFolder.uri.fsPath, 'main.phs'));
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

function createClient(context, workspaceFolder) {
  const configuration = vscode.workspace.getConfiguration('simplecpp.stan', workspaceFolder.uri);
  const phpBinary = configuration.get('phpBinary', 'php');
  const configuredScript = configuration.get('serverScript', '');
  const serverScript = configuredScript && configuredScript.trim() !== ''
    ? configuredScript
    : context.asAbsolutePath(path.join('..', '..', 'bin', 'stan_lsp_server.php'));

  const serverOptions = createServerOptions(workspaceFolder, phpBinary, serverScript);

  const clientOptions = {
    workspaceFolder,
    documentSelector: [
      { scheme: 'file', language: 'simplecpp-phs', pattern: `${workspaceFolder.uri.fsPath.replace(/\\/g, '/')}/**/*` },
      { scheme: 'file', pattern: `${workspaceFolder.uri.fsPath.replace(/\\/g, '/')}/**/*.phs` }
    ],
    synchronize: {
      fileEvents: vscode.workspace.createFileSystemWatcher(new vscode.RelativePattern(workspaceFolder, '**/*.{phs,php,json}'))
    },
    outputChannel: vscode.window.createOutputChannel(`Simple C++ STAN (${workspaceFolder.name})`),
    traceOutputChannel: vscode.window.createOutputChannel(`Simple C++ STAN Trace (${workspaceFolder.name})`)
  };

  const client = new LanguageClient(
    `simplecppStan:${workspaceFolder.uri.fsPath}`,
    `Simple C++ STAN (${workspaceFolder.name})`,
    serverOptions,
    clientOptions
  );

  const traceLevel = configuration.get('trace.server', 'off');
  client.setTrace(traceLevel);
  return client;
}

function createServerOptions(workspaceFolder, phpBinary, serverScript) {
  const wslLaunch = resolveWslLaunch(workspaceFolder.uri.fsPath, serverScript, phpBinary);
  if (wslLaunch !== null) {
    return {
      command: 'wsl.exe',
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

  const linuxWorkspacePath = `/${workspaceMatch[2].replace(/\\/g, '/')}`;
  const linuxScriptPath = `/${scriptMatch[2].replace(/\\/g, '/')}`;
  return ['-d', workspaceDistro, '--cd', linuxWorkspacePath, phpBinary, linuxScriptPath];
}

async function deactivate() {
  const stops = [];
  for (const client of clients.values()) {
    stops.push(client.stop());
  }
  clients.clear();
  await Promise.all(stops);
}

module.exports = {
  activate,
  deactivate
};
