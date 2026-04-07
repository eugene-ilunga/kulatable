<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;
use App\Services\Freshpay\FreshpayCallbackService;

class FreshpayCallbackServiceTest extends TestCase
{
    public function test_it_accepts_plain_json_callbacks_without_signature(): void
    {
        $service = new FreshpayCallbackService();

        $request = Request::create('/freshpay/webhook', 'POST', [
            'reference' => 'fp_plan_1_TESTREF',
            'trans_status' => 'successful',
        ]);

        $gateway = (object) [
            'freshpay_callback_hmac_key' => null,
            'freshpay_callback_secret_key' => null,
            'freshpay_merchant_secret' => 'merchant-secret',
        ];

        $result = $service->resolvePayload($request, $gateway, ['scope' => 'test']);

        $this->assertNull($result['response']);
        $this->assertSame('fp_plan_1_TESTREF', $result['payload']['reference']);
        $this->assertSame('successful', $result['payload']['trans_status']);
    }

    public function test_it_decrypts_signed_callbacks(): void
    {
        $service = new FreshpayCallbackService();

        $gateway = (object) [
            'freshpay_callback_hmac_key' => 'hmac-secret',
            'freshpay_callback_secret_key' => '1234567890123456',
            'freshpay_merchant_secret' => 'merchant-secret',
        ];

        $payload = json_encode([
            'Reference' => 'fp_plan_1_ENCRYPTED',
            'Trans_Status' => 'successful',
        ], JSON_THROW_ON_ERROR);

        $encrypted = openssl_encrypt(
            $payload,
            'AES-128-CBC',
            $gateway->freshpay_callback_secret_key,
            OPENSSL_RAW_DATA,
            substr($gateway->freshpay_callback_secret_key, 0, 16)
        );

        $encodedData = base64_encode($encrypted);
        $signature = hash_hmac('sha256', $encodedData, $gateway->freshpay_callback_hmac_key);

        $request = Request::create('/freshpay/webhook', 'POST', [
            'data' => $encodedData,
        ]);
        $request->headers->set('X-Signature', $signature);

        $result = $service->resolvePayload($request, $gateway, ['scope' => 'test']);

        $this->assertNull($result['response']);
        $this->assertSame('fp_plan_1_ENCRYPTED', $result['payload']['Reference']);
        $this->assertSame('successful', $result['payload']['Trans_Status']);
    }

    public function test_it_validates_allowed_ips_from_config_string(): void
    {
        config()->set('services.freshpay.allowed_ips', '127.0.0.1,10.0.0.5');

        $service = new FreshpayCallbackService();
        $request = Request::create('/freshpay/webhook', 'POST', server: ['REMOTE_ADDR' => '10.0.0.5']);

        $this->assertTrue($service->validateIp($request));
    }
}
