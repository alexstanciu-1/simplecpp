"""Expose the generated entry body to a native fixture's own exception handler."""
import re


def raw_entry(source):
    pattern = r'int main\(\) \{ return ::scpp::run_entry_with_exceptions\(([^)]+)\); \}'
    replacement = r'int tested_entry() { return \1(); }'
    return re.sub(pattern, replacement, source)
