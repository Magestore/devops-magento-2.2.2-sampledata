<?php
namespace Braintree;

use InvalidArgumentException;

/**
 * Braintree DisputeGateway module
 * PHP Version 5
 * Creates and manages Braintree Disputes
 *
 * @package   Braintree
 */
class DisputeGateway
{
    /**
     * @var Gateway
     */
    private $_gateway;

    /**
     * @var Configuration
     */
    private $_config;

    /**
     * @var Http
     */
    private $_http;

    /**
     * @param Gateway $gateway
     */
    public function __construct($gateway)
    {
        $this->_gateway = $gateway;
        $this->_config = $gateway->config;
        $this->_config->assertHasAccessTokenOrKeys();
        $this->_http = new Http($gateway->config);
    }

    /* public class methods */

    /**
     * Accepts a dispute, given a dispute ID
     *
     * @param string $id
     */
    public function accept($id)
    {
        try {
            if (trim($id) == "") {
                throw new Exception\NotFound();
            }

            $path = $this->_config->merchantPath() . '/disputes/' . $id . '/accept';
            $response = $this->_http->put($path);

            if (isset($response['apiErrorResponse'])) {
                return new Result\Error($response['apiErrorResponse']);
            }

            return new Result\Successful();
        } catch (Exception\NotFound $e) {
            throw new Exception\NotFound('dispute with id "' . $id . '" not found');
        }
    }

    /**
     * Adds file evidence to a dispute, given a dispute ID and a document ID
     *
     * @param string $disputeId
     * @param string $documentId
     */
    public function addFileEvidence($disputeId, $documentId)
    {
        if (trim($disputeId) == "") {
            throw new Exception\NotFound('dispute with id "' . $disputeId . '" not found');
        }

        if (trim($documentId) == "") {
            throw new Exception\NotFound('document with id "' . $documentId . '" not found');
        }

        try {
            if (trim($disputeId) == "") {
                throw new Exception\NotFound();
            }

            $path = $this->_config->merchantPath() . '/disputes/' . $disputeId . '/evidence';
            $response = $this->_http->post($path, [
                'document_upload_id' => $documentId
            ]);

            if (isset($response['apiErrorResponse'])) {
                return new Result\Error($response['apiErrorResponse']);
            }

            if (isset($response['evidence'])) {
                $evidence = new Dispute\EvidenceDetails($response['evidence']);
                return new Result\Successful($evidence);
            }
        } catch (Exception\NotFound $e) {
            throw new Exception\NotFound('dispute with id "' . $disputeId . '" not found');
        }
    }

    /**
     * Adds text evidence to a dispute, given a dispute ID and content
     *
     * @param string $id
     * @param string $content
     */
    public function addTextEvidence($id, $content)
    {
        if (trim($content) == "") {
            throw new InvalidArgumentException('content cannot be blank');
        }

        try {
            if (trim($id) == "") {
                throw new Exception\NotFound();
            }

            $path = $this->_config->merchantPath() . '/disputes/' . $id . '/evidence';
            $response = $this->_http->post($path, [
                'comments' => $content
            ]);

            if (isset($response['apiErrorResponse'])) {
                return new Result\Error($response['apiErrorResponse']);
            }

            if (isset($response['evidence'])) {
                $evidence = new Dispute\EvidenceDetails($response['evidence']);
                return new Result\Successful($evidence);
            }
        } catch (Exception\NotFound $e) {
            throw new Exception\NotFound('dispute with id "' . $id . '" not found');
        }
    }

    /**
     * Finalize a dispute, given a dispute ID
     *
     * @param string $id
     */
    public function finalize($id)
    {
        try {
            if (trim($id) == "") {
                throw new Exception\NotFound();
            }

            $path = $this->_config->merchantPath() . '/disputes/' . $id . '/finalize';
            $response = $this->_http->put($path);

            if (isset($response['apiErrorResponse'])) {
                return new Result\Error($response['apiErrorResponse']);
            }

            return new Result\Successful();
        } catch (Exception\NotFound $e) {
            throw new Exception\NotFound('dispute with id "' . $id . '" not found');
        }
    }

    /**
     * Find a dispute, given a dispute ID
     *
     * @param string $id
     */
    public function find($id)
    {
        if (trim($id) == "") {
            throw new Exception\NotFound('dispute with id "' . $id . '" not found');
        }

        try {
            $path = $this->_config->merchantPath() . '/disputes/' . $id;
            $response = $this->_http->get($path);
            return Dispute::factory($response['dispute']);
        } catch (Exception\NotFound $e) {
            throw new Exception\NotFound('dispute with id "' . $id . '" not found');
        }
    }

    /**
     * Remove evidence from a dispute, given a dispute ID and evidence ID
     *
     * @param string $disputeId
     * @param string $evidenceId
     */
    public function removeEvidence($disputeId, $evidenceId)
    {
        try {
            if (trim($disputeId) == "" || trim($evidenceId) == "") {
                throw new Exception\NotFound();
            }

            $path = $this->_config->merchantPath() . '/disputes/' . $disputeId . '/evidence/' . $evidenceId;
            $response = $this->_http->delete($path);

            if (isset($response['apiErrorResponse'])) {
                return new Result\Error($response['apiErrorResponse']);
            }

            return new Result\Successful();
        } catch (Exception\NotFound $e) {
            throw new Exception\NotFound('evidence with id "' . $evidenceId . '" for dispute with id "' . $disputeId . '" not found');
        }
    }

    /**
     * Search for Disputes, given a DisputeSearch query
     *
     * @param DisputeSearch $query
     */
    public function search($query)
    {
        $criteria = [];
        foreach ($query as $term) {
            $criteria[$term->name] = $term->toparam();
        }
        $pager = [
            'object' => $this,
            'method' => 'fetchDisputes',
            'query' => $criteria
        ];
        return new PaginatedCollection($pager);
    }

    public function fetchDisputes($query, $page)
    {
        $response = $this->_http->post($this->_config->merchantPath() . '/disputes/advanced_search?page=' . $page, [
            'search' => $query
        ]);
        $body = $response['disputes'];
        $disputes = Util::extractattributeasarray($body, 'dispute');
        $totalItems = $body['totalItems'][0];
        $pageSize = $body['pageSize'][0];
        return new PaginatedResult($totalItems, $pageSize, $disputes);
    }
}
class_alias('Braintree\DisputeGateway', 'Braintree_DisputeGateway');
