<?

	$fp = fopen("4.VE", "rb");
	fseek($fp, 0x0000a870);

	while (!feof($fp)) {

		$count = ord(fread($fp, 1));
		print sprintf("\nCommands in this batch (%d):\n", $count);

		while ($count > 0) {
			$buf = fread($fp, 0x10);
			$args = unpack("V4", $buf);

			$opcode = $args[1];
			switch ($opcode) {

				case 0x0004:
				print sprintf("\tsetVariable(%d, 0x%x);\n", $args[2], $args[3]);
				break;

				case 0x000a:
				print sprintf("\tisVarNotEqual(%d, 0x%x)\n", $args[2], $args[3]);
				break;

				case 0x0f:
				// nop
				break;

				case 0x70:
				// break loop
				print sprintf("\tbreak\n");
				break;

				case 0xcd:
				print sprintf("\t0xcb\n");
				break;
				
				case 0x100:
				// nop
				break;

				case 0x157:
				case 0x158:
				print sprintf("\t__debug(%d)\n", $args[2]);
				break;

				case 0x0178:
				print sprintf("\taddTimer(%d, %d)", $args[2], $args[3]);
				break;

				case 0x17a:
				print sprintf("\t0x17a\n");
				break;

				default:
				print sprintf("Unsupported command 0x%x\n", $opcode);
				exit;
			}

			$count--;
		}
	}

	if (feof($fp)) {
		print "\neof\n";
	}

