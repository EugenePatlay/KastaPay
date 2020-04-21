<?php

namespace Parfums\SaleBundle\Response;

class KastaPayRefundResponse extends KastaPayResponse
{
    /** @var float */
    public $baseAmount;
    /** @var string */
    public $baseCurrency;
}
