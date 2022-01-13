<?php

namespace AnSms\Tests\Message\DeliveryReport;

use AnSms\Message\DeliveryReport\DeliveryReport;
use PHPUnit\Framework\TestCase;

class DeliveryReportTest extends TestCase
{
    public function testGetIdReturnsId(): void
    {
        $id = '123';
        $deliveryReport = new DeliveryReport($id, 'some status');

        $this->assertSame($id, $deliveryReport->getId());
    }

    public function testGetStatusReturnsStatus(): void
    {
        $status = 'delivered';
        $deliveryReport = new DeliveryReport('123', $status);

        $this->assertSame($status, $deliveryReport->getStatus());
    }

    public function testGetLogContextReturnsContext(): void
    {
        $id = '123';
        $status = 'delivered';
        $deliveryReport = new DeliveryReport($id, $status);

        $this->assertSame(
            [
                'id' => $id,
                'status' =>  $status,
            ],
            $deliveryReport->getLogContext()
        );
    }
}
