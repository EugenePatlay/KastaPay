<?php

declare(strict_types=1);

namespace Parfums\SaleBundle\Service\Payment\KastaPay;

interface KastaPayStatuses
{
    public const STATUS_CREATED = 'Created';
    public const STATUS_IN_PROCESSING = 'InProcessing';
    public const STATUS_WAITING_AUTH_COMPLETE = 'WaitingAuthComplete';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_PENDING = 'Pending';
    public const STATUS_EXPIRED = 'Expired';
    public const STATUS_REFUNDED = 'Refunded';
    public const STATUS_DECLINED = 'Declined';
    public const STATUS_REFUND_IN_PROCESSING = 'RefundInProcessing';

    public const ARRAY_STATUS_CANCELED = [self::STATUS_DECLINED, self::STATUS_EXPIRED];
}
