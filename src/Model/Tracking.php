<?php

namespace JouwWeb\Sendcloud\Model;

class Tracking
{
    /**
     * @param int $parcelId ID for the `Parcel` related to the `Tracking`.
     * @param string $carrierCode A carrier represented by a Sendcloud code.
     * @param \DateTimeImmutable|null $createdAt Timestamp indicating when the `Parcel` was first tracked by Sendcloud’s systems.
     * @param string $carrierTrackingUrl Carrier’s tracking page for this parcel.
     * @param string|null $sendcloudTrackingUrl SendCloud’s Tracking page for this parcel. Null if the tracking page for the brand associated with the parcel has not been published.
     * @param bool $isReturn True if the `Parcel` concerns a return to the merchant.
     * @param bool $isToServicePoint True if the `Parcel` last mile is different than home address.
     * @param bool $isMailBox Indicates whether this `Parcel` will be delivered to a mail box.
     * @param \DateTimeImmutable|null $expectedDeliveryDate Estimated date of delivery.
     * @param array $statuses List of all the package statuses, with timestamps, statuses and messages.
     */
    public function __construct(
        public readonly int $parcelId,
        public readonly string $carrierCode,
        public readonly \DateTimeImmutable $createdAt,
        public readonly string $carrierTrackingUrl,
        public readonly ?string $sendcloudTrackingUrl,
        public readonly bool $isReturn,
        public readonly bool $isToServicePoint,
        public readonly bool $isMailBox,
        public readonly ?\DateTimeImmutable $expectedDeliveryDate,
        public readonly array $statuses,
    ) {
    }
    public static function fromData(array $data): self
    {
        return new self(
            parcelId: (int)$data['parcel_id'],
            carrierCode: (string)$data['carrier_code'],
            createdAt: new \DateTimeImmutable((string)$data['created_at']),
            carrierTrackingUrl: (string)$data['carrier_tracking_url'],
            sendcloudTrackingUrl: (string)$data['sendcloud_tracking_url'],
            isReturn: (bool)$data['is_return'],
            isToServicePoint: (bool)$data['is_to_service_point'],
            isMailBox: (bool)$data['is_mail_box'],
            expectedDeliveryDate: new \DateTimeImmutable($data['expected_delivery_date']),
            statuses: array_map(TrackingStatus::fromData(...), $data['statuses']),
        );
    }
}