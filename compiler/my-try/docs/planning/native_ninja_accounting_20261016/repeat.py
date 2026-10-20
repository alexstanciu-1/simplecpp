from pathlib import Path
import benchmark as b
for repeat in [2,3]:
 b.PUBLISHED=b.BASE/f'published-repeat-{repeat}'
 b.BUILD=b.BASE/f'app-build-repeat-{repeat}'
 b.RESULTS=b.BASE/f'measurements-repeat-{repeat}';b.RESULTS.mkdir()
 b.main()
