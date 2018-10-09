<?php
/**
 * Class for working with MO files
 *
 * @version $Id: mo.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage mo
 */

require_once dirname(__FILE__) . '/translations.php';
require_once dirname(__FILE__) . '/streams.php';

if ( ! class_exists( 'MO', false ) ):
class MO extends Gettext_Translations {
	// refactored. var $_nplurals = 2;
	// refactored. private $filename = '';

	/**
	 * Returns the loaded MO file.
	 *
	 * @return string The loaded MO file.
	 */
	public function get_filename() {
		return $this->filename;
	}

	// refactored. function import_from_file($filename) {}

	/**
	 * @param string $filename
	 * @return bool
	 */
	function export_to_file($filename) {
		$fh = fopen($filename, 'wb');
		if ( !$fh ) return false;
		$res = $this->export_to_file_handle( $fh );
		fclose($fh);
		return $res;
	}

	/**
	 * @return string|false
	 */
	function export() {
		$tmp_fh = fopen("php://temp", 'r+');
		if ( !$tmp_fh ) return false;
		$this->export_to_file_handle( $tmp_fh );
		rewind( $tmp_fh );
		return stream_get_contents( $tmp_fh );
	}

	/**
	 * @param Translation_Entry $entry
	 * @return bool
	 */
	function is_entry_good_for_export( $entry ) {
		if ( empty( $entry->translations ) ) {
			return false;
		}

		if ( !array_filter( $entry->translations ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param resource $fh
	 * @return true
	 */
	function export_to_file_handle($fh) {
		$entries = array_filter( $this->entries, array( $this, 'is_entry_good_for_export' ) );
		ksort($entries);
		$magic = 0x950412de;
		$revision = 0;
		$total = count($entries) + 1; // all the headers are one entry
		$originals_lenghts_addr = 28;
		$translations_lenghts_addr = $originals_lenghts_addr + 8 * $total;
		$size_of_hash = 0;
		$hash_addr = $translations_lenghts_addr + 8 * $total;
		$current_addr = $hash_addr;
		fwrite($fh, pack('V*', $magic, $revision, $total, $originals_lenghts_addr,
			$translations_lenghts_addr, $size_of_hash, $hash_addr));
		fseek($fh, $originals_lenghts_addr);

		// headers' msgid is an empty string
		fwrite($fh, pack('VV', 0, $current_addr));
		$current_addr++;
		$originals_table = chr(0);

		$reader = new POMO_Reader();

		foreach($entries as $entry) {
			$originals_table .= $this->export_original($entry) . chr(0);
			$length = $reader->strlen($this->export_original($entry));
			fwrite($fh, pack('VV', $length, $current_addr));
			$current_addr += $length + 1; // account for the NULL byte after
		}

		$exported_headers = $this->export_headers();
		fwrite($fh, pack('VV', $reader->strlen($exported_headers), $current_addr));
		$current_addr += strlen($exported_headers) + 1;
		$translations_table = $exported_headers . chr(0);

		foreach($entries as $entry) {
			$translations_table .= $this->export_translations($entry) . chr(0);
			$length = $reader->strlen($this->export_translations($entry));
			fwrite($fh, pack('VV', $length, $current_addr));
			$current_addr += $length + 1;
		}

		fwrite($fh, $originals_table);
		fwrite($fh, $translations_table);
		return true;
	}

	/**
	 * @param Translation_Entry $entry
	 * @return string
	 */
	function export_original($entry) {
		//TODO: warnings for control characters
		$exported = $entry->singular;
		if ($entry->is_plural) $exported .= chr(0).$entry->plural;
		if ($entry->context) $exported = $entry->context . chr(4) . $exported;
		return $exported;
	}

	/**
	 * @param Translation_Entry $entry
	 * @return string
	 */
	function export_translations($entry) {
		//TODO: warnings for control characters
		return $entry->is_plural ? implode(chr(0), $entry->translations) : $entry->translations[0];
	}

	/**
	 * @return string
	 */
	function export_headers() {
		$exported = '';
		foreach($this->headers as $header => $value) {
			$exported.= "$header: $value\n";
		}
		return $exported;
	}

	// refactored. function get_byteorder($magic) {}
	// refactored. function import_from_reader($reader) {}
	// refactored. function &make_entry($original, $translation) {}

	/**
	 * @param int $count
	 * @return string
	 */
	function select_plural_form($count) {
		return $this->gettext_select_plural_form($count);
	}

	/**
	 * @return int
	 */
	function get_plural_forms_count() {
		return $this->_nplurals;
	}
}
endif;
