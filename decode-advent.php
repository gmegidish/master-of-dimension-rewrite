<?php

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

/*
0000000 bf 21 00 00 05 43 53 44 45 46 02 00 00 00 00 00
0000010 00 00 3f 01 00 00 08 43 53 4f 42 4a 45 43 54 02
0000020 00 00 00 43 01 00 00 42 01 00 00 06 43 53 45 58
0000030 49 54 02 00 00 00 89 02 00 00 9a 01 00 00 04 43
0000040 53 55 50 07 00 00 00 27 04 00 00 7c 03 00 00 06
0000050 43 53 44 4f 57 4e 07 00 00 00 a7 07 00 00 a4 03
0000060 00 00 06 43 53 4c 45 46 54 07 00 00 00 4f 0b 00
0000070 00 be 02 00 00 07 43 53 52 49 47 48 54 07 00 00
0000080 00 11 0e 00 00 be 02 00 00 05 42 4f 59 4c 54 0b
0000090 00 00 00 d3 10 00 00 36 b3 00 00 05 42 4f 59 52
00000a0 54 0b 00 00 00 0d c4 00 00 a8 b4 00 00 05 42 4f
*/
