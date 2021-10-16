<?php

	require_once __DIR__ . "/Palette.php";

	class Picture
	{
		private $im;

		public function __construct(int $width, int $height, array $framebuffer, Palette $palette)
		{
			$offset = 0;
			$im = imagecreatetruecolor($width, $height);
			for ($y = 0; $y < $height; $y++) {
				for ($x = 0; $x < $width; $x++) {
					$c = $framebuffer[$offset++];;
					$color = $palette->get($c);
					imagesetpixel($im, $x, $y, $color);
				}
			}

			$this->im = $im;
		}

		public function getGdImage()
		{
			return $this->im;
		}
	}
