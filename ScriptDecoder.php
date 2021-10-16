<?php

	class ScriptDecoder
	{
		/** @var FileReader */
		private $fp;

		private $scriptType;
		private $areas;
		private $cursors;

		public function __construct(FileReader $fp)
		{
			$this->fp = $fp;
		}

		private $stringsTable;
		private $palettesTable;
		private $exitsTable;
		private $animationsTable;
		private $smcTable;
		private $themesTable;
		private $soundsTable;
		private $constants;

		public function read(): ScriptDecoder
		{
			$this->scriptType = $this->fp->readLong();
			print "Script type: " . $this->scriptType . "\n";

			$this->constants = $this->readConsts(fopen("22.MENU", "rb"));

			$this->stringsTable = $this->readArrayOfStrings();
			$this->palettesTable = $this->readArrayOfStrings();
			$this->exitsTable = $this->readArrayOfStrings();
			$this->animationsTable = $this->readArrayOfStrings();
			$this->smcTable = $this->readArrayOfStrings();
			$this->themesTable = $this->readArrayOfStrings();
			$this->soundsTable = $this->readArrayOfStrings();

			$this->cursors = $this->loadCursors();
			$this->areas = $this->loadAreas();

			$what = $this->fp->read(0xf * 4);

			print "Strings array: " . json_encode($this->stringsTable) . "\n";
			print "Palettes array: " . json_encode($this->palettesTable) . "\n";
			print "Exits array: " . json_encode($this->exitsTable) . "\n";
			print "Animations array: " . json_encode($this->animationsTable) . "\n";
			print "SCA/SMC array: " . json_encode($this->smcTable) . "\n";
			print "Themes array:" . json_encode($this->themesTable) . "\n";
			print "Sound/speech array:" . json_encode($this->soundsTable) . "\n";

			$this->readScripts();

			return $this;
		}

		public function getCursors(): array
		{
			return $this->cursors;
		}

		public function getAreas(): array
		{
			return $this->areas;
		}

		private function readArrayOfStrings(): array
		{
			$strings = [];

			$count = $this->fp->readLong();
			for ($i = 0; $i < $count; $i++) {
				$strings[] = $this->fp->readPascalString();
			}

			return $strings;
		}

		private function readConsts($fp): array
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

		private function loadCursors(): array
		{
			$count = $this->fp->readLong();
			$cursors = [];
			for ($i = 0; $i < $count; $i++) {
				$block = $this->fp->read(176);
				$cursor = unpack("l*", $block);
				$cursors[] = array_values($cursor);
			}

			return $cursors;
		}

		private function loadAreas(): array
		{
			$areas = [];
			$n_areas = $this->fp->readLong(); // if >= 1000 then die  files_load_scn:253
			print "Number of areas: $n_areas\n";
			if ($n_areas > 0) {
				for ($i = 0; $i < $n_areas; $i++) {
					$area = new stdClass();
					$area->x0 = $this->fp->readLong();
					$area->y0 = $this->fp->readLong();
					$area->x1 = $this->fp->readLong();
					$area->y1 = $this->fp->readLong();
					$area->dunno = $this->fp->readLong();
					print "Area $i: " . json_encode($area) . "\n";
					$areas[] = $area;
				}

				print "\n";
			}

			return $areas;
		}

		public function getScriptType(): int
		{
			return $this->scriptType;
		}

		private function readScripts()
		{
			$count_of_scripts = $this->fp->readLong();
			assert($count_of_scripts <= 1000);

			$strings = $this->constants;

			for ($script_index = 0; $script_index < $count_of_scripts; $script_index++) {

				$count = ($this->getScriptType() == 1) ? $this->fp->readByte() : $this->fp->readLong();

				print sprintf("\n${script_index}: Commands in this batch (%d): /* %s */\n", $count, $strings[$script_index]);

				$indent = 0;

				while ($count > 0) {
					$buf = $this->fp->read(16);
					$args = unpack("V4", $buf);

					if ($indent > 0) {
						print str_repeat("\t", $indent);
					}

					$opcode = $args[1];
					switch ($opcode) {

						case 0x1:
							print sprintf("\tani_add_by_num(%d)\n", $args[2]);
							break;

						case 0x2:
							print sprintf("\tnop\n");
							break;

						case 0x03:
							print sprintf("\t%s = 1\n", "DAT_007144d8");
							break;

						case 0x04:
							print sprintf("\tvar_%d = 0x%x\n", $args[2], $args[3]);
							break;

						case 0x7:
							print sprintf("\twm_recalc_curs(%d)\n", $args[2]);
							break;

						case 0x08:
							print sprintf("\twm_recalc_curs\n");
							break;

						case 0x09:
							print sprintf("\tif var_%d <= 0x%x {\n", $args[2], $args[3]);
							$indent++;
							break;

						case 0x0a:
							print sprintf("\tif var_%d != 0x%x {\n", $args[2], $args[3]);
							$indent++;
							break;

						case 0x0b:
							print sprintf("\tif var_%d >= 0x%x {\n", $args[2], $args[3]);
							$indent++;
							break;

						case 0x0f:
							$indent--;
							print sprintf("}\n");
							break;

						case 0x10:
							print sprintf("\tcontinue?\n");
							break;

						case 0x13:
							$str = $this->animationsTable[$args[2]];
							print sprintf("\tremove_ani?(\"%s\") // %d\n", $str, $args[2]);
							break;

						case 0x19:
							$str = $this->animationsTable[$args[2]];
							print sprintf("\tget_ani_slot_by_num(\"%s\") // %d\n", $str, $args[2]);
							break;

						case 0x3b:
							$str = $strings[$args[2]];
							print sprintf("\tswitch_to_script(\"%s\") // %d\n", $str, $args[2]);
							break;

						case 0x3c:
							print sprintf("\tjmp to %d\n", $args[2]);
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
							$str = $this->smcTable[$args[2]];
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
							$str = $this->smcTable[$args[2]];
							print sprintf("\tintro_play(\"%s\")\n", $str);
							break;

						case 0x77: // fallthrough
						case 0x78:
							$str = $this->smcTable[$args[2]];
							print sprintf("\tscm_add(\"%s\")\n", $str);
							break;

						case 0xcd:
							$str = $this->soundsTable[$args[2]];
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
							print sprintf("\tprintf(\"%s\") // %d\n", $this->stringsTable[$args[2]], $args[2]);
							break;

						case 0x158:
							print sprintf("\tnop\n");
							break;

						case 0x159:
							print sprintf("\tani_set_script(%d, %d)\n", $args[2], $args[3]);
							break;

						case 0x15b:
							print sprintf("\tvar_%d += 0x%x\n", $args[2], $args[3]);
							break;

						case 0x15c:
							print sprintf("\tvar_%d -= 0x%x\n", $args[2], $args[3]);
							break;

						case 0x15e:
							print sprintf("\tarray_clear()\n");
							break;

						case 0x15f:
							print sprintf("\tarray_start()\n");
							break;

						case 0x160:
							print sprintf("\tarray_end()\n");
							break;

						case 0x161:
							print sprintf("\tvar_%d = array_next()\n", $args[2]);
							break;

						case 0x162:
							print sprintf("\tvar_%d = array_prev()\n", $args[2]);
							break;

						case 0x163:
							print sprintf("\tvar_%d = array_get()\n", $args[2]);
							break;

						case 0x164:
							print sprintf("\tarray_set(var_%d)\n", $args[2]);
							break;

						case 0x165:
							print sprintf("\tarray_add()\n", $args[2]);
							break;

						case 0x16c:
							$str = $this->themesTable[$args[2]];
							print sprintf("\tthm_event(\"%s\")\n", $str);
							break;

						case 0x0178:
							print sprintf("\tsync_add_timer(%d, 0x%x)", $args[2], $args[3]);
							break;

						case 0x17a:
							print sprintf("\tstop all sound %x %d\n", $args[2], $args[3]);
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
							$str = $this->animationsTable[$args[2]];
							print sprintf("\tani_add_by_num(num=%d /* %s */, type=1, read_delay=0)\n", $args[2], $str);
							print sprintf("\t+ ani_set_priority(%d)\n", $args[3]);
							print sprintf("\t+ ani_set_advance(0)\n");
							print sprintf("\t+ ani_set_frame(0)\n");
							print sprintf("\t+ ani_suspend()\n");
							break;

						default:
							print sprintf("Unsupported command 0x%x arg2=0x%x arg3=0x%x\n", $opcode, $args[2], $args[3]);
							break;
					}

					$count--;
				}
			}
		}
	}
