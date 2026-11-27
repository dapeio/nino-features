<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	CrmPdfProbe		A package of the suite of crm-pdf, made in the sandbox of the suite and never a feature of the catalogue. It answers /pdf/template/uses, /pdf/vocabulary and /pdf/editor as the suite tells it:
	 *										'./crm-pdf-probe/uses' (template key => package => count, or the word throw, garbage or none), './crm-pdf-probe/vocabulary' (the answer, or the word throw) and
	 *										'./crm-pdf-probe/editor' (the answer of the listener of /pdf/editor)
	 */
	class CrmPdfProbe {

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			if( \Nino\Modules\Crm::featureOn( $appData, 'crm' ) === false || \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return;

			\Nino\Callbacks::registerCallback( $appData, '/pdf/template/uses', [ self::class, 'callbackUses' ] );
			\Nino\Callbacks::registerCallback( $appData, '/pdf/vocabulary', [ self::class, 'callbackVocabulary' ] );
			\Nino\Callbacks::registerCallback( $appData, '/pdf/editor', [ self::class, 'callbackEditor' ] );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$asked
		 *
		 *	@return 	array|null
		 */
		public static function callbackUses( array &$appData, array &$asked ): ?array {

			$says = $appData['./crm-pdf-probe/uses'] ?? [];

			if( $says === 'throw' )
				throw new \RuntimeException( 'the probe says no' );

			if( $says === 'garbage' )
				return [ 'uses' => 'nothing' ];

			if( $says === 'none' )
				return null;

			return [ 'uses' => (array) $says ];
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$asked
		 *
		 *	@return 	array|null
		 */
		public static function callbackVocabulary( array &$appData, array &$asked ): ?array {

			$says = $appData['./crm-pdf-probe/vocabulary'] ?? null;

			if( $says === 'throw' )
				throw new \RuntimeException( 'the probe says no' );

			return is_array( $says ) === true ? $says : null;
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$asked
		 *
		 *	@return 	array|null
		 */
		public static function callbackEditor( array &$appData, array &$asked ): ?array {

			$says = $appData['./crm-pdf-probe/editor'] ?? null;

			return is_array( $says ) === true ? $says : null;
		}
	}
}
