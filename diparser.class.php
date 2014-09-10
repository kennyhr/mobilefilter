<?php
//load,getSection,has,info
/**
 * 手机号段文件分析类
 * @version:1.0
 * @author:Kenny{Kenny.F<mailto:kennyffly@gmail.com>}
 * @since:2014/04/23
 */
require('bloomfilter.class.php');

ini_set('memory_limit', '-1');

class DIParser {

	public $bf_bit_array = array();
	public $bf_info_array = array();
	public $bf;
	public $section;
	private $_bit_length = 9000000;
	private $_cached = False;
	private $_setinfo = False;
	private $_add_key_arr = array();
	private $_secinfo_data = array();
	private $_section = NULL;

	/**
	 * 载入手机号段文件
	 * @param $file 同级目录下文件文件名
	 * @param $cached 利用文件缓存，load速度变慢，但是只用load一次不用反复load加载
	 * @param $setinfo 标记，设置是否载入号码相关信息
	 */
	public function load($file = '', $cached=False, $setinfo=False)
	{
		if (empty($file))
		{
			echo "file is null\n";
			return False;
		}

		if (!file_exists($file))
		{
			echo "file not exists\n";
			return False;
		}

		$this->_cached = $cached;
		$this->_setinfo = $setinfo;

		$db_data = $this->getFileLines($file, 1, 0);
		if(empty($db_data))
		{
			echo "file can not be read\n";
			return False;
		} else{
			return True;
		}

	}

	/**
	 * 设置搜索区域
	 * @param $section 区域标记，e.g. beijingshi
	 */
	public function getSection($section = '')
	{
		if(empty($section))
		{
			echo "section can not be null\n";
			return False;
		}
		$this->section = $section;

		if (isset($this->bf_bit_array[$section]) && !empty($this->bf_bit_array[$section]))
		{
			$data = $this->bf_bit_array[$section];
		} else{
			$data_str = file_get_contents('./data/'.$section);
			if(empty($data))
			{
				echo "load section data faild\n";
				return False;
			} else{
				$data=unserialize($data_str);
			}
		}

		$this->bf = BloomFilter::init($data,$this->_bit_length);
		return $this;
	}

	/**
	 * 判断指定号段是否存在
	 * @param $target 目标号码，e.g. 1300013
	 * @return boolean
	 */
	public function has($target = '')
	{
		if(empty($this->bf))
		{
			echo "section set faild";
			return False;
		}

		if ($this->bf->has($target))
		{
			return True;
		} else{
			return False;
		}

	}

	/**
	 * 获取指定号段所在区域信息
	 * @param $target 目标号码，e.g. 1300013
	 * @return array
	 */
	public function info($target = '')
	{
		if(empty($this->bf) || empty($this->section))
		{
			echo "section set faild";
			return False;
		}

		if ($this->bf->has($target))
		{
			if (!empty($this->bf_info_array) && isset($this->bf_info_array[$target]))
			{
				$section_info_arr = $this->bf_info_array;
			} else{
				$fn = $this->section.'_info';
				$section_info_str = file_get_contents('./data/'.$fn);
				if(!empty($section_info_str))
				{
					$section_info_arr = unserialize($section_info_str);
				}else{
					echo "read section info cache faild\n";
					return False;
				}
			}

			$target_info = $section_info_arr[$target];
			return $target_info ? $target_info : False;

		} else{
			return False;
		}
	}


	//加载并处理数据文件
	private function _load($db_data='')
	{
		if(empty($db_data))
		{
			// echo "file contents empty\n";
			return False;
		}

		$value = trim($db_data);
		if (empty($value))
		{
			return False;
		}
		$vh = substr($value, 0, 1);
		if($vh == ';' || $vh == '#' || $vh == '\n')
		{
			//跳过注释行
			return False;
		}

		//get section
		preg_match("/^\[+\w+\]$/", $value, $section_temp_arr);
		if(!empty($section_temp_arr))
		{
			$this->_section = substr($section_temp_arr[0], 1, -1);
		}

		//get group
		$line_temp_arr = explode(" ", $value);
		if(!empty($line_temp_arr)){
			$group = trim($line_temp_arr[0]);
		} else{
			return False;
		}

		//get part
		if(!empty($line_temp_arr) && !empty($line_temp_arr[1]))
		{
			$part_temp_arr = explode(',', $line_temp_arr[1]);
			if($part_temp_arr)
			{
				foreach((array)$part_temp_arr as $val)
				{
					//$val:1019 or 1100-1129
					if (is_numeric($val))
					{
						$temp_key = $group . sprintf("%04d", $val);
						$this->_add_key_arr[$this->_section][] = $temp_key;
						if ($this->_setinfo)
						{
							$this->_secinfo_data[$this->_section][$temp_key] = array('section'=>$this->_section, 'line'=>$line+1, 'group'=>$group, 'part'=>$val);
						}
					} else{
						$add_key_temp_arr = explode('-', $val);
						for($i=$add_key_temp_arr[0]; $i<=$add_key_temp_arr[1]; ++$i)
						{
							$temp_key = $group . sprintf("%04d", $i);
							$this->_add_key_arr[$this->_section][] = $temp_key;
							if ($this->_setinfo)
							{
								$this->_secinfo_data[$this->_section][$temp_key] = array('section'=>$this->_section, 'line'=>$line+1, 'group'=>$group, 'part'=>$val);
							}
						}
					}
				}
			}
		}
		return True;
	}

	//save cache data
	private function _save()
	{
		//add bitset
		if(!empty($this->_add_key_arr) && is_array($this->_add_key_arr))
		{
			foreach((array)$this->_add_key_arr as $section=>$value)
			{
				$bf = new BloomFilter($this->_bit_length);
				foreach((array)$value as $key)
				{
					$bf->add($key);
				}
				if(!empty($bf->field))
				{
					$this->bf_bit_array[$section] = $bf->field;

					if ($this->_cached)
					{
						file_put_contents('./data/'.$section, serialize($bf->field));
					}

				}
			}

		}

		//set section info
		if($this->_setinfo && $this->_secinfo_data)
		{
			$this->bf_info_array = $this->_secinfo_data;

			if ($this->_cached)
			{
				foreach((array)$this->_secinfo_data as $section=>$secinfo)
				{
					file_put_contents('./data/'.$section.'_info', serialize($secinfo));
				}
			}


		}
	}

	//读文件
	private function getFileLines($filename, $startLine = 1, $endLine = 100, $method = 'r')
	{
		$content = array();
		$count = 0;

		if($endLine >= $startLine)
		{
			$count = $endLine - $startLine;
		}

		// SplFileObject need PHP>=5.1.0
		if(version_compare(PHP_VERSION, '5.1.0', '>='))
		{
			$fp = new SplFileObject($filename, $method);
			$fp->seek($startLine-1);// seek from 0
			$i = 0;
			while($fp->valid())
			{
				if(!empty($count) && $i > $count)
				{
					break;
				}
				$content = $fp->current();
				$this->_load($content);
				$fp->next();
				$i ++ ;
			}
			$this->_save();
		} else{
			echo "need PHP>=5.1.0\n";
			return False;
		}
		return True;
	}

}

?>