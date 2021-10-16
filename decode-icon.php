<?php
	$framebuffer = array_fill(0, 640*480, 0);
	$palette = array_fill(0, 256, 0);

	class XPictureDecoder
	{
		public function decode(string $f)
		{
			$framebuffer = array_fill(0, 640*480, 0);

			$signature = substr($f, 0, 2);
			if ($signature != "\x10\x01") {
				var_dump(bin2hex($signature));
				print "Incorrect signature\n";
				return;
			}

			$header = substr($f, 0, 20);
			$width = unpack("v", substr($header, 3, 2))[1];
			$height = unpack("v", substr($header, 5, 2))[1];
			$size = unpack("V", substr($header, 16, 4))[1];
			print "    width $width height $height size $size\n";

			$y0 = 0;

			$offset = 0x1d;
			while ($offset < $size) {
				for ($y = 0; $y < $height; $y++) {
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
						die("Can't handle $type\n");
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

			return $framebuffer;
		}
	}

	function create_image(array $framebuffer, array $palette)
	{
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

	function load_palette(string $f): array
	{
		$palette = array_fill(0, 256, 0);

		$f = substr($f, 18);
		for ($i=0; $i<256; $i++) {
			$r = ord($f[$i*3+0]) << 2;
			$g = ord($f[$i*3+1]) << 2;
			$b = ord($f[$i*3+2]) << 2;
			$palette[$i] = ($r << 16) | ($g << 8) | $b;
		}

		return $palette;
	}

	$f = file_get_contents("3.GENERAL");
	$palette = load_palette($f);

	$name = "GUITAR";

	$f = file_get_contents("2.$name");
	$decoder = new XPictureDecoder();
	$framebuffer = $decoder->decode($f);
	$im = create_image($framebuffer, $palette);
	imagepng($im, "$name.PNG");

