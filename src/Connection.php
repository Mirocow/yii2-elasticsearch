<?php
namespace mirocow\elasticsearch;

use mirocow\elasticsearch\components\indexes\AbstractSearchIndex;

/**
 * Class Connection
 * @package mirocow\elasticsearch\components\indexes
 *
 * @example:
<?php
    use mirocow\elasticsearch\Connection

    class ProductIndex extends Connection
    {
        private $products;

        public function __construct() {
            parent::__construct();

            $this->products = new ProductRepository();
        }

        public function accepts($document)
        {
            return $document instanceof Product;
        }

        public function documentIds()
        {
            return $this->products->ids();
        }

        public function documentCount()
        {
            return $this->products->count();
        }

        try {
            $document = $this->products->get($documentId);
        } catch (EntityNotFoundException $e) {
            throw new SearchIndexerException('Product with id '.$documentId.' does not exist', 0, $e);
        }

        $body = [
            'id' => $product->id,
            'title' => [
                'ru' => $productName,
                'en' => $productName,
            ],
            'attributes' => $product->attributes,
        ];

        if($this->documentExists($document->id)){
            return $this->documentUpdateById($document->id, $body);
        } else {
            return $this->documentCreate($document->id, $body);
        }

    }
 *
 */
abstract class Connection extends AbstractSearchIndex
{

}