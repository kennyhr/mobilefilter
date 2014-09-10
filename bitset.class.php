<?php
define('CHAR_BIT', 8);
/**
 * bitset操作php实现
 * @version:1.0
 * @author:Kenny{Kenny.F<mailto:kennyffly@gmail.com>}
 * @since:2014/05/21
 */
class Bitset {

	private $bitset_data = array();
	private $_len = 0;

	//分配位数组空间
	function &bitset_empty($bit=0)
	{
		if(!is_numeric($bit) || $bit<0)
		{
			echo "argument must be a positive integer";
			return False;
		}
		$this->_len = $bit;
		return $this->bitset_data;
	}

	//位数组位置$bit上的值置为1
	public function bitset_incl(&$bitset_data=array(), $bit=0)
	{
		if (!is_numeric($bit) || $bit<0)
		{
			echo "Second argument must be a positive integer";
			return False;
		}

		$bitset_temp = isset($bitset_data[intval($bit/CHAR_BIT)]) ? $bitset_data[intval($bit/CHAR_BIT)] : 0;
		$bitset_data[intval($bit/CHAR_BIT)]  = $bitset_temp | 1 << ($bit % CHAR_BIT);

		unset($bitset_data);
	}

	//判断某个位置bit上的值是否为1
	public function bitset_in($bitset_data=array(), $bit=0)
	{
		if (!is_array($bitset_data))
		{
			echo "first argument is not a array";
			return False;
		}

		if ($bit < 0)
		{
			return False;
		}
		if ($this->_len == 0)
		{
			return False;
		} elseif($bit >= $this->_len*CHAR_BIT){
			return False;
		} elseif ($bitset_data[intval($bit/CHAR_BIT)] & (1 << ($bit % CHAR_BIT))){
			return True;
		} else{
			return False;
		}
	}

}