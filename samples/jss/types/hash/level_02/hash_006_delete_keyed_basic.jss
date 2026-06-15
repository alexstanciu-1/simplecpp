let items: hash<int> = {"a": 1, "b": 2};
delete items["a"];
delete items["missing"];
print(items["b"], "\n");
