<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

class SparService
{
    protected Client $client;
    protected string $endpoint;
    protected string $certPath;
    protected string $certPassword;
    protected string $customerId;
    protected string $assignmentId;

    public function __construct()
    {
        $this->endpoint = config('services.spar.endpoint');
        $this->certPath = config('services.spar.cert_path');
        $this->certPassword = config('services.spar.cert_password');
        $this->customerId = config('services.spar.customer_id');
        $this->assignmentId = config('services.spar.assignment_id');

        $this->client = new Client([
            'cert' => [$this->certPath, $this->certPassword],
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Search for a person in SPAR by their ID number.
     *
     * @param string $personId The person's ID number (personnummer)
     * @return array The parsed response as a PHP array
     * @throws Exception
     */
    public function searchPerson(string $personId): array
    {
        try {
            $xmlBody = $this->buildXmlRequest($personId);

            $response = $this->client->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'text/xml',
                ],
                'body' => $xmlBody,
            ]);

            $xmlResponse = $response->getBody()->getContents();
            return $this->parseXmlResponse($xmlResponse);

        } catch (GuzzleException $e) {
            throw new Exception('SPAR API request failed: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception('Error processing SPAR response: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Build the XML request body for SPAR person search.
     *
     * @param string $personId
     * @return string
     */
    protected function buildXmlRequest(string $personId): string
    {
        return <<<XML
<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
  <Body>
    <SPARPersonsokningFraga xmlns="http://statenspersonadressregister.se/schema/personsok/2023.1/personsokningfraga">
      <Identifieringsinformation xmlns="http://statenspersonadressregister.se/schema/komponent/metadata/identifieringsinformationWs-1.1">
        <KundNrLeveransMottagare>{$this->customerId}</KundNrLeveransMottagare>
        <KundNrSlutkund>{$this->customerId}</KundNrSlutkund>
        <UppdragId>{$this->assignmentId}</UppdragId>
        <SlutAnvandarId>Laravel-App</SlutAnvandarId>
      </Identifieringsinformation>
      <PersonsokningFraga xmlns="http://statenspersonadressregister.se/schema/komponent/sok/personsokningsokparametrar-1.2">
        <IdNummer xmlns="http://statenspersonadressregister.se/schema/komponent/person/person-1.3">{$personId}</IdNummer>
      </PersonsokningFraga>
    </SPARPersonsokningFraga>
  </Body>
</Envelope>
XML;
    }

    /**
     * Parse the XML response from SPAR into a PHP array.
     *
     * @param string $xmlString
     * @return array
     * @throws Exception
     */
    protected function parseXmlResponse(string $xmlString): array
    {
        // 1. TRUCO DE MAGIA: Eliminar los namespaces (ej: ns26:Body -> Body)
        // Esto permite que PHP lea el XML como texto plano sin confundirse.
        $cleanXml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$3", $xmlString);

        // 2. Cargar el XML limpio
        $xml = simplexml_load_string($cleanXml);

        if ($xml === false) {
            throw new Exception('No se pudo leer el XML de respuesta.');
        }

        // 3. Convertir a JSON y luego a Array (manera estándar de Laravel)
        $json = json_encode($xml);
        $array = json_decode($json, true);

        // 4. Navegar directamente a los datos para no devolver basura
        // La estructura limpia suele ser Envelope -> Body -> SPARPersonsokningSvar
        if (isset($array['Body']['SPARPersonsokningSvar'])) {
            return $array['Body']['SPARPersonsokningSvar'];
        }

        // Si la estructura es distinta, devolvemos todo para debuguear
        return $array;
    }
}
