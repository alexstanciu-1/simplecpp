class Counter:
    value: int = 0

    def __init__(self, start: int):
        self.value = start

    def add(self, amount: int) -> void:
        self.value = self.value + amount


values: vector<int> = [2, 3]
values[1] = 5
weights: hash<int,string> = {"first": values[0], "second": values[1]}
counter: Counter = Counter(0)
for key in weights:
    weight: int = weights[key]
    counter.add(weight)

return 0 if counter.value == 7 else 1
