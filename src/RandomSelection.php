<?php

/**
 * RandomSelection -- randomly displays one of the given options.
 * Usage: <choose><option>A</option><option>B</option></choose>
 * Optional parameter: <option weight="3"> == 3x weight given
 *
 * @file
 * @ingroup Extensions
 * @author Ross McClure <https://www.mediawiki.org/wiki/User:Algorithm>
 * @link https://www.mediawiki.org/wiki/Extension:RandomSelection Documentation
 */
class RandomSelection {
	private const EXT_DATA_VERSION_LABEL = 'RandomSelection-ext-version';

	/**
	 * Register the <choose> tag and {{#choose:option 1|...|option N}} function
	 * with the Parser.
	 *
	 * @param Parser &$parser
	 * @return bool
	 */
	public static function register( &$parser ): bool {
		$parser->setHook( 'choose', [ __CLASS__, 'render' ] );
		$parser->setFunctionHook( 'choose', [ __CLASS__, 'renderParserFunction' ] );
		return true;
	}

	/**
	 * Register the magic word ID.
	 *
	 * @param array &$variableIds
	 * @return bool
	 */
	public static function variableIds( &$variableIds ): bool {
		$variableIds[] = 'choose';
		return true;
	}

	/**
	 * Callback for register() which actually does all the processing.
	 *
	 * @param string $input User-supplied input
	 * @param array $argv User-supplied arguments to the tag, e.g. <choose before="...">...</choose>
	 * @param Parser $parser
	 * @return array
	 */
	public static function render( $input, $argv, $parser ): array {
		# Parse the options and calculate total weight
		$len = preg_match_all(
			"/<option(?:(?:\\s[^>]*?)?\\sweight=[\"']?([^\\s>]+))?"
				. "(?:\\s[^>]*)?>([\\s\\S]*?)<\\/option>/",
			$input,
			$out
		);

		if ( !$len ) {
			return [ '', 'isHTML' => true ];
		}

		self::addExtensionData( $parser );

		# Find any references to a surrounding template
		preg_match_all(
			"/<choicetemplate(?:(?:\\s[^>]*?)?\\sweight=[\"']?([^\\s>]+))?"
				. "(?:\\s[^>]*)?>([\\s\\S]*?)<\\/choicetemplate>/",
			$input,
			$outTemplate
		);

		$options = [];
		foreach ( $out[2] as $option ) {
			# Surround by template if applicable
			if ( isset( $outTemplate[2][0] ) ) {
				$option = '{{' . $outTemplate[2][0] . '|' . $option . '}}';
			}

			if ( isset( $argv['before'] ) ) {
				$option = $argv['before'] . $option;
			}
			if ( isset( $argv['after'] ) ) {
				$option .= $argv['after'];
			}

			$options[] = $option;
		}

		return self::parse( $parser, $options, $out[1] );
	}

	/**
	 * Callback for the {{#choose:}} magic word magic (see register() in this file)
	 *
	 * @param Parser $parser
	 * @param string[] ...$args User-supplied arguments
	 * @return array
	 */
	public static function renderParserFunction( Parser $parser, ...$args ): array {
		// First one is always null
		array_shift( $args );
		if ( !count( $args ) ) {
			return [ '', 'isHTML' => true ];
		}

		self::addExtensionData( $parser );

		return self::parse( $parser, $args );
	}

	public static function onBeforePageDisplay( OutputPage $out ): void {
		$out->addModules( 'ext.RandomSelection.js' );
	}

	public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $parserOutput ): void {
		$parsedExtensionVersion = $parserOutput->getExtensionData( self::EXT_DATA_VERSION_LABEL );
		$currentExtensionVersion = ExtensionRegistry::getInstance()->getAllThings()['RandomSelection']['version'];

		// Expire the cache of parsed pages with an old version of "RandomSelection" extension
		if ( $parsedExtensionVersion !== $currentExtensionVersion ) {
			$parserOutput->updateCacheExpiry( 0 );
		}
	}

	private static function addExtensionData( Parser $parser ): void {
		$randomSelectionVersion = ExtensionRegistry::getInstance()->getAllThings()['RandomSelection']['version'];
		$parser->getOutput()->setExtensionData( self::EXT_DATA_VERSION_LABEL, $randomSelectionVersion );
	}

	private static function parse( Parser $parser, array $options, array $weights = [] ): array {
		if ( empty( $weights ) ) {
			// Parser function doesn't support weights but let's standardize the logic for both the tags and the function
			$weights = array_fill( 0, count( $options ), 1 );
		}

		$rawResult = [];
		$runningSum = 0;
		$cumulativeWeights = [];
		foreach ( $weights as $weight ) {
			$numericValue = intval( $weight );
			// intval() returns 0 on failure and any other number lower than 1 is invalid
			if ( $numericValue <= 0 ) {
				$numericValue = 1;
			}
			$runningSum += $numericValue;
			$cumulativeWeights[] = $runningSum;
		}
		$rawResult['cumulativeWeights'] = $cumulativeWeights;

		foreach ( $options as $option ) {
			$rawResult['items'][] = $parser->recursiveTagParseFully( $option );
		}

		return [
			Html::rawElement(
				'script',
				[ 'type' => 'text/random-selection-ext-data' ],
				json_encode( $rawResult )
			),
			'isHTML' => true
		];
	}
}
