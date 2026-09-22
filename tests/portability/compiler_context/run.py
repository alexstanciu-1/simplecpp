"""Active rewrite compiler proof entrypoint; no stage has been adopted yet."""
import sys


def main():
    print('Compiler rewrite has no ready component. The previous 39-file cumulative '
          'proof is archived at tests/portability/reference/pre-rewrite/compiler_context; '
          'replay it from Git branch v0.2/pre-rewrite-reference.', file=sys.stderr)
    return 1


if __name__ == '__main__':
    sys.exit(main())
