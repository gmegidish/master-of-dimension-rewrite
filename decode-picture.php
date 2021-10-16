<?php
	require_once __DIR__ . "/FileReader.php";
	require_once __DIR__ . "/Picture.php";
	require_once __DIR__ . "/PictureDecoder.php";
	require_once __DIR__ . "/Palette.php";
	require_once __DIR__ . "/PaletteDecoder.php";
	require_once __DIR__ . "/ScriptDecoder.php";

	$name = "MENU";
//	$name = "VVI2";
//	$name = "MAP";
//	$name = "OPTIONS";

	$decoder = new PictureDecoder();
	$image = $decoder->decode(new FileReader(fopen("6.$name", "rb")));
	$decoder = new PaletteDecoder();
	$palette = $decoder->decode(new FileReader(fopen("3.$name", "rb")));
	$picture = new Picture(640, 480, $image, $palette);
	$im = $picture->getGdImage();

	$script = new ScriptDecoder(new FileReader(fopen("4.$name", "rb")));
	$script->read();
	$areas = $script->getAreas();
	$cursors = $script->getCursors();

	$colors = [0xff0000, 0x00ff00, 0x0000ff, 0xff00ff, 0xffff00, 0x00ffff, 0xffffff];

	if (true) {
		$index = 0;
		foreach ($cursors as $cursor) {
			if ($cursor[0] < 0) {
				continue;
			}

			print "$index: " . json_encode($cursor) . "\n";
			$index++;

//			imagerectangle($im, $cursor[0], $cursor[1], $cursor[2], $cursor[3], 0xff00ff);
		}
	}

	if (true) {
		foreach ($areas as $area) {
			imagerectangle($im, $area->x0, $area->y0, $area->x1, $area->y1, $colors[$area->dunno % count($colors)]);
		}
	}

	imagepng($im, "1.png");
