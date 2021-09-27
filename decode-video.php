<?php

	$framebuffer = array_fill(0, 640*480, 0);
	$palette = array_fill(0, 256, 0);

	function decode_picture($f)
	{
		global $palette;
		global $framebuffer;

		$header = substr($f, 1, 8);
		$y0 = unpack("v", substr($header, 4, 2))[1];
		$height = unpack("v", substr($header, 6, 2))[1];

		$offset = 9;
		for ($y=0; $y<$height; $y++) {

			$dst = ($y + $y0) * 640;

			$type = ord($f[$offset++]);

			switch($type) {

				case 0x0:
				for ($x=0; $x<640; $x++) {
					$framebuffer[$dst++] = ord($f[$offset++]);
				}
				break;

				case 0x1:
				$x = 0;
				while (true) {
					$times = ord($f[$offset++]);
					if ($times == 0) {
						// end of line
						break;
					} else if ($times < 0x80) {
						$c = ord($f[$offset++]);
						while ($times > 0) {
							$framebuffer[$dst++] = $c;
							$x++;
							$times--;
						}
					} else {
						$times = 256 - $times;
						while ($times > 0) {
							$c = ord($f[$offset++]);
							$framebuffer[$dst++] = $c;
							$x++;
							$times--;
						}
					}
				}
				break;

				case 0x02:
				$x = 0;
				while (true) {
					$times = ord($f[$offset++]);
					if ($times == 0) {
						break;
					}

					if ($times >= 0x80) {
						$times = 256 - $times;
						while ($times > 0) {
							$c = ord($f[$offset++]);
							$framebuffer[$dst++] = $c;
							$x++;
							$times--;
						}
					} else {
						$dst += $times;
						$x += $times;
					}
				}
				break;

				case 0x03:
				$x = 0;
				while (true) {
					$skip = ord($f[$offset++]);
					$x += $skip;
					$dst += $skip;

					$times = ord($f[$offset++]);
					if ($times < 0x80 && $times > 0) {
						$c = ord($f[$offset++]);
						while ($times > 0) {
							$framebuffer[$dst++] = $c;
							$x++;
							$times--;
						}
					} else if ($times >= 0x80) {
						$times = 256 - $times;
						while ($times > 0) {
							$c = ord($f[$offset++]);
							$framebuffer[$dst++] = $c;
							$x++;
							$times--;
						}
					}

					if (ord($f[$offset]) == 0xff) {
						$offset++;
						break;
					}
				}
				break;

				case 0x04:
				break;

				default:
				die("Can't handle $type");
			}
		}
	}

	function create_image()
	{
		global $palette;
		global $framebuffer;

		$offset = 0;
		$im = imagecreatetruecolor(640, 480);
		for ($y=0; $y<480; $y++) {
			for ($x=0; $x<640; $x++) {
				$c = $framebuffer[$offset++];;
				$color = $palette[$c];
				imagesetpixel($im, $x, $y, $color);
			}
		}

		return $im;
	}

	#$fp = fopen("16.EIDOS", "rb"); fseek($fp, 0x23);
	#$fp = fopen("16.INTRO", "rb"); fseek($fp, 0x74061);
	#$fp = fopen("16.SAMANSIC", "rb"); fseek($fp, 0x1e085);
	#$fp = fopen("16.VEMPIREA", "rb"); fseek($fp, 0x23);
	#$fp = fopen("16.SPACE", "rb"); fseek($fp, 0x23);
	#$fp = fopen("16.BFGAME", "rb"); fseek($fp, 0x0039cb4);
	#$fp = fopen("16.VG", "rb");
	#$fp = fopen("16.CREDIT", "rb");
	#$fp = fopen("16.INTRO3", "rb"); fseek($fp, 0x1e);
	#$fp = fopen("16.WINGS", "rb"); fseek($fp, 0x21);
	#$fp = fopen("16.SPACE", "rb"); fseek($fp, 0x23);
	$fp = fopen("16.PHONES", "rb");

	fseek($fp, 0x10, SEEK_SET); // discard the first 16 bytes

	$index = 0;
	while (!feof($fp)) {

		$save_pic = false;

		$str = fread($fp, 2);
		if (strlen($str) != 2) {
			break;
		}

		$count = unpack("v", $str)[1];
		while ($count > 0) {
			$count--;
			$header = fread($fp, 8);
			$chunk_size = unpack("V", $header)[1];
			$f = fread($fp, $chunk_size);

			if (ord($f[0]) == 0) {
				// palette
				for ($i=0; $i<256; $i++) {
					$r = ord($f[2 + $i*3 + 0]) << 2;
					$g = ord($f[2 + $i*3 + 1]) << 2;
					$b = ord($f[2 + $i*3 + 2]) << 2;
					$color = ($r << 16) | ($g << 8) + $b;
					$palette[$i] = $color;
				}
			}

			if (ord($f[0]) == 2 || ord($f[0]) == 1) {
				decode_picture($f);
				$save_pic = true;
			}
		}

		if ($save_pic) {
			$im = create_image();
			imagepng($im, sprintf("CREDIT-%04d.png", $index++));
		}
	}

