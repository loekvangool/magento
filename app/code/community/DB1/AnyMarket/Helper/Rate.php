<?php

class DB1_AnyMarket_Helper_Rate extends DB1_AnyMarket_Helper_Data
{

    /**
     * @return bool
     */
    public function applyRateLimit(){
        $paraCache = "rateLimitCallCurl";

        $cache = Mage::app()->getCacheInstance();
        $rateLimitCallCurl = $cache->load($paraCache);
        $currDate = date('Y-m-d H:i:s.').gettimeofday()['usec'];
        if (false === $rateLimitCallCurl) {
            $qtyCalled = 1;
            $jsonRate = array("timeStart" => $currDate, "qtyCalled" => $qtyCalled);
            $cache->save(serialize(json_encode($jsonRate)), $paraCache);
            return true;
        }

        $jsonRateLimit = json_decode(unserialize($rateLimitCallCurl));

        $timeStart = $jsonRateLimit->timeStart;
        $qtyCalled = $jsonRateLimit->qtyCalled;

        $dateTime  = strtotime($timeStart);
        $dateTime1 = strtotime($currDate);
        if($dateTime1 != $dateTime){
            $qtyCalled = 1;
            $jsonRate = array("timeStart" => $currDate, "qtyCalled" => $qtyCalled);
            $cache->save(serialize(json_encode($jsonRate)), $paraCache);
            return true;
        }

        if($qtyCalled >= 10){
            usleep(1000000);
            $qtyCalled = 1;
            $now = date('Y-m-d H:i:s.').gettimeofday()['usec'];
            $jsonRate = array("timeStart" => $now, "qtyCalled" => $qtyCalled);
            $cache->save(serialize(json_encode($jsonRate)), $paraCache);
            return true;
        }

        $qtyCalled = (int)$qtyCalled+1;
        $jsonRate = array("timeStart" => $timeStart, "qtyCalled" => $qtyCalled);
        $cache->save(serialize(json_encode($jsonRate)), $paraCache);
        return true;
    }
}