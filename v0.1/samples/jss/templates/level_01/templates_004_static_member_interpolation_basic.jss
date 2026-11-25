class BuildInfo {
    static version: int = 3;
    const LABEL = "JSS";
}

print(`v${BuildInfo.version}:${BuildInfo.LABEL}`, "\n");
