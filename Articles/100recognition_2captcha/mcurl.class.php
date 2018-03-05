<?php
class mcurl {

    var $timeout = 20; // page loading timeout  
    var $threads = 10; // number of threads  

    var $all_useragents = array(  
    "Opera/9.23 (Windows NT 5.1; U; ru)",  
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.8) Gecko/20071008 Firefox/2.0.0.4;MEGAUPLOAD 1.0",  
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; Alexa Toolbar; MEGAUPLOAD 2.0; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7;MEGAUPLOAD 1.0",  
    "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; MyIE2; Maxthon)",  
    "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; MyIE2; Maxthon)",  
    "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; MyIE2; Maxthon)",  
    "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; Maxthon; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; Media Center PC 5.0; InfoPath.1)",  
    "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; MyIE2; Maxthon)",  
    "Opera/9.10 (Windows NT 5.1; U; ru)",  
    "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1; aggregator:Tailrank; http://tailrank.com/robot) Gecko/20021130",  
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.8) Gecko/20071008 Firefox/2.0.0.8",  
    "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; MyIE2; Maxthon)",  
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.8) Gecko/20071008 Firefox/2.0.0.8",  
    "Opera/9.22 (Windows NT 6.0; U; ru)",  
    "Opera/9.22 (Windows NT 6.0; U; ru)",  
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.8) Gecko/20071008 Firefox/2.0.0.8",  
    "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)",  
    "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; MRSPUTNIK 1, 8, 0, 17 HW; MRA 4.10 (build 01952); .NET CLR 1.1.4322; .NET CLR 2.0.50727)",  
    "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)",  
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9"  
    );  

    function multiget($urls, &$result)  
    {  
        $threads = $this->threads;  
        $useragent = $this->all_useragents[array_rand($this->all_useragents)];  

        $i = 0;  
        for($i=0;$i<count($urls);$i=$i+$threads)  
        {  
            $urls_pack[] = array_slice($urls, $i, $threads);  
        }  
        foreach($urls_pack as $pack)  
        {  
            $mh = curl_multi_init(); unset($conn);  
            foreach ($pack as $i => $url)  
            {  
                $conn[$i]=curl_init(trim($url));  
                curl_setopt($conn[$i],CURLOPT_RETURNTRANSFER, 1);  
                curl_setopt($conn[$i],CURLOPT_TIMEOUT, $this->timeout);  
                curl_setopt($conn[$i],CURLOPT_USERAGENT, $useragent);  
                curl_multi_add_handle ($mh,$conn[$i]);  
            }  
            do { $n=curl_multi_exec($mh,$active); usleep(100); } while ($active);  
            foreach ($pack as $i => $url)  
            {  
                $result[]=curl_multi_getcontent($conn[$i]);  
                curl_close($conn[$i]);  
            }  
            curl_multi_close($mh);  
        }  

    } 
    
    function multipost($urls, $post = array(), &$result)  
    {  
        $threads = $this->threads;  
        $useragent = $this->all_useragents[array_rand($this->all_useragents)];  

        $mh = curl_multi_init(); unset($conn);  
        foreach ($urls as $i => $url)  
        {  
            $conn[$i]=curl_init(trim($url));  
            curl_setopt($conn[$i],CURLOPT_RETURNTRANSFER, 1);  
            curl_setopt($conn[$i],CURLOPT_TIMEOUT, $this->timeout);  
            curl_setopt($conn[$i],CURLOPT_USERAGENT, $useragent);  
            if(!empty($post[$i])){
                curl_setopt($conn[$i], CURLOPT_POST, true);
                curl_setopt($conn[$i], CURLOPT_POSTFIELDS, http_build_query($post[$i]));                
            }
            curl_multi_add_handle ($mh,$conn[$i]);  
        }  
        do { $n=curl_multi_exec($mh,$active); usleep(100); } while ($active);  
        foreach ($urls as $i => $url)  
        {  
            $result[$i]=curl_multi_getcontent($conn[$i]);  
            curl_close($conn[$i]);  
        }  
        curl_multi_close($mh);  
    }    
}
?>
