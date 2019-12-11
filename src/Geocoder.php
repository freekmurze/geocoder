<?php

namespace Spatie\Geocoder;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Spatie\Geocoder\Exceptions\CouldNotGeocode;

class Geocoder
{
    const RESULT_NOT_FOUND = 'result_not_found';

    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var string */
    protected $endpoint = 'https://maps.googleapis.com/maps/api/geocode/json';

    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $language;

    /** @var string */
    protected $region;

    /** @var string */
    protected $bounds;

    /** @var string */
    protected $country;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function setLanguage(string $language)
    {
        $this->language = $language;

        return $this;
    }

    public function setRegion(string $region)
    {
        $this->region = $region;

        return $this;
    }

    public function setBounds(string $bounds)
    {
        $this->bounds = $bounds;

        return $this;
    }

    public function setCountry(string $country)
    {
        $this->country = $country;

        return $this;
    }

    public function getCoordinatesForAddress(string $address): array
    {
        if (empty($address)) {
            return $this->emptyResponse()->first();
        }

        $payload = $this->getRequestPayload(compact('address'));
        $response = $this->client->request('GET', $this->endpoint, $payload);

        if ($response->getStatusCode() !== 200) {
            throw CouldNotGeocode::couldNotConnect();
        }

        $geocodingResponse = json_decode($response->getBody());

        if (! empty($geocodingResponse->error_message)) {
            throw CouldNotGeocode::serviceReturnedError($geocodingResponse->error_message);
        }

        if (! count($geocodingResponse->results)) {
            return $this->emptyResponse()->first();;
        }

        return $this->formatResponse($geocodingResponse)->first();
    }

    public function getAllCoordinatesForAddress(string $address): Collection
    {
        if (empty($address)) {
            return $this->emptyResponse();
        }

        $payload = $this->getRequestPayload(compact('address'));
        $response = $this->client->request('GET', $this->endpoint, $payload);

        if ($response->getStatusCode() !== 200) {
            throw CouldNotGeocode::couldNotConnect();
        }

        $geocodingResponse = json_decode($response->getBody());

        if (! empty($geocodingResponse->error_message)) {
            throw CouldNotGeocode::serviceReturnedError($geocodingResponse->error_message);
        }

        if (! count($geocodingResponse->results)) {
            return $this->emptyResponse();
        }

        return $this->formatResponse($geocodingResponse);
    }

    public function getAddressForCoordinates(float $lat, float $lng): array
    {
        $payload = $this->getRequestPayload([
            'latlng' => "$lat,$lng",
        ]);
        $response = $this->client->request('GET', $this->endpoint, $payload);
        if ($response->getStatusCode() !== 200) {
            throw CouldNotGeocode::couldNotConnect();
        }
        $reverseGeocodingResponse = json_decode($response->getBody());
        if (! empty($reverseGeocodingResponse->error_message)) {
            throw CouldNotGeocode::serviceReturnedError($reverseGeocodingResponse->error_message);
        }
        if (! count($reverseGeocodingResponse->results)) {
            return $this->emptyResponse()->first();
        }
        return $this->formatResponse($reverseGeocodingResponse)->first();
    }

    protected function formatResponse($response): Collection
    {
        $locations = new Collection;

        foreach($response->results as $result) {
            $locations->push([
                'lat' => $result->geometry->location->lat,
                'lng' => $result->geometry->location->lng,
                'accuracy' => $result->geometry->location_type,
                'formatted_address' => $result->formatted_address,
                'viewport' => $result->geometry->viewport,
                'address_components' => $result->address_components,
                'place_id' => $result->place_id,
            ]);
        }

        return $locations;
    }

    protected function getRequestPayload(array $parameters): array
    {
        $parameters = array_merge([
            'key' => $this->apiKey,
            'language' => $this->language,
            'region' => $this->region,
            'bounds' => $this->bounds,
        ], $parameters);

        if ($this->country) {
            $parameters = array_merge(
                $parameters,
                ['components' => 'country:'.$this->country]
            );
        }

        return ['query' => $parameters];
    }

    protected function emptyResponse(): Collection
    {
        return new Collection([
            'lat' => 0,
            'lng' => 0,
            'accuracy' => static::RESULT_NOT_FOUND,
            'formatted_address' => static::RESULT_NOT_FOUND,
            'viewport' => static::RESULT_NOT_FOUND,
        ]);
    }
}
