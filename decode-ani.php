<?php
	$framebuffer = array_fill(0, 640*480, 0);
	$palette = array_fill(0, 256, 0);

	function decode_picture($f, $frame)
	{
		global $palette;
		global $framebuffer;

		$signature = substr($f, 0, 2);
		if ($signature != "\x10\x01") {
			var_dump(bin2hex($signature));
			print "Incorrect signature\n";
			return;
		}

		$header = substr($f, 0, 20);
		$width = unpack("v", substr($header, 3, 2))[1];
		$height = unpack("v", substr($header, 5, 2))[1];
		$dunno = ord($header[8]);
		$frames_in_animation = unpack("V", substr($header, 8, 4))[1];
		print "width $width height $height frames $frames_in_animation\n";

		$frames = [];
		for ($i=0; $i<$frames_in_animation; $i++) {
			$args = array_values(unpack("v3V", substr($f, 0xc + $i*8, 8)));
			$frames[] = [
				"width" => $args[0],
				"height" => $args[1],
				"size" => $args[2],
			];
		}

		$y0 = 0;

		//$frame = 0;
		$offset = 0xc + $frames_in_animation*8;
		for ($i=0; $i<$frame; $i++) {
			$offset += $frames[$i]["size"];
		}

		$offset += 9;
		$size = $frames[$frame]["size"];
		$end = $offset + $size;
		while ($offset < $end) {
			for ($y = 0; $y < $height; $y++) {
				if ($offset >= $end) break;
				$dst = ($y + $y0) * 640;

				$type = ord($f[$offset++]);
				//print "y=$y offset=$offset type=$type\n";

				switch($type) {
					case 0x0:
					for ($x=0; $x<640; $x++) {
						$framebuffer[$dst++] = ord($f[$offset++]);
					}
					break;

					case 0x1:
					while (true) {
						$times = ord($f[$offset++]);
						if ($times == 0) {
							// end of line
							break;
						} else if ($times < 0x80) {
							$c = ord($f[$offset++]);
							while ($times-- > 0) {
								$framebuffer[$dst++] = $c;
							}
						} else {
							$times = 256 - $times;
							while ($times-- > 0) {
								$framebuffer[$dst++] = ord($f[$offset++]);
							}
						}
					}
					break;

					case 0x02:
					while (true) {
						$times = ord($f[$offset++]);
						if ($times == 0) {
							break;
						}

						if ($times >= 0x80) {
							$times = 256 - $times;
							while ($times-- > 0) {
								$framebuffer[$dst++] = ord($f[$offset++]);
							}
						} else {
							$dst += $times;
						}
					}
					break;

					case 0x03:
					$x = 0;
					$skip = ord($f[$offset++]);
					$dst += $skip;

					while (true) {
						$times = ord($f[$offset++]);
						if ($times < 0x80 && $times > 0) {
							$c = ord($f[$offset++]);
							while ($times-- > 0) {
								$framebuffer[$dst++] = $c;
							}
						} else if ($times >= 0x80) {
							$times = 256 - $times;
							while ($times-- > 0) {
								$framebuffer[$dst++] = ord($f[$offset++]);
							}
						}

						$skip = ord($f[$offset++]);
						if ($skip == 0xff) {
							break;
						}

						$dst += $skip;
					}
					break;

					case 0x04:
					break;

					default:
					//die("Can't handle $type\n");
					//return;
					break;
				}
			}

break;
			$header = substr($f, $offset, 4);
			$skipy = unpack("v", substr($header, 0, 2))[1];
			$y0 = $y0 + $height + $skipy;
			$height = unpack("v", substr($header, 2, 2))[1];
			$offset += 4;

			if ($height == 0) break;
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

	$names = glob("7.CURS*");
	$names = glob("7.OPETDEF");
	foreach($names as $name) {
		$name = substr($name, 2);
		print "Processing $name\n";

		$framebuffer = array_fill(0, 640*480, 0);
		$palette = array_fill(0, 256, 0);

		$f = file_get_contents("3.MENU");
		$f = file_get_contents("3.OPTIONS");
		$f = substr($f, 18);
		for ($i=0; $i<256; $i++) {
			$r = ord($f[$i*3+0]) << 2;
			$g = ord($f[$i*3+1]) << 2;
			$b = ord($f[$i*3+2]) << 2;
			$palette[$i] = ($r << 16) | ($g << 8) | $b;
		}

		$f = file_get_contents("7.$name");
		for ($frame=0; $frame<8; $frame++) {
			$framebuffer = array_fill(0, 640*480, 0);
			decode_picture($f, $frame);
			$im = create_image();
			imagepng($im, "$name-$frame.PNG");
		}

		print "Saved $name.PNG\n";
	}

