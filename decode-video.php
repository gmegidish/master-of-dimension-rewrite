<?php

	#$fp = fopen("16.EIDOS", "rb"); fseek($fp, 0x23);
	#$fp = fopen("16.INTRO", "rb"); fseek($fp, 0x74061);
	#$fp = fopen("16.SAMANSIC", "rb"); fseek($fp, 0x1e085);
	#$fp = fopen("16.VEMPIREA", "rb"); fseek($fp, 0x23);
	#$fp = fopen("16.SPACE", "rb"); fseek($fp, 0x23);
	#$fp = fopen("16.BFGAME", "rb"); fseek($fp, 0x0039cb4);
	#$fp = fopen("16.VG", "rb");
	#$fp = fopen("16.CREDIT", "rb");
	#$fp = fopen("16.MENGINE", "rb"); 
	#$fp = fopen("16.BRDFWR3", "rb"); 
	#$fp = fopen("16.SNORKEL", "rb"); 
	#$fp = fopen("16.PHONES", "rb");
	#$fp = fopen("16.VVKSPACE", "rb");
	$fp = fopen("../../DUMP.MOD/16.INTRO", "rb");

	$framebuffer = array_fill(0, 640*480, 0);
	$palette = array_fill(0, 256, 0);

	function decode_picture($f)
	{
		global $palette;
		global $framebuffer;

		$image_type = ord($f[0]);

		$header = substr($f, 1, 8);
		$y0 = unpack("v", substr($header, 4, 2))[1];
		$height = unpack("v", substr($header, 6, 2))[1];

		print "y0 $y0 $height\n";

		$offset = 9;
		while (true) {
			for ($y = 0; $y < $height; $y++) {

				$dst = ($y + $y0) * 640;

				$type = ord($f[$offset++]);

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
				}
			}

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

	fseek($fp, 0x10, SEEK_SET); // discard the first 16 bytes

	$index = 0;
	while (!feof($fp)) {

		$save_pic = false;

		$str = fread($fp, 2);
		if (strlen($str) < 2) {
			break;
		}

		$count = unpack("v", $str)[1];
		while ($count > 0) {
			$count--;
			$header = fread($fp, 8);
			$chunk_size = unpack("V", $header)[1];
			print "count $count chunk size $chunk_size\n";
			if ($chunk_size == 0) {
				continue;
			}

			$f = fread($fp, $chunk_size);
			print "  " . ord($f[0]) . "\n";

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

			if (ord($f[0]) == 4) {
				$img_width = unpack("v", substr($f, 1, 2))[1];
				$img_height = unpack("v", substr($f, 3, 2))[1];
				$frame_width = unpack("v", substr($f, 5, 2))[1];
				$frame_height = unpack("v", substr($f, 7, 2))[1];
				print "imgw $img_width imgh $img_height framew $frame_width frameh $frame_height\n";

				$offset = 9;;

				for ($y=0; $y<$img_height; $y += $frame_height) {
					for ($x=0; $x<$img_width; $x += $frame_width) {
						$type = ord($f[$offset++]);
						switch ($type) {
							case 0x00:
							break;

							case 0x03:
							// put_block_skip64
							$subtype = ord($f[$offset++]);
							if ($subtype != 255) {
								$subtype = min($subtype, 64);
								$offset += $subtype;
							}

							while (true) {
								$c = ord($f[$offset++]);
								if ($c == 0) {
									break;
								}

								if (($c & 0xc0) == 0) {
								}
					
								print $c; exit;
							}
							break;

							default:
							die("Don't know how to handle put_block with type $type\n");	
						}
					}
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

