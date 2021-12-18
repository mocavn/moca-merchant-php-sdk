<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Moca\Merchant\MerchantIntegrationOnline;
use MocaMerchant;

class DemoControllers extends Controller
{

    protected $code, $state, $access_token, $partnerTxIDOrig, $partnerRefundTxID, $originTxID ;

    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $partnerTxID = md5(uniqid(rand(), true));
        session(['partnerTxID' => $partnerTxID]);
        $this->state = $this->generateRandomString(7);
        $call = new MerchantIntegrationOnline('STG','VN','fd092e5b-900c-4969-8c2f-48ab29ef9d67','nRrOISCpbpgFx3D_','0a46279c-c38c-480b-9fda-1466a5700445','e9b5560b0be844a2ad55c6afa8b23fbb','BDGSPQYYUqLXNkmy','http://localhost:8888/result');

        $resp = $call->onaCreateWebUrl($partnerTxID, $partnerTxID, 6000, 'VND', "testing otc", false,  [], [], [], [], $this->state);

        if ($resp->code == 200) {
            return redirect($resp->body);
        } else {
            return view('card')->with("error");
        }
    }
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function GetResponse(Request $request) {
        if(!empty($request->error)) {
            return view('result',['result' =>$request->error]);
        } else {
            $code = $request->code;
            $result = null;

            $call = new MerchantIntegrationOnline('STG','VN','fd092e5b-900c-4969-8c2f-48ab29ef9d67','nRrOISCpbpgFx3D_','0a46279c-c38c-480b-9fda-1466a5700445','e9b5560b0be844a2ad55c6afa8b23fbb','BDGSPQYYUqLXNkmy','http://localhost:8888/result');

            $resp = $call->onaOAuth2Token(session('partnerTxID'),$code);

            if($resp->code == 200) {
                $access_token = $resp->body->access_token;
                session(['access_token' => $access_token]);

                $respComplete = $call->onaChargeComplete(session('partnerTxID'), $access_token);

                if($respComplete->code == 200) {
                    $partnerTxIDOrig = session('partnerTxID');
                    $result = "success";
                    session(['partnerTxIDOrig' => $partnerTxIDOrig]);
                    $originTxID = $respComplete->body->txID;
                }

                return view('result',['result' =>$respComplete]);
            } else {
                $result = "failt";
                return view('result',['result' =>$result]);
            }
        }
    }

    public function getChargeStatus() {
        $call = new MerchantIntegrationOnline('STG','VN','fd092e5b-900c-4969-8c2f-48ab29ef9d67','nRrOISCpbpgFx3D_','0a46279c-c38c-480b-9fda-1466a5700445','e9b5560b0be844a2ad55c6afa8b23fbb','BDGSPQYYUqLXNkmy','http://localhost:8888/result');

        return $call->onaGetChargeStatus($this->partnerTxIDOrig, "VND",$this->access_token);
    }

    public function refundOnA() {
        $call = new MerchantIntegrationOnline('STG','VN','fd092e5b-900c-4969-8c2f-48ab29ef9d67','nRrOISCpbpgFx3D_','0a46279c-c38c-480b-9fda-1466a5700445','e9b5560b0be844a2ad55c6afa8b23fbb','BDGSPQYYUqLXNkmy','http://localhost:8888/result');
        $partnerTxID = md5(uniqid(rand(), true));

        $resp = $call->onaRefund($partnerTxID, $partnerTxID, 1000, "VND", $this->originTxID, "refund", $this->access_token);

        if($resp->code ==200) {
            $this->partnerRefundTxID = $this->partnerTxID;
        }

        return $resp;
    }

    public function getRedundStatus() {
        $call = new MerchantIntegrationOnline('STG','VN','fd092e5b-900c-4969-8c2f-48ab29ef9d67','nRrOISCpbpgFx3D_','0a46279c-c38c-480b-9fda-1466a5700445','e9b5560b0be844a2ad55c6afa8b23fbb','BDGSPQYYUqLXNkmy','http://localhost:8888/result');

        return $call->onaGetRefundStatus($this->partnerRefundTxID, "VND", $this->access_token);
    }

    private function generateRandomString($length) {
        $text = '';
        $possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ( $i = 0; $i < $length; $i++) {
            $text .= $possible[rand(0, strlen($possible)-1)];
        }
        return $text;
    }
}
