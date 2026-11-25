class BaseValue {
    value(): int {
        return 10;
    }
}

class ChildValue extends BaseValue {
}

let item: ChildValue = new ChildValue();
print(item.value(), "\n");
