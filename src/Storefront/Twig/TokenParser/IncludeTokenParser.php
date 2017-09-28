<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Storefront\Twig\TokenParser;

use Shopware\Storefront\Twig\TemplateFinder;
use Twig_Node_Include;
use Twig_Token;

final class IncludeTokenParser extends \Twig_TokenParser
{
    /**
     * @var TemplateFinder
     */
    private $finder;

    public function __construct(TemplateFinder $finder)
    {
        $this->finder = $finder;
    }

    public function parse(Twig_Token $token)
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();

        //resolves parent template
        $parent = $this->finder->find(
            //set pointer to next value (contains the template file name)
            $this->getTemplateName($expr->getAttribute('value')),
            true
        );

        $expr->setAttribute('value', $parent);

        [$variables, $only, $ignoreMissing] = $this->parseArguments();

        return new Twig_Node_Include($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    public function sparse(Twig_Token $token)
    {
        //get full token stream to inject extends token for inheritance
        $stream = $this->parser->getStream();

        //resolves parent template
        $parent = $this->finder->find(
            //set pointer to next value (contains the template file name)
            $this->getTemplateName(
                $stream->next()->getValue()
            )
        );

        //set pointer to end of line - BLOCK_END_TYPE
        $stream->next();

        $stream->injectTokens([
            new Twig_Token(Twig_Token::BLOCK_START_TYPE, '', 2),
            new Twig_Token(Twig_Token::NAME_TYPE, 'include', 2),
            new Twig_Token(Twig_Token::STRING_TYPE, $parent, 2),
            new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 2),
        ]);
    }

    public function getTag(): string
    {
        return 'sw_include';
    }

    protected function parseArguments()
    {
        $stream = $this->parser->getStream();

        $ignoreMissing = false;
        if ($stream->nextIf(Twig_Token::NAME_TYPE, 'ignore')) {
            $stream->expect(Twig_Token::NAME_TYPE, 'missing');

            $ignoreMissing = true;
        }

        $variables = null;
        if ($stream->nextIf(Twig_Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $only = false;
        if ($stream->nextIf(Twig_Token::NAME_TYPE, 'only')) {
            $only = true;
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return [$variables, $only, $ignoreMissing];
    }

    private function getTemplateName(string $template): string
    {
        //remove static template inheritance prefix
        if (0 !== strpos($template, '@')) {
            return $template;
        }

        $template = explode('/', $template);
        array_shift($template);
        $template = implode('/', $template);

        return $template;
    }
}