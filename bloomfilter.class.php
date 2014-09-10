<?php
require('bitset.class.php');
/**
 * 二进制位数组bitset过滤存储
 * @version:1.1
 * @author:Kenny{Kenny.F<mailto:kennyffly@gmail.com>}
 * @since:2014/05/21
 */
class BloomFilter {

	public $length = 0;
	public $field = '';
	private $_bitclass;

	public function __construct($len)
	{
		if (!is_numeric($len) || $len <= 0)
		{
			exit('diparser length must be a num > 0');
		}

		$this->_bitclass = new Bitset();
		$this->length = $len;
		$this->field = & $this->_bitclass->bitset_empty($this->length);//初始化位数组
	}

	static function init($field,$len)
	{
		$bf = new self($len);
		$bf->field = $field;
		return $bf;
	}

	//hash对应算法
	private function myhash($key)
	{
		return array(
			abs(hexdec(hash('crc32','m'.$key.'a'))%$this->length),
			abs(hexdec(hash('crc32','p'.$key.'b'))%$this->length),
			abs(hexdec(hash('crc32','t'.$key.'c'))%$this->length)
			);
	}

	//将字符串对应二进制位数组
	public function add($key)
	{
		foreach ((array)$this->myhash($key) as $h)
		{
			$this->_bitclass->bitset_incl($this->field, $h);//存入二进制位数组
		}

		// echo "add finish\n";
	}

	//判断二进制位数组是否有指定的字符串
	public function has($key)
	{
		foreach ($this->myhash($key) as $h)
		{
			if (!$this->_bitclass->bitset_in($this->field, $h))
				return false;
		}
		return true;
	}

}