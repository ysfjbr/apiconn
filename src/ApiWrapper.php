<?php

namespace Gnm\ApiConn;

use Illuminate\Support\Facades\Cache;
use Firebase\JWT\JWT;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ApiWrapper{

    protected $ServiceUrl;
    protected $ServiceSecret;

    public function __construct($ServiceUrl, $ServiceSecret)
    {
        $this->ServiceUrl = $ServiceUrl;
        $this->ServiceSecret = $ServiceSecret;
    }

    public function getData($entity, $method = "GET",  $params=[])
    {
        $endpoint = $this->ServiceUrl .'/'. $entity;

        #dd($user);

        Cache::increment('REQAPICOUNT');

        // How many jobs now!
        $ReqQueue = Cache::get('REQAPICOUNT');

        // wait according to count of jobs  (if one job sleep 0.5 sec, 2 jobs sleep 1 sec ...)

        usleep($ReqQueue * env('API_SLEEP_TIME', 1000) * 1000);


        // for testing only!
        if($entity == "FAKE") {
            Cache::decrement('REQAPICOUNT');
            return Cache::get('REQAPICOUNT');
        }

        // Register this request for monitoring (URl & Time)
        $ReqsInCache =  (Cache::has('APIREQUESTS')) ? Cache::get('APIREQUESTS') : [];

        $mt = microtime(true);
        $millsec = round( $mt * 1000 ) - (floor($mt) * 1000) ;

        $paramStr = '';
        try {
            $paramStr = implode(',',$params);
        } catch (\Throwable $th) {
            //throw $th;
        }

        array_push($ReqsInCache, 'URL : '. $endpoint.' | Params: '.$paramStr.' | Time : '.now()." > ".$millsec);
        Cache::put('APIREQUESTS', $ReqsInCache, Carbon::now()->addDay(1));

        $params_arr = (is_array($params))? $params: $params->all();

        $getParams = implode('&', array_map(
            function ($v, $k) {
                if(is_array($v)){
                    return $k.'[]='.implode('&'.$k.'[]=', $v);
                }else{
                    return $k.'='.$v;
                }
            },
            $params_arr,
            array_keys($params_arr)
        ));

        if($method === "GET")
            $endpoint = $endpoint.'?'.$getParams;
        else if($method === "POST")
            $endpoint = $endpoint;
        else if($method === "PUT")
            $endpoint = $endpoint.'/update';

        $ch = curl_init($endpoint);

        $data_json = json_encode($params);

        $user= Auth::user();

        $user = array (
            'sub' => $user->sub,
            'name' => $user->name,
            'admin' => true,
          );

        $token = JWT::encode($user, $this->ServiceSecret);

        $authorization = "Authorization: Bearer ".$token;

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        // After Requesting jobs will be decreased by 1
        Cache::decrement('REQAPICOUNT');

        return $result;

    }


    public static function getCacheKey($type, $key){
        // Key and Type input tirm and to upper case
        $key = strtoupper(trim($key));
        $type = strtoupper(trim($type));

        //Multiple spaces to one space
        $key = preg_replace('/\s+/', ' ', $key);

        // Replace Spaces with (+)
        $key = str_replace(" ","+", $key);

        // for example " The   World " => "THE+WORLD"
        return 'SHOW.'.$type.'.'.$key;
    }

    //Get array of Last requests to 3rd party in last 24 hours
    public static function getApiRequestCache()
    {
        return (Cache::has('APIREQUESTS')) ? Cache::get('APIREQUESTS') : [];
    }


}
