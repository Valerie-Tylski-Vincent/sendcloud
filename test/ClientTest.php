<?php

namespace Test\JouwWeb\SendCloud;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JouwWeb\SendCloud\Client;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use JouwWeb\SendCloud\Model\Address;
use JouwWeb\SendCloud\Model\Parcel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /** @var Client */
    protected $client;

    /** @var \GuzzleHttp\Client|MockObject */
    protected $guzzleClientMock;

    public function setUp(): void
    {
        $this->client = new Client('handsome public key', 'gorgeous secret key', 'aPartnerId');

        $this->guzzleClientMock = $this->createPartialMock(\GuzzleHttp\Client::class, ['request']);

        // Inject the mock HTTP client through reflection. The alternative is to pass it into the ctor but that would
        // require us to use PSR-7 requests instead of Guzzle's more convenient usage.
        $clientProperty = new \ReflectionProperty(Client::class, 'guzzleClient');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->client, $this->guzzleClientMock);
    }

    public function testGetUser(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"user":{"address":"Insulindelaan","city":"Eindhoven","company_logo":null,"company_name":"SendCloud","data":[],"email":"johndoe@sendcloud.nl","invoices":[{"date":"05-06-201811:58:52","id":1,"isPayed":false,"items":"https://local.sendcloud.sc/api/v2/user/invoices/1","price_excl":77.4,"price_incl":93.65,"ref":"1","type":"periodic"}],"modules":[{"activated":true,"id":5,"name":"SendCloudClient","settings":null,"short_name":"sendcloud_client"},{"id":3,"name":"PrestashopIntegration","settings":{"url_webshop":"http://localhost/testing/prestashop","api_key":"O8ALXHMM24QULWM213CC6SGQ5VDJKC8W"},"activated":true,"short_name":"prestashop"}],"postal_code":"5642CV","registered":"2018-05-2912:52:51","telephone":"+31626262626","username":"johndoe"}}'
        ));

        $user = $this->client->getUser();

        $this->assertEquals('johndoe', $user->getUsername());
        $this->assertEquals('SendCloud', $user->getCompanyName());
        $this->assertEquals('+31626262626', $user->getPhoneNumber());
        $this->assertEquals('Insulindelaan', $user->getAddress());
        $this->assertEquals('Eindhoven', $user->getCity());
        $this->assertEquals('5642CV', $user->getPostalCode());
        $this->assertEquals('johndoe@sendcloud.nl', $user->getEmailAddress());
        $this->assertEquals(new \DateTimeImmutable('2018-05-29 12:52:51'), $user->getRegistered());
    }

    public function testGetShippingMethods(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"shipping_methods": [{"service_point_input": "none","max_weight": "1.000","name": "Low weight shipment","carrier": "carrier_code","countries": [{"iso_2": "BE","iso_3": "BEL","id": 1,"price": 3.50,"name": "Belgium"},{"iso_2": "NL","iso_3": "NLD","id": 2,"price": 4.20,"name": "Netherlands"}],"min_weight": "0.001","id": 1,"price": 0}]}'
        ));

        $shippingMethods = $this->client->getShippingMethods();

        $this->assertCount(1, $shippingMethods);
        $this->assertEquals(1, $shippingMethods[0]->getId());
        $this->assertEquals(1, $shippingMethods[0]->getMinimumWeight());
        $this->assertEquals(1000, $shippingMethods[0]->getMaximumWeight());
        $this->assertEquals('carrier_code', $shippingMethods[0]->getCarrier());
        $this->assertEquals(['BE' => 350, 'NL' => 420], $shippingMethods[0]->getPrices());
        $this->assertEquals(420, $shippingMethods[0]->getPriceForCountry('NL'));
        $this->assertNull($shippingMethods[0]->getPriceForCountry('EN'));
    }

    public function testGetSenderAddresses(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"sender_addresses":[{"id":92837,"company_name":"AwesomeCo Inc.","contact_name":"Bertus Bernardus","email":"bertus@awesomeco.be","telephone":"+31683749586","street":"Wegstraat","house_number":"233","postal_box":"","postal_code":"8398","city":"Brussel","country":"BE"},{"id":28397,"company_name":"AwesomeCo Inc. NL","contact_name":"","email":"","telephone":"0645000000","street":"Torenallee","house_number":"20","postal_box":"","postal_code":"5617 BC","city":"Eindhoven","country":"NL"}]}'
        ));

        $senderAddresses = $this->client->getSenderAddresses();

        $this->assertCount(2, $senderAddresses);
        $this->assertEquals(92837, $senderAddresses[0]->getId());
        $this->assertEquals('AwesomeCo Inc.', $senderAddresses[0]->getCompanyName());
        $this->assertEquals('', $senderAddresses[1]->getContactName());
    }

    public function testCreateParcel(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcel":{"id":8293794,"address":"straat 23","address_2":"","address_divided":{"house_number":"23","street":"straat"},"city":"Gehucht","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"baron@vanderzanden.nl","name":"Baron van der Zanden","postal_code":"9283DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"","tracking_number":"","weight":"2.486","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}}'
        ));

        $parcel = $this->client->createParcel(
            new Address('Baron van der Zanden', null, 'straat', '23', 'Gehucht', '9283DD', 'NL', 'baron@vanderzanden.nl', null),
            null,
            '201900001',
            2486
        );

        $this->assertEquals(8293794, $parcel->getId());
        $this->assertEquals(Parcel::STATUS_NO_LABEL, $parcel->getStatusId());
        $this->assertEquals(new \DateTimeImmutable('2019-03-11 14:35:10'), $parcel->getCreated());
        $this->assertEquals('Baron van der Zanden', $parcel->getAddress()->getName());
        $this->assertEquals('', $parcel->getAddress()->getCompanyName());
        $this->assertFalse($parcel->hasLabel());
        $this->assertNull($parcel->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT));
        $this->assertEquals(2486, $parcel->getWeight());
        $this->assertEquals('201900001', $parcel->getOrderNumber());
        $this->assertNull($parcel->getShippingMethodId());
    }

    public function testUpdateParcel(): void
    {
        // Test that update only updates the address details (and not e.g., order number/weight)
        $this->guzzleClientMock->expects($this->once())->method('request')
            ->willReturnCallback(function () {
                $this->assertEquals([
                    'put',
                    'parcels',
                    [
                        'json' => [
                            'parcel' => [
                                'id' => 8293794,
                                'name' => 'Completely different person',
                                'company_name' => 'Some company',
                                'address' => 'Rosebud',
                                'house_number' => '2134A',
                                'city' => 'Almanda',
                                'postal_code' => '9238DD',
                                'country' => 'NL',
                                'email' => 'completelydifferent@email.com',
                                'telephone' => '+31699999999',
                            ],
                        ],
                    ],
                ], func_get_args());

                return new Response(
                    200,
                    [],
                    '{"parcel":{"id":8293794,"address":"Rosebud 2134A","address_2":"","address_divided":{"street":"Rosebud","house_number":"2134"},"city":"Almanda","company_name":"Some company","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"completelydifferent@email.com","name":"Completely different person","postal_code":"9238DD","reference":"0","shipment":null,"status":{"id":999,"message":"No label"},"to_service_point":null,"telephone":"+31699999999","tracking_number":"","weight":"2.490","label":{},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":null,"shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":null,"external_order_id":"8293794","external_shipment_id":"201900001"}}'
                );
            });

        $parcel = $this->client->updateParcel(8293794, new Address('Completely different person', 'Some company', 'Rosebud', '2134A', 'Almanda', '9238DD', 'NL', 'completelydifferent@email.com', '+31699999999'));

        $this->assertEquals('Some company', $parcel->getAddress()->getCompanyName());
    }

    public function testCreateLabel(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcel":{"id":8293794,"address":"Rosebud 2134A","address_2":"","address_divided":{"street":"Rosebud","house_number":"2134"},"city":"Almanda","company_name":"Some company","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"11-03-2019 14:35:10","email":"completelydifferent@email.com","name":"Completely different person","postal_code":"9238 DD","reference":"0","shipment":{"id":117,"name":"DHLForYou Drop Off"},"status":{"id":1000,"message":"Ready to send"},"to_service_point":null,"telephone":"+31699999999","tracking_number":"JVGL4004421100020097","weight":"2.490","label":{"label_printer":"https://panel.sendcloud.sc/api/v2/labels/label_printer/8293794","normal_printer":["https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=0","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=1","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=2","https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=3"]},"customs_declaration":{},"order_number":"201900001","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":"parcel","shipment_uuid":"7ade61ad-c21a-4beb-b7fd-2f579feacdb6","shipping_method":117,"external_order_id":"8293794","external_shipment_id":"201900001","carrier":{"code":"dhl"},"tracking_url":"https://jouwweb.shipping-portal.com/tracking/?country=nl&tracking_number=jvgl4004421100020097&postal_code=9238dd"}}'
        ));

        $parcel = $this->client->createLabel(8293794, 117, 61361);

        $this->assertEquals(Parcel::STATUS_READY_TO_SEND, $parcel->getStatusId());
        $this->assertEquals('JVGL4004421100020097', $parcel->getTrackingNumber());
        $this->assertEquals('https://jouwweb.shipping-portal.com/tracking/?country=nl&tracking_number=jvgl4004421100020097&postal_code=9238dd', $parcel->getTrackingUrl());
        $this->assertTrue($parcel->hasLabel());
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/labels/label_printer/8293794', $parcel->getLabelUrl(Parcel::LABEL_FORMAT_A6));
        $this->assertEquals('https://panel.sendcloud.sc/api/v2/labels/normal_printer/8293794?start_from=3', $parcel->getLabelUrl(Parcel::LABEL_FORMAT_A4_BOTTOM_RIGHT));
        $this->assertEquals('dhl', $parcel->getCarrier());
        $this->assertEquals(117, $parcel->getShippingMethodId());
    }

    public function testGetParcel(): void
    {
        $this->guzzleClientMock->expects($this->once())->method('request')->willReturn(new Response(
            200,
            [],
            '{"parcel":{"id":2784972,"address":"Teststraat 12 A10","address_2":"","address_divided":{"street":"Teststraat","house_number":"12"},"city":"Woonplaats","company_name":"","country":{"iso_2":"NL","iso_3":"NLD","name":"Netherlands"},"data":{},"date_created":"27-08-2018 11:32:04","email":"sjoerd@jouwweb.nl","name":"Sjoerd Nuijten","postal_code":"7777 AA","reference":"0","shipment":{"id":8,"name":"Unstamped letter"},"status":{"id":11,"message":"Delivered"},"to_service_point":null,"telephone":"","tracking_number":"3SYZXG192833973","weight":"1.000","label":{"label_printer":"https://panel.sendcloud.sc/api/v2/labels/label_printer/13846453","normal_printer":["https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=0","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=1","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=2","https://panel.sendcloud.sc/api/v2/labels/normal_printer/13846453?start_from=3"]},"customs_declaration":{},"order_number":"201806006","insured_value":0,"total_insured_value":0,"to_state":null,"customs_invoice_nr":"","customs_shipment_type":null,"parcel_items":[],"type":"parcel","shipment_uuid":"cb1e0f2d-4e7f-456b-91fe-0bcf09847d10","shipping_method":39,"external_order_id":"2784972","external_shipment_id":"201806006","carrier":{"code":"postnl"},"tracking_url":"https://tracking.sendcloud.sc/forward?carrier=postnl&code=3SYZXG192833973&destination=NL&lang=nl&source=NL&type=parcel&verification=7777AA"}}'
        ));

        $parcel = $this->client->getParcel(2784972);

        $this->assertEquals(2784972, $parcel->getId());
        $this->assertEquals(Parcel::STATUS_DELIVERED, $parcel->getStatusId());
    }

    public function testCancelParcel(): void
    {
        $this->guzzleClientMock->expects($this->exactly(2))->method('request')->willReturnCallback(function ($method, $url) {
            $parcelId = (int)explode('/', $url)[1];

            if ($parcelId === 8293794) {
                return new Response(200, [], '{"status":"deleted","message":"Parcel has been deleted"}');
            }

            throw new RequestException(
                'Client error: ...',
                new Request('POST', 'url'),
                new Response(400, [], '{"status":"failed","message":"Shipped parcels, or parcels being shipped, can no longer be cancelled."}')
            );
        });

        $this->assertTrue($this->client->cancelParcel(8293794));
        $this->assertFalse($this->client->cancelParcel(2784972));
    }

    public function testParseRequestException(): void
    {
        $this->guzzleClientMock->method('request')->willThrowException(new RequestException(
            "Client error: `GET https://panel.sendcloud.sc/api/v2/user` resulted in a `401 Unauthorized` response:\n{\"error\":{\"message\":\"Invalid username/password.\",\"request\":\"api/v2/user\",\"code\":401}}\n))",
            new Request('GET', 'https://some.uri'),
            new Response(401, [], '{"error":{"message":"Invalid username/password.","request":"api/v2/user","code":401}}')
        ));

        try {
            $this->client->getUser();
            $this->fail('getUser completed successfully while a SendCloudRequestException was expected.');
        } catch (SendCloudRequestException $exception) {
            $this->assertEquals(SendCloudRequestException::CODE_UNAUTHORIZED, $exception->getCode());
            $this->assertEquals(401, $exception->getSendCloudCode());
            $this->assertEquals('Invalid username/password.', $exception->getSendCloudMessage());
        }
    }

    public function testParseRequestExceptionNoBody(): void
    {
        $this->guzzleClientMock->method('request')->willThrowException(new ConnectException(
            'Failed to reach server or something.',
            new Request('GET', 'https://some.uri')
        ));

        try {
            $this->client->getUser();
            $this->fail('getUser completed successfully while a SendCloudRequestException was expected.');
        } catch (SendCloudRequestException $exception) {
            $this->assertEquals(SendCloudRequestException::CODE_CONNECTION_FAILED, $exception->getCode());
            $this->assertNull($exception->getSendCloudCode());
            $this->assertNull($exception->getSendCloudMessage());
        }
    }
}