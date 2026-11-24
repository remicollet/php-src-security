--TEST--
Bug #78793 backport: maker note entry past the data of a guessed offset (MN_OFFSET_GUESS)
--SKIPIF--
<?php if (!extension_loaded('exif')) print 'skip exif extension not available'; ?>
--FILE--
<?php
/* Canon maker note of 10 entries (122 bytes). Entry 0 holds 76 where the guessing code reads
 * the offset of the first value, so offset_diff is 50 and only 72 bytes of data remain:
 * entries 6 to 9 start past them. Entry 9 asks for 64 bytes at 120 from the guessed base,
 * past the end of the maker note. */
$mn = pack('v', 10);
for ($i = 0; $i < 9; $i++) {
	$mn .= pack('vvVV', 0x100 + $i, 3, 1, $i ? 0 : 76);
}
$mn .= pack('vvVV', 0x109, 2, 64, 120);

$tiff = "II*\0" . pack('V', 8)
	. pack('v', 2) . pack('vvVV', 0x010F, 2, 6, 38) . pack('vvVV', 0x8769, 4, 1, 44) . pack('V', 0)
	. "Canon\0"
	. pack('v', 1) . pack('vvVV', 0x927C, 7, strlen($mn), 62) . pack('V', 0)
	. $mn;
$file = __DIR__ . '/bug78793_guess.tiff';
file_put_contents($file, $tiff);

$data = @exif_read_data($file, 'MAKERNOTE', true);
var_dump(isset($data['MAKERNOTE']['UndefinedTag:0x0100']));
var_dump(isset($data['MAKERNOTE']['UndefinedTag:0x0109']));
?>
--CLEAN--
<?php @unlink(__DIR__ . '/bug78793_guess.tiff'); ?>
--EXPECT--
bool(true)
bool(false)
