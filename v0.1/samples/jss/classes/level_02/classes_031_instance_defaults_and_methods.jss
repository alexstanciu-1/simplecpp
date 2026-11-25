class Greeting {
    public prefix: string = "Hello";
    public name: string = "Ada";

    constructor(name: string = "Ada") {
        this.name = name;
    }

    public render(): string {
        return `${this.prefix} ${this.name}`;
    }
}

let greeting: Greeting = new Greeting("Lin");
print(greeting.render(), "\n");
print(greeting.prefix, "\n");
