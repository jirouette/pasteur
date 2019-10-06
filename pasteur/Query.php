<?php
namespace Pasteur;

class Query {
    /**
     * SQL WHERE query part
     * @var string
     */
    public $query = "";
    /**
     * Entries
     * @var array
     */
    public $entries = [];

    public function __construct(string $query = null, array $entries = null) {
        if ($query !== null) {
            $this->query = $query;
        }
        if ($entries !== null) {
            $this->entries = $entries;
        }
    }
}
