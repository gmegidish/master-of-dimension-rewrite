<?php

	require_once __DIR__ . "/FileReader.php";
		
	class UnpackResourcesAnd
	{
		public function run()
		{
			$game = "and";

			$fp = fopen("games/$game/ADVENT.IDX", "rb");
			$idx = new FileReader($fp);

			$fp = fopen("games/$game/ADVENT.RES", "rb");

			$signature = $idx->read(4);
			while (!$idx->feof()) {
				$id = $idx->readPascalString();
				$type = $idx->readLong();
				$offset = $idx->readLong();
				$size = $idx->readLong();
				print "id: '$id', type: $type, offset: $offset, size: $size\n";

				if (true) {
					fseek($fp, $offset, SEEK_SET);
					$data = fread($fp, $size);
					@mkdir("dump");
					@mkdir("dump/$game");
					file_put_contents("dump/$game/$type.$id", $data);
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
		}
	}

	$ref = new UnpackResourcesAnd();
	$ref->run();

