function print_cli_data(): void {
	let argc = cli_argc();
	let argv = cli_argv();
	let args = cli_args();

	print(argc, "\n");
	print(argv[1], "\n");
	print(args[2], "\n");
}

print_cli_data();
