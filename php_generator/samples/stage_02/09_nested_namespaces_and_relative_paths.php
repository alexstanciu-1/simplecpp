<?php

// Coverage:
// - NS-DECL-002
// - NS-QUALIFIED-001
// - NS-QUALIFIED-003
// - CLASS-STATIC-NS-002
// - cross-namespace calls rewritten to valid PHP resolution rules

namespace StageTwo\Paths\Lib {
	class Tool {
		public static function make(): int {
			return 30;
		}
	}

	function local_value(): int {
		return Tool::make();
	}
}

namespace StageTwo\Paths\App {
	function local_call(): int {
		$one = \StageTwo\Paths\Lib\local_value();
		$two = \StageTwo\Paths\Lib\Tool::make();
		$three = \StageTwo\Paths\Lib\Tool::make();
		$sum = $one + $two;
		$sum = $sum + $three;
		return $sum;
	}

	$a = local_call();
	$b = $a;

	echo $b, "\n";
}
