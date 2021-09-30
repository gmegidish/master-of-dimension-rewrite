<?

	//$fp = fopen("4.VE", "rb"); fseek($fp, 0xa870);
	//$fp = fopen("4.INTRO", "rb"); fseek($fp, 0x8a);
	//$fp = fopen("4.OPTIONS", "rb"); fseek($fp, 0x10ec+1);
	$fp = fopen("4.THEEND", "rb"); 

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
	$header = fread($fp, 12);

	$arr1 = read_array_of_strings($fp);
	$arr2 = read_array_of_strings($fp);
	$arr3 = read_array_of_strings($fp);
	$arr4 = read_array_of_strings($fp);
	$arr5 = read_array_of_strings($fp);

	print "First array: " . json_encode($arr1) . "\n";
	print "Second array: " . json_encode($arr2) . "\n";
	print "Third array: " . json_encode($arr3) . "\n";
	print "Fourth array: " . json_encode($arr4) . "\n";
	print "Fifth array: " . json_encode($arr5) . "\n";

	fseek($fp, 0xcc);

	while (!feof($fp)) {

		$count = ord(fread($fp, 1));
		print sprintf("\nCommands in this batch (%d):\n", $count);

		while ($count > 0) {
			$buf = fread($fp, 0x10);
			$args = unpack("V4", $buf);

			$opcode = $args[1];
			switch ($opcode) {

				case 0x03:
				print sprintf("\t%s = 1\n", "DAT_007144d8");
				break;

				case 0x04:
				print sprintf("\tset_variable(0x%x, 0x%x);\n", $args[2], $args[3]);
				break;

				case 0x0a:
				print sprintf("\tisVarNotEqual(0x%x, 0x%x)\n", $args[2], $args[3]);
				break;

				case 0x0f:
				// nop
				break;

				case -0x10:
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

				case 0xcd:
				print sprintf("\t0xcb\n");
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

