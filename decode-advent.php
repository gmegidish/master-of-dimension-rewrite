<?php

	class FileReader
	{
		public function __construct($fp)
		{
			$this->fp = $fp;
		}

		public function readPascalString(): string
		{
			$length = ord($this->readByte());
			return $this->read($length);
		}

		public function readByte()
		{
			return $this->read(1);
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
		
	class UnpackResourcesAnd
	{
		public function run()
		{
			$fp = fopen("games/and/ADVENT.IDX", "rb");
			$idx = new FileReader($fp);

			$fp = fopen("games/and/ADVENT.RES", "rb");

			$signature = $idx->read(4);
			while (!$idx->feof()) {
				$id = $idx->readPascalString();
				$type = $idx->readLong();
				$offset = $idx->readLong();
				$size = $idx->readLong();
				print "id: '$id', type: $type, offset: $offset, size: $size\n";

				if ($type == 16) {
					fseek($fp, $offset, SEEK_SET);
					$data = fread($fp, $size);
					@mkdir("dump");
					@mkdir("dump/and");
					file_put_contents("dump/and/$type.$id", $data);
				}
			}

		}
	}

	class UnpackResourcesMod
	{
		public function run()
		{
			$f = fopen("ADVENT77.R", "rb");
			// $f = fopen("ADVENTH7.R", "rb");

			fseek($f, -4, SEEK_END);
			$toc = fread($f, 4);
			$toc = unpack("V", $toc)[1];

			fseek($f, $toc, SEEK_SET);

			$signature = fread($f, 4); // 0x21bfA

			while (!feof($f)) {
				$id_length = ord(fread($f, 1));
				$id = fread($f, $id_length); 

				$type = fread($f, 4);
				$type = unpack("V", $type)[1];

				$offset = fread($f, 4);
				$offset = unpack("V", $offset)[1];

				$length = fread($f, 4);
				$length = unpack("V", $length)[1];

				print "id: '$id', type: $type, offset: $offset, length: $length\n";
				$ptr = ftell($f);
				fseek($f, $offset, SEEK_SET);
				$raw = fread($f, $length);
				file_put_contents("DUMP/$type.$id", $raw);
				fseek($f, $ptr, SEEK_SET);
			}

			// 13 is audio: sox -b 8 -e unsigned-integer -c 1 -r 22050 13.XX120.raw  a.mp3
			// 16 is video+audio
			//  6 looks like backgrounds, or sprite sheets
			// 20 "ADV mem file"
			//  3 ??
			//  4 scripts?
		}
	}

	$ref = new UnpackResourcesAnd();
	$ref->run();

