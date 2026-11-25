class Box {
	name: string = "ready";
}

let maybe: ?Box = new Box();
print(maybe?.name, "\n");
