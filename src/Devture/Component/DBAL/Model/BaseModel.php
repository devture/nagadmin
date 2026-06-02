<?php
namespace Devture\Component\DBAL\Model;

abstract class BaseModel {

	private $record = array();

	public function __construct(array $record = array()) {
		$this->record = $record;
	}

	protected function getAttribute($key, $defaultValue = null) {
		return (isset($this->record[$key]) || array_key_exists($key, $this->record) ? $this->record[$key] : $defaultValue);
	}

	protected function setAttribute($key, $value) {
		if (is_string($value)) {
			$value = $this->sanitizeString($value);
		}

		$this->record[$key] = $value;
	}

	/**
	 * Ensures a string is valid, storable UTF-8.
	 *
	 * Drops invalid byte sequences, strips the BOM and invisible/control
	 * characters (C0/C1 ranges + DEL, keeping tab/newline/carriage-return),
	 * while preserving all printable Unicode (including non-breaking spaces).
	 *
	 * Replaces the former voku/portable-utf8 `UTF8::cleanup()` call; the
	 * library's ISO<->UTF-8 mojibake remapping is intentionally not carried
	 * over (input already arrives as UTF-8 and the heuristic can corrupt
	 * legitimately-encoded data).
	 */
	private function sanitizeString($value) {
		$previousSubstituteCharacter = mb_substitute_character();
		mb_substitute_character('none');
		$value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
		mb_substitute_character($previousSubstituteCharacter);

		$value = str_replace("\xEF\xBB\xBF", '', $value);

		return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{0080}-\x{009F}]/u', '', $value);
	}

	public function export() {
		return $this->record;
	}

	public function getId() {
		return $this->getAttribute('_id', null);
	}

	public function setId($value) {
		$this->setAttribute('_id', $value);
	}

}
