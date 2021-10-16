<?php

	require_once __DIR__ . "/FileReader.php";
	require_once __DIR__ . "/Picture.php";
	require_once __DIR__ . "/Palette.php";
	require_once __DIR__ . "/PaletteDecoder.php";

	class PictureDecoder
	{
		public function decode(FileReader $fp): array
		{
			$signature = $fp->read(2);
			if ($signature != "\x10\x01") {
				var_dump(bin2hex($signature));
				print("Incorrect signature\n");
				return [];
			}

			$header = $fp->read(18);
			$width = unpack("v", substr($header, 1, 2))[1];
			$height = unpack("v", substr($header, 3, 2))[1];
			$size = unpack("V", substr($header, 14, 4))[1];
			print "    width $width height $height size $size\n";

			$framebuffer = array_fill(0, $width * $height, 0);

			$y0 = 0;

			$f = $fp->read($size);

			$offset = 0x9;
			while ($offset < $size) {
				for ($y = 0; $y < $height; $y++) {
					$dst = ($y + $y0) * 640;

					$type = ord($f[$offset++]);
					//print "y=$y offset=$offset type=$type\n";

					switch ($type) {
						case 0x0:
							for ($x = 0; $x < $width; $x++) {
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

