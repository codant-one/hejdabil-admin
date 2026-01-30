<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Exception;

class SparService
{
    protected Client $client;
    protected string $endpoint;
    protected string $certPath;
    protected string $certPassword;
    protected string $customerId;
    protected string $slutkundId;
    protected string $assignmentId;
    protected ?string $soapAction;
    protected bool $debug;

    public function __construct()
    {
        $this->endpoint = config('services.spar.endpoint');
        $this->certPath = config('services.spar.cert_path');
        $this->certPassword = config('services.spar.cert_password');
        $this->customerId = config('services.spar.customer_id');
        $this->slutkundId = (string) config('services.spar.slutkund_id', $this->customerId);
        $this->assignmentId = config('services.spar.assignment_id');
        $this->soapAction = config('services.spar.soap_action');
        $this->debug = (bool) config('services.spar.debug', false);

        $this->assertConfigured();

        $curlOptions = [];
        if ($this->isPkcs12Certificate($this->certPath)) {
            // SPAR suele entregar certificados cliente en formato PKCS#12 (.p12/.pfx).
            // cURL por defecto asume PEM; indicamos explícitamente el tipo.
            $curlOptions[CURLOPT_SSLCERTTYPE] = 'P12';
        }

        $this->client = new Client([
            'cert' => [$this->certPath, $this->certPassword],
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
            'verify' => true,
            'curl' => $curlOptions,
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

            $headers = [
                'Content-Type' => 'text/xml; charset=utf-8',
                'Accept' => 'text/xml',
                'User-Agent' => 'Laravel-SPAR-Client/1.0',
            ];
            if (!empty($this->soapAction)) {
                $headers['SOAPAction'] = $this->soapAction;
            }

            $response = $this->client->post($this->endpoint, [
                'headers' => $headers,
                'body' => $xmlBody,
            ]);

            $statusCode = $response->getStatusCode();
            $xmlResponse = (string) $response->getBody();

            if ($statusCode >= 400) {
                $snippet = mb_substr($xmlResponse, 0, 500);
                throw new Exception("SPAR HTTP error {$statusCode}: {$snippet}");
            }

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
                <KundNrSlutkund>{$this->slutkundId}</KundNrSlutkund>
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
        // Parse robusto: NO eliminamos namespaces con regex (puede romper XML válido).
        // Usamos XPath con local-name() para navegar Envelope/Body/Respuesta sin depender de prefijos.

        $flags = LIBXML_NONET | LIBXML_NOCDATA | LIBXML_NOBLANKS;
        $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', $flags);

        if ($xml === false) {
            throw new Exception('No se pudo leer el XML de respuesta.');
        }

        // SOAP Fault
        $faultNodes = $xml->xpath('//*[local-name()="Fault"]');
        if (!empty($faultNodes)) {
            $faultArray = $this->xmlNodeToArray($faultNodes[0]);
            $faultString = $faultArray['faultstring'] ?? null;

            if (config('app.debug')) {
                Log::warning('SPAR SOAP Fault recibido', [
                    'fault' => $faultArray,
                ]);
            }

            $msg = $faultString ? "SPAR SOAP Fault: {$faultString}" : 'SPAR SOAP Fault recibido.';
            throw new Exception($msg);
        }

        // Intentar devolver directamente SPARPersonsokningSvar
        $svarNodes = $xml->xpath('//*[local-name()="SPARPersonsokningSvar"]');
        if (!empty($svarNodes)) {
            $payload = $this->xmlNodeToArray($svarNodes[0]);

            $undantag = $this->extractUndantag($payload);
            if ($undantag !== null) {
                $kod = $undantag['kod'] ?? 'Unknown';
                $beskrivning = $undantag['beskrivning'] ?? 'Okänt undantag';
                throw new Exception("SPAR Undantag {$kod}: {$beskrivning}");
            }

            return $payload;
        }

        // Fallback: devolver Body completo (útil para ver estructura real)
        $bodyNodes = $xml->xpath('//*[local-name()="Body"]');
        if (!empty($bodyNodes)) {
            return $this->xmlNodeToArray($bodyNodes[0]);
        }

        // Último recurso: todo el XML como array
        return $this->xmlNodeToArray($xml);
    }

    protected function xmlNodeToArray(\SimpleXMLElement $node): array
    {
        $result = [];

        $text = trim((string) $node);
        if ($text !== '') {
            $result['_text'] = $text;
        }

        foreach ($node->attributes() as $attrName => $attrValue) {
            $result['@' . $attrName] = (string) $attrValue;
        }

        $namespaces = $node->getNamespaces(true);
        $namespaces[''] = $namespaces[''] ?? null;

        foreach ($namespaces as $prefix => $uri) {
            $children = $uri ? $node->children($uri) : $node->children();
            foreach ($children as $child) {
                $name = $child->getName();
                $value = $this->xmlNodeToArray($child);

                if (array_key_exists($name, $result)) {
                    if (!is_array($result[$name]) || !array_is_list($result[$name])) {
                        $result[$name] = [$result[$name]];
                    }
                    $result[$name][] = $value;
                } else {
                    $result[$name] = $value;
                }
            }
        }

        return $result;
    }

    protected function maskPersonId(string $personId): string
    {
        $clean = preg_replace('/\D+/', '', $personId) ?? '';
        if (strlen($clean) <= 4) {
            return '****';
        }
        return substr($clean, 0, 2) . str_repeat('*', max(0, strlen($clean) - 4)) . substr($clean, -2);
    }

    protected function assertConfigured(): void
    {
        $missing = [];

        if (empty($this->endpoint)) {
            $missing[] = 'SPAR_ENDPOINT';
        }
        if (empty($this->certPath)) {
            $missing[] = 'SPAR_CERT_PATH';
        }
        if (empty($this->certPassword)) {
            $missing[] = 'SPAR_CERT_PASSWORD';
        }
        if (empty($this->customerId)) {
            $missing[] = 'SPAR_CUSTOMER_ID';
        }
        if (empty($this->slutkundId)) {
            $missing[] = 'SPAR_SLUTKUND_ID/SPAR_CUSTOMER_ID';
        }
        if (empty($this->assignmentId)) {
            $missing[] = 'SPAR_ASSIGNMENT_ID';
        }

        if (!empty($missing)) {
            throw new Exception('SPAR config faltante: ' . implode(', ', $missing));
        }
    }

    protected function extractUndantag(array $payload): ?array
    {
        $undantag = $payload['Undantag'] ?? null;
        if (!is_array($undantag)) {
            return null;
        }

        $kod = $undantag['Kod']['_text'] ?? $undantag['Kod'] ?? null;
        $beskrivning = $undantag['Beskrivning']['_text'] ?? $undantag['Beskrivning'] ?? null;

        if (empty($kod) && empty($beskrivning)) {
            return null;
        }

        return [
            'kod' => is_string($kod) ? $kod : null,
            'beskrivning' => is_string($beskrivning) ? $beskrivning : null,
        ];
    }

    protected function isPkcs12Certificate(string $path): bool
    {
        $lower = strtolower($path);
        return str_ends_with($lower, '.p12') || str_ends_with($lower, '.pfx');
    }
}
