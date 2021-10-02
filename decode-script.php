<?

	//$fp = fopen("4.VE", "rb"); fseek($fp, 0xa870);
	//$fp = fopen("4.INTRO", "rb"); fseek($fp, 0x8a);
	$fp = fopen("4.OPTIONS", "rb");
	$fp = fopen("4.INTRO", "rb");
	$fp = fopen("4.MENU", "rb");
	//$fp = fopen("4.THEEND", "rb"); 

	function read_array_of_strings($fp): array
	{
		$strings = [];

		$count = unpack("V", fread($fp, 4))[1];
		for ($i=0; $i<$count; $i++) {
			$len = ord(fread($fp, 1));
			$str = fread($fp, $len);
			$strings[] = $str;
		}

		return $strings;
	}

	fseek($fp, 0, SEEK_SET);
	$header = fread($fp, 4);
	//$header = fread($fp, 12);

	$script_type = 0;
	if (unpack("V", $header)[1] == 1) {
		$script_type = 1;
		die("what kind of script is this?\n");
	}

	$arr1 = read_array_of_strings($fp);
	$arr2 = read_array_of_strings($fp);
	$arr3 = read_array_of_strings($fp);
	$arr4 = read_array_of_strings($fp);
	$arr5 = read_array_of_strings($fp);
	$arr6 = read_array_of_strings($fp);
	$arr7 = read_array_of_strings($fp);

	$count_of = fread($fp, 4); // if >= 0x96 then die files_load_scn:
	$count_of = unpack("V", $count_of)[1];
	$what = fread($fp, 176 * $count_of);

	print "count $count_of\n";
	for ($i=0; $i<$count_of; $i++) {
		print bin2hex(substr($what, $i*176, 176)) . "\n";
	}

	$count_of = fread($fp, 4); // if >= 1000 then die  files_load_scn:253
	$count_of = unpack("V", $count_of)[1];
	$what = fread($fp, 20 * $count_of);
	print "count $count_of\n";
	for ($i=0; $i<$count_of; $i++) {
		print bin2hex(substr($what, $i*20, 20)) . "\n";
	}

	$what = fread($fp, 0xf * 4);

	$count_of = fread($fp, 4); // if >= 1000 then die  files_load_scn:253
	$count_of = unpack("V", $count_of)[1];
	print "count $count_of\n";

	print "Strings array: " . json_encode($arr1) . "\n";
	print "Palettes array: " . json_encode($arr2) . "\n";
	print "Exits array: " . json_encode($arr3) . "\n";
	print "Animatins array: " . json_encode($arr4) . "\n";
	print "SCA/SMC array: " . json_encode($arr5) . "\n";
	print "Themes array:" . json_encode($arr6) . "\n";
	print "Sound/speech array:" . json_encode($arr7) . "\n";

	//fseek($fp, 0xcc);
	//fseek($fp, 0x10ec+1);
	//fseek($fp, 0xe4, SEEK_SET);
	//fseek($fp, 0xd7b, 0);

	while (!feof($fp)) {

		$data = ($script_type == 1) ? fread($fp, 1) . "\x0\x0\x0" : fread($fp, 4);
		$count = unpack("V", $data)[1];

		print sprintf("\nCommands in this batch (%d):\n", $count);

		while ($count > 0) {
			$buf = fread($fp, 0x10);
			$args = unpack("V4", $buf);

			$opcode = $args[1];
			switch ($opcode) {

				case 0x1:
				print sprintf("\tani_add_by_num(%d)\n", $args[2]);
				break;

				case 0x2:
				print sprintf("\tnop\n");
				break;

				case 0x191:
				print sprintf("\tget_ani_by_slot(%d)\n", $args[2]);
				break;

				case 0x195:
				print sprintf("\tget_ani_by_slot(%d)\n", $args[2]);
				break;

				case 0x03:
				print sprintf("\t%s = 1\n", "DAT_007144d8");
				break;

				case 0x04:
				print sprintf("\tvar_%d = 0x%x\n", $args[2], $args[3]);
				break;

				case 0x09:
				print sprintf("\tif var_%d <= 0x%x\n", $args[2], $args[3]);
				break;

				case 0x0a:
				print sprintf("\tif var_%d != 0x%x\n", $args[2], $args[3]);
				break;

				case 0x0b:
				print sprintf("\tif var_%d >= 0x%x\n", $args[2], $args[3]);
				break;

				case 0x0f:
				// nop
				break;

				case -0x10:
				break;

				case 0x19:
				$str = $arr2[$args[2]];
				print sprintf("\tget_ani_slot_by_num(\"%s\") // %d\n", $str, $args[2]);
				break;

				case 0x4d:
				$x = ($args[2] < 1) ? 3000 : $args[2];
				print sprintf("\tthm_fadeout(%d)\n", $x);
				break;

				case 0x50:
				$str = $arr5[$args[2]];
				print sprintf("\tspeak(\"%s\")\n", $str);
				break;

				case -0x70:
				// break loop
				print sprintf("\tbreak\n");
				break;

				case 0x71:
				$str = $arr3[$args[2]];
				print sprintf("\tintro_play(\"%s\")\n", $str);
				break;

				case 0x77: // fallthrough
				case 0x78:
				$str = $arr5[$args[2]];
				print sprintf("\tscm_add(\"%s\")\n", $str);
				break;

				case 0xcd:
				$str = $arr5[$args[2]];
				print sprintf("\tnwspeak(\"%s\")\n", $str);
				break;
				
				case 0x100:
				// nop
				break;

				case 0x12d:
				print sprintf("\twait_frames\n");
				break;

				case 0x12e:
				print sprintf("\tpop txt_set_lines()\n");
				break;

				case 0x12f:
				print sprintf("\tpalette_save?\n");
				break;

				case 0x130:
				print sprintf("\tpalette_restore?\n");
				break;

				case 0x157:
				case 0x158:
				print sprintf("\t__debug(%d)\n", $args[2]);
				break;

				case 0x16c:
				$str = $arr4[$args[2]];
				print sprintf("\tthm_event(\"%s\")\n", $str);
				break;

				case 0x0178:
				print sprintf("\tsync_add_timer(%d, 0x%x)", $args[2], $args[3]);
				break;

				case 0x17a:
				print sprintf("\tstop all sound %x %d\n",  $args[2], $args[3]);
				break;

				default:
				print sprintf("Unsupported command 0x%x arg2=0x%x arg3=0x%x\n", $opcode, $args[2], $args[3]);
				break;
			}

			$count--;
		}
	}

	if (feof($fp)) {
		print "\neof\n";
	}

