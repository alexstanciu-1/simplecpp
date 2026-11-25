class BuildInfo {
    public static version: int = 3;
    public const LABEL = "JSS";
    public name: string = "frontend";

    public static current(): int {
        return BuildInfo.version;
    }

    public tag(): string {
        return `${BuildInfo.LABEL}:${this.name}`;
    }
}

let info: BuildInfo = new BuildInfo();
print(BuildInfo.current(), " ", info.tag(), "\n");
