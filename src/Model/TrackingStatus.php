<?php

namespace JouwWeb\Sendcloud\Model;

class TrackingStatus
{
    /**
     * @param \DateTimeImmutable $carrierUpdateTimestamp Timestamp related to the creation of this `TrackingStatus`.
     * @param string $parcelStatusHistoryId ID of the `TrackingStatus`.
     * @param string $parentStatus Current delivery status of the parcel. A same `parentStatus` can be related to various `carrierMessage`.
     * @param string $carrierCode A carrier represented by a Sendcloud code. Can be an empty string, even if the `Tracking->carrierCode` is not empty.
     * @param string $carrierMessage Status description specified by the carrier, more detailed and more "human-friendly" than `parentStatus`.
     */
    public function __construct(
        public readonly \DateTimeImmutable $carrierUpdateTimestamp,
        public readonly string $parcelStatusHistoryId,
        public readonly string $parentStatus,
        public readonly string $carrierCode,
        public readonly string $carrierMessage,
    ) {
    }

    public static function fromData(array $data): self
    {
        return new self(
            carrierUpdateTimestamp: new \DateTimeImmutable($data['carrier_update_timestamp']),
            parcelStatusHistoryId: (string)$data['parcel_status_history_id'],
            parentStatus: (string)$data['parent_status'],
            carrierCode: (string)$data['carrier_code'],
            carrierMessage: (string)$data['carrier_message'],
        );
    }
}