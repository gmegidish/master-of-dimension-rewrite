<?php

	require_once __DIR__ . "/Palette.php";
	require_once __DIR__ . "/FileReader.php";

	class PaletteDecoder
	{
		public function decode(FileReader $fp): Palette
		{
			$dummy = $fp->read(18);
			$data = $fp->read(256*3);
			return Palette::fromString($data);
		}
	}

