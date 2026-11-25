function pick(value: ?int): int {
    return value ?? 10;
}

print(pick(null), "\n");
print(pick(3), "\n");
