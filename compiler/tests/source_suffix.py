"""Source-file extensions for generated multi-flavor fixtures."""


def source_suffix(flavor):
    return 'phs' if flavor == 'php' else flavor
