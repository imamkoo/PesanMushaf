<?php

namespace App\Contracts;

use App\Models\Registration;

interface CreatesSnapPaymentToken
{
    public function createTokenForRegistration(Registration $registration): string;
}
