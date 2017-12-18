<?php declare(strict_types=1);

namespace Shopware\Api\Entity\Search\Term;

use Shopware\Context\Struct\TranslationContext;

class SearchTermInterpreter
{
    /**
     * @var TokenizerInterface
     */
    private $tokenizer;

    public function __construct(TokenizerInterface $tokenizer)
    {
        $this->tokenizer = $tokenizer;
    }

    public function interpret(string $term, TranslationContext $context): SearchPattern
    {
        $terms = $this->tokenizer->tokenize($term);

        $pattern = new SearchPattern(
            new SearchTerm($term)
        );

        if (count($terms) === 1) {
            return $pattern;
        }

        foreach ($terms as $part) {
            $percent = strlen($part) / strlen($term);
            $pattern->addTerm(new SearchTerm($part, $percent));
        }

        return $pattern;
    }
}
