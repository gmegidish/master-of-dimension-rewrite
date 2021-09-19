<?php

	$im = imagecreatetruecolor(640, 480);

	$fp = fopen("16.EIDOS", "rb"); fseek($fp, 0x23);
	//$fp = fopen("16.INTRO", "rb"); fseek($fp, 0x74061);
	//$fp = fopen("16.VEMPIREA", "rb"); fseek($fp, 0x23);
	//$fp = fopen("16.THEEND", "rb"); fseek($fp, 0x23);

// 0000000: 10 00 01 00 25 00 04 00 00 00 00 00 00 00 00 00
// 10 00 01 00 signature
// 25 00       37 frames in this video (each frame has header of 11 bytes)

// 0000010: 02 00 [9c 23] 00 00 10 00 02 00 02 [80 02] [e0 01] [00 00] [e0 01]
// 9c 23       0x239c bytes in this frame
// 80 02       640 pixels wide
// e0 01       480 pixels high
// 00 00       starting at line 0
// e0 01       ending at line 480

// 00026c0: 01 00 01 00 00 00 10 00 03 00 03 <- an example of an empty frame

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

