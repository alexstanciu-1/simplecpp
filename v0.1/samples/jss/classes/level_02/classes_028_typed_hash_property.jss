class Store {
	items: hash<int> = {"a": 1};
}

let store: Store = new Store();
print(store.items["a"], "\n");
