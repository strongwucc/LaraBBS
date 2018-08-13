<?php

namespace App\Http\Controllers\Api;

use Overtrue\EasySms\EasySms;
use App\Http\Requests\Api\VerificationCodeRequst;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Cache;

class VerificationCodesController extends Controller
{
    public function store (VerificationCodeRequst $requst, EasySms $easySms)
    {
        $phone = $requst->phone;

        if (!app()->environment('production')) {
            $code = '1234';
        } else {
            // 生成4位随机数，左侧补0
            $code = str_pad(random_int(1,9999),4,0,STR_PAD_LEFT);

            try {

                $result = $easySms->send($phone,[
                    'content' => "您的验证码是{$code}。如非本人操作，请忽略本短信"
                ]);

            } catch (NoGatewayAvailableException $exception) {

                $message = $exception->getException('yunpian')->getMessage();
                return $this->response->errorInternal($message ?? '短信发送异常');

            }
        }

        $key = 'verificationCode_' . str_random(15);
        $expiredAt = now()->addMinutes(10);

        Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);

        return $this->response->array([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString()
        ])->setStatusCode(201);
    }
}
