<?

	//$fp = fopen("4.VE", "rb"); fseek($fp, 0xa870);
	//$fp = fopen("4.OPTIONS", "rb");
	//$fp = fopen("4.INTRO", "rb");
	$fp = fopen("4.MENU", "rb");
	$fp = fopen("4.VVI2", "rb");
	//$fp = fopen("4.VVB", "rb");
	//$fp = fopen("4.ENTRY", "rb");
	//$fp = fopen("4.OPTION", "rb");
	//$fp = fopen("4.INTER", "rb");
	//$fp = fopen("4.THEEND", "rb"); 
	//$fp = fopen("4.MAPMUSIC", "rb");
	//$fp = fopen("4.VVK3", "rb");
	//$fp = fopen("4.VVK", "rb");

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

	function read_consts($fp): array
	{
		$strings = [];

		while (!feof($fp)) {
			$bytes = fread($fp, 4);
			if (strlen($bytes) < 4) {
				break;
			}

			$length = unpack("V", $bytes)[1];
			$str = $length > 0 ? fread($fp, $length) : "";
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

	$fp2 = fopen("22.MENU", "rb");
	$fp2 = fopen("22.VVI2", "rb");
	//$fp2 = fopen("22.ENTRY", "rb");
	$strings = read_consts($fp2);
	print_r($strings);

	$count_of = fread($fp, 4); // if >= 0x96 then die files_load_scn:
	$count_of = unpack("V", $count_of)[1];
	print "count of cursors $count_of\n";
	if  ($count_of > 0) {
		$what = fread($fp, 176 * $count_of);

		for ($i=0; $i<$count_of; $i++) {
			print "$i: ";
			for ($j=0; $j < 176/4; $j++) {
				$c = unpack("V", substr($what, $i*176 + $j*4, 4))[1];
				print sprintf("%x ", $c);
				//if ((($j+1)%8) == 0) print "\n";
			}

			print "\n";
		}
	}

	$n_areas = fread($fp, 4); // if >= 1000 then die  files_load_scn:253
	$n_areas = unpack("V", $n_areas)[1];
	print "Number of areas: $n_areas\n";
	if ($n_areas > 0) {
		$areas = fread($fp, 20 * $n_areas);
		for ($i=0; $i<$n_areas; $i++) {
			$area = substr($areas, $i *20, 20);
			$points = unpack("V5", $area);
			print "Area $i: " . json_encode(array_values($points)) . "\n";
			//print bin2hex(substr($areas, $i*20, 20)) . "\n";
		}

		print "\n";
	}

	$what = fread($fp, 0xf * 4);
	// print_r(bin2hex($what)); exit;

	$count_of = fread($fp, 4); // if >= 1000 then die  files_load_scn:253
	$count_of_scripts = unpack("V", $count_of)[1];

	print "Strings array: " . json_encode($arr1) . "\n";
	print "Palettes array: " . json_encode($arr2) . "\n";
	print "Exits array: " . json_encode($arr3) . "\n";
	print "Animations array: " . json_encode($arr4) . "\n";
	print "SCA/SMC array: " . json_encode($arr5) . "\n";
	print "Themes array:" . json_encode($arr6) . "\n";
	print "Sound/speech array:" . json_encode($arr7) . "\n";

	for ($script_index=0; $script_index < $count_of_scripts; $script_index++) {

		$data = ($script_type == 1) ? fread($fp, 1) . "\x0\x0\x0" : fread($fp, 4);
		$count = unpack("V", $data)[1];

		print sprintf("\n${script_index}: Commands in this batch (%d): /* %s */\n", $count, $strings[$script_index]);

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

				case 0x7:
				print sprintf("\twm_recalc_curs(%d)\n", $args[2]);
				break;

				case 0x0f:
				print sprintf("\tendif\n");
				break;

				case 0x10:
				print sprintf("\tcontinue?\n");
				break;

				case 0x03:
				print sprintf("\t%s = 1\n", "DAT_007144d8");
				break;

				case 0x04:
				print sprintf("\tvar_%d = 0x%x\n", $args[2], $args[3]);
				break;

				case 0x08:
				print sprintf("\twm_recalc_curs\n");
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

				case 0x13:
				$str = $arr4[$args[2]];
				print sprintf("\tremove_ani?(\"%s\") // %d\n", $str, $args[2]);
				break;

				case 0x19:
				$str = $arr4[$args[2]];
				print sprintf("\tget_ani_slot_by_num(\"%s\") // %d\n", $str, $args[2]);
				break;

				case 0x3b:
				$str = $strings[$args[2]];
				print sprintf("\tswitch_to_script(\"%s\") // %d\n", $str, $args[2]);
				break;

				case -0x3c:
				$str = $arr4[$args[2]];
				print sprintf("\tani_add_by_num(num=%d /* %s */, type=0, read_delay=0) 3c\n", $args[2], $str);
				break;

				case 0x49:
				print sprintf("\twait_frames_no_async(1)\n");
				break;

				case 0x13c:
				print sprintf("\tfreeze_frame?(%d)\n", $args[2]);
				break;

				case 0x4d:
				$x = ($args[2] < 1) ? 3000 : $args[2];
				print sprintf("\tthm_fadeout(%d)\n", $x);
				break;

				case 0x50:
				$str = $arr5[$args[2]];
				print sprintf("\tspeak(\"%s\")\n", $str);
				break;

				case 0x65:
				print sprintf("\trun_prog(%d)\n", $args[2]);
				break;

				case -0x70:
				// break loop
				print sprintf("\tbreak\n");
				break;

				case 0x71:
				$str = $arr5[$args[2]];
				print sprintf("\tintro_play(\"%s\")\n", $str);
				break;

				case 0x77: // fallthrough
				case 0x78:
				$str = $arr5[$args[2]];
				print sprintf("\tscm_add(\"%s\")\n", $str);
				break;

				case 0xcd:
				$str = $arr7[$args[2]];
				print sprintf("\tnwspeak(\"%s\") /* %d */\n", $str, $args[2]);
				break;
				
				case 0xff:
				case 0x100:
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
				print sprintf("\tprintf(\"%s\") // %d\n", $arr1[$args[2]], $args[2]);
				break;

				case 0x158:
				print sprintf("\tnop\n");
				break;

				case 0x16c:
				$str = $arr6[$args[2]];
				print sprintf("\tthm_event(\"%s\")\n", $str);
				break;

				case 0x0178:
				print sprintf("\tsync_add_timer(%d, 0x%x)", $args[2], $args[3]);
				break;

				case 0x17a:
				print sprintf("\tstop all sound %x %d\n",  $args[2], $args[3]);
				break;

				case 0x191:
				print sprintf("\tani_suspend(get_ani_slot_by_num(%d))\n", $args[2]);
				break;

				case 0x195:
				print sprintf("\tani_clear_suspended(get_ani_slot_by_num(%d))\n", $args[2]);
				break;

				case 0x196:
				print sprintf("\tasync_add_timer(%d, %d)\n", $args[3], $args[2]);
				break;

				case 0x204:
				print sprintf("\tpal_smooth_fade_toblack(%d)\n", $args[2]);
				break;

				case 0x2c0:
				print sprintf("\tDAT_004c5c48 = 5\n");
				break;

				case 0x84c:
				print sprintf("\tvar_%d = si_get_vol()\n", $args[2]);
				break;

				case 0x850:
				print sprintf("\tvar_%d = txt_get_speed()\n", $args[2]);
				break;

				case 0x852:
				print sprintf("\ttxt_set_on(var_%d)\n", $args[2]);
				break;

				case 0x855:
				print sprintf("\tvar_%d = thm_get_on()\n", $args[2]);
				break;

				case 0x856:
				print sprintf("\tvar_%d = txt_get_on()\n", $args[2]);
				break;

				case 0x857:
				print sprintf("\tvar_%d = (_DAT_0062b284 == 0)\n", $args[2]);
				break;

				case 0x858:
				print sprintf("\tvar_%d = pal_get_brightness()\n", $args[2]);
				break;

/*
        case 0x2bd:
          program_memory[(int)arg_1] = program_memory[(int)arg_1] / (int)arg_2;
          iVar1 = INT_0070be90;
          break;
        case 0x2be:
          program_memory[(int)arg_1] = program_memory[(int)arg_1] * arg_2;
          iVar1 = INT_0070be90;
          break;
        case 0x2bf:
          program_memory[(int)arg_1] = program_memory[(int)arg_1] % (int)arg_2;
          iVar1 = INT_0070be90;
          break;
*/

				case 0x901:
				print sprintf("\tgv_addbutton(%d, 0)\n", $args[2]);
				break;

				case 0x902:
				print sprintf("\tgv_update_buttons()\n");
				break;

				case 0x903:
				print sprintf("\tgv_addbutton(-1, %d)\n", $args[3]);
				break;

				case 0x905:
				print sprintf("\tsav_select_load()\n");
				break;

				case 0x1004:
				print sprintf("\tinit_00468bb5()\n");
				break;

				case 0x1838:
				print sprintf("\tgran_diary_init()\n");
				break;

				case 0x13ba:
				$str = $arr4[$args[2]];
				print sprintf("\tani_add_by_num(num=%d /* %s */, type=1, read_delay=0)\n", $args[2], $str);
				print sprintf("\t+ ani_set_priority()\n");
				print sprintf("\t+ ani_set_advance()\n");
				print sprintf("\t+ ani_set_frame()\n");
				print sprintf("\t+ ani_suspend()\n");
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

