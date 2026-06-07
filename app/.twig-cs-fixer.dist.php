<?php

declare(strict_types=1);

use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\Delimiter\DelimiterSpacingRule;
use TwigCsFixer\Rules\Function\NamedArgumentSeparatorRule;
use TwigCsFixer\Rules\Function\NamedArgumentSpacingRule;
use TwigCsFixer\Rules\Operator\OperatorNameSpacingRule;
use TwigCsFixer\Rules\Operator\OperatorSpacingRule;
use TwigCsFixer\Rules\Punctuation\PunctuationSpacingRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Rules\Whitespace\BlankEOFRule;
use TwigCsFixer\Rules\Whitespace\IndentRule;
use TwigCsFixer\Rules\Whitespace\TrailingSpaceRule;

$ruleset = new Ruleset();
$ruleset
	->addRule(new BlankEOFRule())
	->addRule(new DelimiterSpacingRule())
	->addRule(new IndentRule(spaceRatio: 4, useTab: true))
	->addRule(new NamedArgumentSeparatorRule())
	->addRule(new NamedArgumentSpacingRule())
	->addRule(new OperatorNameSpacingRule())
	->addRule(new OperatorSpacingRule())
	->addRule(new PunctuationSpacingRule())
	->addRule(new TrailingSpaceRule())
;

return (new Config('Nagadmin Twig Baseline'))
	->setFinder(
		(new Finder())
			->in(__DIR__ . '/src')
	)
	->setRuleset($ruleset)
	->setCacheFile(__DIR__ . '/var/cache/twig-cs-fixer.cache')
	->allowNonFixableRules(false)
;
