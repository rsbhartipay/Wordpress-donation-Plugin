<?php
class PayDonation {


    static function getHashFromArray($arrayList, $key) {
	ksort($arrayList);
     $all = '';
     $salt=trim(get_option('bhartipay_merchant_key'));
     $postdata=$arrayList;
        foreach ($postdata as $name => $value) {
            $all .= $name."=".$value."~";
        }
        
        $all = substr($all, 0, -1);
        $all .= $salt;
         return strtoupper(hash('sha256', $all));
    }




	static function redirect2PG($paramList, $key) {
		$hashString = self::getHashFromArray($paramList);
		//print_r($hashString);exit;
		$checksum   = self::encrypt_e($hashString, $key);
	}





	static function sanitizedParam($param) {
		$pattern[0]     = "%,%";
		$pattern[1]     = "%#%";
		$pattern[2]     = "%\(%";
		$pattern[3]     = "%\)%";
		$pattern[4]     = "%\{%";
		$pattern[5]     = "%\}%";
		$pattern[6]     = "%<%";
		$pattern[7]     = "%>%";
		$pattern[8]     = "%`%";
		$pattern[9]     = "%!%";
		$pattern[10]    = "%\\$%";
		$pattern[11]    = "%\%%";
		$pattern[12]    = "%\^%";
		$pattern[13]    = "%=%";
		$pattern[14]    = "%\+%";
		$pattern[15]    = "%\|%";
		$pattern[16]    = "%\\\%";
		$pattern[17]    = "%:%";
		$pattern[18]    = "%'%";
		$pattern[19]    = "%\"%";
		$pattern[20]    = "%;%";
		$pattern[21]    = "%~%";
		$pattern[22]    = "%\[%";
		$pattern[23]    = "%\]%";
		$pattern[24]    = "%\*%";
		$pattern[25]    = "%&%";
		$sanitizedParam = preg_replace($pattern, "", $param);
		return $sanitizedParam;
	}

	
}