<?php

	class Palette
	{
		private $pal;

		private function __construct(array $pal)
		{
			$this->pal = $pal;
		}

		public function get($c): int
		{
			return $this->pal[$c];
		}

		static public function fromString($f): Palette
		{
			$palette = array_fill(0, 256, 0);

			for ($i = 0; $i < 256; $i++) {
				$r = ord($f[$i * 3 + 0]) << 2;
				$g = ord($f[$i * 3 + 1]) << 2;
				$b = ord($f[$i * 3 + 2]) << 2;
				$palette[$i] = ($r << 16) | ($g << 8) | $b;
			}

			return new Palette($palette);
		}
	}
