<?php
/**
* @author Ivan Pavlov [ivan dot pavlov at gmail dot com]
*/
class BloomFilter {

	public $field; //length of the bitfield
	public $len;

	function myhash($key){
		return array(
			abs(hexdec(hash('crc32','m'.$key.'a'))%$this->len),
			abs(hexdec(hash('crc32','p'.$key.'b'))%$this->len),
			abs(hexdec(hash('crc32','t'.$key.'c'))%$this->len)
			);
	}

	function __construct($len){
		$this->len = $len;
		$this->field = bitset_empty($this->len);
	}

	static function init($field){
		$bf = new self(strlen(bitset_to_string($field)));
		$bf->field = $field;
		return $bf;
	}

	function add($key){
		foreach ($this->myhash($key) as $h)  bitset_incl($this->field,$h);
	}

	function has($key){
		foreach ($this->myhash($key) as $h) if (!bitset_in($this->field,$h)) return false;
		return true;
	}

	/**
	* Reports the false positive rate of the current bloom filter
	* @param  int $numItems number of items inserted in the bloom filter
	*/
	function falsePositiveRate($numItems){
		$k = count($this->myhash('1'));
		return pow((1-pow((1-1/$this->len),$k*$numItems)),$k);
	}

}

?>