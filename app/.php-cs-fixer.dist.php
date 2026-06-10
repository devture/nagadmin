<?php

$finder = (new PhpCsFixer\Finder())
	->files()
	->name('*.php')
	->in([
		__DIR__ . '/src',
	])
	->append([
		__DIR__ . '/.php-cs-fixer.dist.php',
	]);

return (new PhpCsFixer\Config())
	->setRules([
		'blank_line_after_namespace' => true,
		'cast_spaces' => true,
		'class_reference_name_casing' => true,
		'constant_case' => true,
		'concat_space' => ['spacing' => 'one'],
		'elseif' => true,
		'indentation_type' => true,
		'lambda_not_used_import' => true,
		'line_ending' => true,
		'lowercase_keywords' => true,
		'magic_constant_casing' => true,
		'method_argument_space' => true,
		'native_function_casing' => true,
		'native_type_declaration_casing' => true,
		'no_closing_tag' => true,
		'no_leading_import_slash' => true,
		'no_unneeded_import_alias' => true,
		'no_spaces_around_offset' => true,
		'no_unused_imports' => true,
		'normalize_index_brace' => true,
		'no_trailing_whitespace' => true,
		'no_whitespace_in_blank_line' => true,
		'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'none'],
		'return_type_declaration' => true,
		'self_static_accessor' => true,
		'single_line_after_imports' => true,
		'single_import_per_statement' => true,
		'single_space_around_construct' => true,
		'single_blank_line_at_eof' => true,
		'single_trait_insert_per_statement' => true,
		'ternary_operator_spaces' => true,
		'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['arguments', 'array_destructuring', 'arrays', 'match', 'parameters']],
		'type_declaration_spaces' => true,
		'trim_array_spaces' => true,
	])
	->setCacheFile(__DIR__ . '/var/cache/php-cs-fixer.cache')
	->setIndent("\t")
	->setLineEnding("\n")
	->setFinder($finder);
