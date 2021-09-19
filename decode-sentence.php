<?php

	$f = fopen("SENTENCE.ENG", "rb");

	$signature = fread($f, 4); // 0x000008cb

	while (!feof($f)) {
		$id_length = fread($f, 4);
		$id_length = unpack("V", $id_length)[1];
		$id = fread($f, $id_length); 

		$length = fread($f, 4);
		$length = unpack("V", $length)[1];
		$text = fread($f, $length);

		print "id: '$id' => '$text'\n";
	}

/*

        cb 08 00 00 05 00 00 00 41 41 30 30 30 8b 01 00
0000010 00 22 27 54 77 61 73 20 64 75 73 6b 20 61 73 20
0000020 74 68 65 20 73 65 63 6f 6e 64 20 52 61 63 6f 6e
0000030 61 6c 20 73 75 6e 20 73 65 74 20 73 6c 6f 77 6c
0000040 79 20 69 6e 20 74 68 65 20 73 6f 75 74 68 2c 20
0000050 62 72 69 6e 67 69 6e 67 20 75 70 6f 6e 20 44 75
0000060 6d 6c 6f 62 61 27 73 20 69 6e 68 61 62 69 74 61
0000070 6e 74 73 20 6f 6e 65 20 6d 6f 72 65 20 6c 6f 6e
0000080 67 2c 20 6d 75 67 67 79 20 65 71 75 61 74 6f 72
*/
