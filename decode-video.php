<?php

	$im = imagecreatetruecolor(640, 480);

	$fp = fopen("16.EIDOS", "rb"); fseek($fp, 0x23);
	$fp = fopen("16.INTRO", "rb"); fseek($fp, 0x74061);
	$fp = fopen("16.SAMANSIC", "rb"); fseek($fp, 0x1e085);
	$fp = fopen("16.VEMPIREA", "rb"); fseek($fp, 0x23);
	$fp = fopen("16.THEEND", "rb"); fseek($fp, 0x23);
	$fp = fopen("16.SPACE", "rb"); fseek($fp, 0x23);
	$fp = fopen("16.BFGAME", "rb"); fseek($fp, 0x0039cb4);
	$fp = fopen("16.VG", "rb"); fseek($fp, 0x23);

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

		if ($type != 1)  {
			print bin2hex(substr($f, $offset, 640+5)) . "\n";
		}

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

	$chunk_type = unpack("V", substr($f, $offset, 4))[1];
	$offset += 4;
	$chunk_size = unpack("V", substr($f, $offset, 4))[1];
	$offset += 4;
	if ($chunk_type == 0x00000000) {
		// palette
		$header_size = unpack("V", substr($f, $offset, 4))[1];
		$offset += 4;
		$header = substr($f, $offset, $header_size);
		$offset += strlen($header);
		$palette = substr($f, $offset, $chunk_size - $header_size);
		$offset += strlen($palette);

		for ($y=0; $y<480; $y++) {
			for ($x=0; $x<640; $x++) {
				$c = imagecolorat($im, $x, $y) & 0xff;
				$r = ord($palette[$c*3+0]) << 2;
				$g = ord($palette[$c*3+1]) << 2;
				$b = ord($palette[$c*3+2]) << 2;
				$rgb = ($r << 16) | ($g << 8) | $b;
				imagesetpixel($im, $x, $y, $rgb);
			}
		}
	}

	// 00 00 00 00  chunk_type palette
        // 02 03 00 00  // chunk_size
	// 02 00 00 00 // header size
        // 00 ff // from color, to color

	$offset += 0x23;
	print "offset  $offset\n";
	imagepng($im, "a.png");

