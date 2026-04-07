<?php

namespace Modules\Webhooks\Tests\Unit;

use Modules\Webhooks\Services\SignatureVerifier;
use Tests\TestCase;

class SignatureVerifierTest extends TestCase
{
    public function test_it_verifies_valid_signature()
    {
        $secret = 'test-secret';
        $payload = '{"hello":"world"}';
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        $this->assertTrue(SignatureVerifier::verify($secret, $timestamp, $payload, $signature));
    }

    public function test_it_rejects_invalid_signature()
    {
        $secret = 'test-secret';
        $payload = '{"hello":"world"}';
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        $this->assertFalse(SignatureVerifier::verify($secret, $timestamp, $payload, $signature . 'x'));
    }

    public function test_it_rejects_out_of_window()
    {
        $secret = 'test-secret';
        $payload = '{"hello":"world"}';
        $timestamp = now()->subMinutes(10)->timestamp;
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        $this->assertFalse(SignatureVerifier::verify($secret, $timestamp, $payload, $signature, 60));
    }
}
