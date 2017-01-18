<?php

namespace DevGroup\FlexIntegration\base;

class SearchQuery
{
    /**
     * @var string Key of AbstractEntityCollection to fill with results
     */
    public $key = '';

    /**
     * @var int the number of models to be fetched in each batch.
     */
    public $batchSize = 100;

    /** @var string Class name of ActiveRecord model */
    public $modelClass = '';

    /** @var array Search existing models by this attributes */
    public $searchBy = [];

    /** @var array Search by primary keys */
    public $searchPk = [];
}
