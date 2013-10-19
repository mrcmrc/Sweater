<?php

namespace Silk;

abstract class Manager {
	
	public function __call($strFunction, $arrArguments){
		$strType = substr($strFunction, 0, 3);
		switch($strType){
			case 'get':
				$strProperty = substr($strFunction, 3);
				if(property_exists($this, 'int' . $strProperty)) return $this->{'int' . $strProperty};
				if(property_exists($this, 'str' . $strProperty)) return $this->{'str' . $strProperty};
				if(property_exists($this, 'arr' . $strProperty)) return $this->{'arr' . $strProperty};
				if(property_exists($this, 'bln' . $strProperty)) return $this->{'bln' . $strProperty};
			break;
			case 'set':
				$strProperty = substr($strFunction, 3);
				list($strValue) = $arrArguments;
				if(property_exists($this, 'int' . $strProperty)) return $this->{'int' . $strProperty} = $strValue;
				if(property_exists($this, 'str' . $strProperty)) return $this->{'str' . $strProperty} = $strValue;
				if(property_exists($this, 'arr' . $strProperty)) return $this->{'arr' . $strProperty} = $strValue;
				if(property_exists($this, 'bln' . $strProperty)) return $this->{'bln' . $strProperty} = $strValue;
			break;
		}
	}
	
}

?>
