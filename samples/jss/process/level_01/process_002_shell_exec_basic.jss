let out = shell_exec("printf shell_exec_ok");

if (out === false) {
	print("false\n");
} else {
	print(out, "\n");
}
