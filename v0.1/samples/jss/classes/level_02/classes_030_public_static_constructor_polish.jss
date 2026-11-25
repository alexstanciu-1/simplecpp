class CounterBox {
    public static count: int = 1;
    public value: int;

    constructor(value: int) {
        this.value = value;
    }

    public static next(): int {
        return CounterBox.count;
    }
}

let item: CounterBox = new CounterBox(CounterBox.next());
print(item.value, "\n");
