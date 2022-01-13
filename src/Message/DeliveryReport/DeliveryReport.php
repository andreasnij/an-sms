<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message\DeliveryReport;

class DeliveryReport implements DeliveryReportInterface
{
    protected string $id;
    protected string $status;

    public function __construct(string $id, string $status)
    {
        $this->id = $id;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLogContext(): array
    {
        return [
            'id' => $this->id,
            'status' =>  $this->status,
        ];
    }
}
