<?php

namespace Gnm\ApiConn;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Firebase\JWT\JWT;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        #dd($params);

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

        $xform_data = true;
        $getParams = '';   // key1=val1&key2=val2
        $data_json = [];   // x-data-form as array

        if($params && ($method === "GET" || $method === "DELETE")){
            $xform_data = false;

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
        }else{
            $xform_data = true;
            $attachFile = false;
            foreach($params as $key => $val)
            {
                //$data_json[$key] =  $val;

                if(is_object($val) ){

                    if(get_class ($val) === "Illuminate\Http\UploadedFile"){
                        $temp = $val->store('temp');
                        $temp = storage_path('app/') . $temp;
                        #dd($temp );
                        $attachFile = true;
                        $data_json[$key] =  curl_file_create($temp);
                    }
                }
                else{
                    $data_json[$key] = $val;
                }
            }

            if(!$attachFile)
                $data_json = http_build_query($data_json);
        }

        #$data_json = json_encode($data_json);

        #dd($data_json);

        if($method === "GET")
            $endpoint = $endpoint.'?'.$getParams;
        else if($method === "POST")
            $endpoint = $endpoint;
        else if($method === "PUT")
            $endpoint = $endpoint.'/update';
        else if($method === "DELETE")
            $endpoint = $endpoint.'/del'.'?'.$getParams;

        //$data_json = json_encode($params);
        $userId = '';

        try {
            $userId = User::getAuthUserUid();
        } catch (\Throwable $th) {
            $userId = Auth::user()->sub;
        }

        $user = array (
            'sub' => $userId,
          );

        $token = JWT::encode($user, $this->ServiceSecret);

        $authorization = "Authorization: Bearer ".$token;



        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( $authorization, 'Accept: application/json' ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($xform_data)? $data_json :  $getParams );
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
