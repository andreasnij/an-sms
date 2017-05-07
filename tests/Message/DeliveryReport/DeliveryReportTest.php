<?php

namespace Tests\Message\Address;

use AnSms\Message\DeliveryReport\DeliveryReport;
use PHPUnit\Framework\TestCase;

class DeliveryReportTest extends TestCase
{
    public function testGetIdReturnsId()
    {
        $id = '123';
        $deliveryReport = new DeliveryReport($id, 'some status');

        $this->assertSame($id, $deliveryReport->getId());
    }

    public function testGetStatusReturnsStatus()
    {
        $status = 'delivered';
        $deliveryReport = new DeliveryReport('123', $status);

        $this->assertSame($status, $deliveryReport->getStatus());
    }

    public function testGetLogContextReturnsContext()
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
