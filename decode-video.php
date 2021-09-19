<?php

	$im = imagecreatetruecolor(640, 480);

	//$fp = fopen("16.EIDOS", "rb"); fseek($fp, 0x23);
	//$fp = fopen("16.INTRO", "rb"); fseek($fp, 0x74061);
	$fp = fopen("16.VEMPIREA", "rb"); fseek($fp, 0x23);
	//$fp = fopen("16.THEEND", "rb"); fseek($fp, 0x23);

	$f = fread($fp, 500000);

	$offset = 0;
	for ($y=0; $y<480; $y++) {

		$type = ord($f[$offset++]);
		print "type $type\n";

		$x = 0;
		while (true) {
			print "y=$y x=$x\n";
			$times = ord($f[$offset++]);
			print "y=$y x=$x times=$times\n";
			if ($times == 0) {
				// end of line
				break;
			} else if ($times < 0x80) {
				$c = ord($f[$offset++]);
				print "y=$y x=$x c=$c times=$times\n";
				$rgb = ($c << 16) | ($c << 8) | $c;
				while ($times > 0) {
					imagesetpixel($im, $x++, $y, $rgb);
					$times--;
				}
			} else {
				$times = 256 - $times;
				while ($times > 0) {
					$c = ord($f[$offset++]);
					$rgb = ($c << 16) | ($c << 8) | $c;
					imagesetpixel($im, $x++, $y, $rgb);
					$times--;
				}
			}
		}
	}

	print "offset  $offset\n";
	imagepng($im, "a.png");
