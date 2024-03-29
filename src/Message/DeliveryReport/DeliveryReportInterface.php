<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message\DeliveryReport;

interface DeliveryReportInterface
{
    public function getId() : string;

    public function getStatus() : string;

    public function getLogContext() : array;
}
