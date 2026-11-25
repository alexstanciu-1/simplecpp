namespace Lib;

class Box {
	static value(): int {
		return 41;
	}
}

namespace App;

use Lib.Box;

print(Box.value(), "\n");
