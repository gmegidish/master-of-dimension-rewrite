<?php

	class FileReader
	{
		private $fp;

		public function __construct($fp)
		{
			$this->fp = $fp;
		}

		public function readPascalString(): string
		{
			$length = $this->readByte();
			return $this->read($length);
		}

		public function readByte(): int
		{
			return ord($this->read(1));
		}

		public function read(int $length): string
		{
			return fread($this->fp, $length);
		}

		public function readLong(): int
		{
			$str = $this->read(4);
			return unpack("V", $str)[1];
		}

		public function feof(): bool
		{
			return feof($this->fp);
		}
	}

