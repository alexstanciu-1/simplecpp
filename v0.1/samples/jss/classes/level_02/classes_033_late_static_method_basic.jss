class A {
	static run(): string {
		return static::hello();
	}

	static hello(): string {
		return "A";
	}
}

print(A.run(), "\n");
