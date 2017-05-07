<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

namespace AnSms\Message\DeliveryReport;

/**
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class DeliveryReport implements DeliveryReportInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $status;

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
